<?php


namespace App\Http\Services;


use App\Person;
use App\Reference;
use App\Book;
use App\Exceptions\ImportRowException;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReferenceImportService
{
    private $validRows = [];
    private $allKey = [];

    private $duplicateRows = []; // 與資料庫比對 unique key 重複
    private $repeatRows = []; // 檔案內部重複
    private $errorRows = [];
    private $warningRows = []; //

    private $authors;
    private $uniqueReference = []; // 檔案內 title+year+authors 去重

    private array $columnMap = [];  // 欄名 => 數字索引（1 起算）
    private Worksheet $sheet;
    
    /** @var callable|null  function(string $phase, int $done) */
    public $onProgress = null;

    private function reportProgress(string $phase, int $done): void
    {
        if (is_callable($this->onProgress)) {
            ($this->onProgress)($phase, $done);
        }
    }

    // 儲存格值的列舉對照（內容仍為中文，僅表頭改英文）
    private $typeMap = [
        '期刊文章' => Reference::TYPE_JOURNAL,
        '書籍文章(章節)' => Reference::TYPE_BOOK_ARTICLE,
        '書籍' => Reference::TYPE_BOOK,
    ];

    private $languageMapping = [
        '英文' => 'en-us',
        '繁體中文' => 'zh-tw',
        '日文' => 'jp-jp',
        '簡體中文' => 'zh-cn',
        '德文' => 'de-de',
        '法文' => 'fr-fr',
        '拉丁文' => 'lat',
        '其他' => 'others',
    ];

    public function __construct($sheet)
    {
        $this->sheet = $sheet;
    }

    // ---- 讀取工具：一律用欄名 ----

    private function buildColumnMap(): void
    {
        if (!empty($this->columnMap)) {
            return;
        }
        $highestColumnIndex = Coordinate::columnIndexFromString($this->sheet->getHighestColumn());
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $header = trim((string) $this->sheet->getCellByColumnAndRow($col, 1)->getValue());
            if ($header !== '') {
                $this->columnMap[$header] = $col;
            }
        }
    }

    /** 依欄名讀取；欄位不存在回 null */
    private function cell(int $row, string $columnName)
    {
        $index = $this->columnMap[$columnName] ?? null;
        if ($index === null) {
            return null;
        }
        return $this->sheet->getCellByColumnAndRow($index, $row)->getValue();
    }

    /** volume 依類型切換：期刊讀 volume、其餘讀 book_volume */
    private function volume(int $row, int $type)
    {
        return (
            $type === Reference::TYPE_JOURNAL
                ? $this->cell($row, 'volume')
                : $this->cell($row, 'book_volume')
        ) ?? '';
    }

    /** 唯讀：逐列收集全部錯誤，回傳錯誤列數 */
    public function validate(): int
    {
        $this->buildColumnMap();

        // 先蒐集全部作者名，一次撈回作者 map（save 階段重用）
        $authorStringMap = [];
        for ($row = 2; $row <= $this->sheet->getHighestRow(); $row++) {
            $authorNamesString = $this->cell($row, 'authors');
            foreach (explode('|', $authorNamesString) as $name) {
                $authorStringMap[$name] = true;
            }
        }

        $this->authors = Person::select('id', 'original_full_name', 'last_name')
            ->whereIn('original_full_name', array_keys($authorStringMap))
            ->get()
            ->keyBy('original_full_name');

        $this->uniqueReference = [];

        $highest = $this->sheet->getHighestRow();
        $done = 0;
        for ($row = 2; $row <= $highest; $row++) {
            try {
                $this->validateRow($row);
            } catch (ImportRowException $e) {
                // 該列第一個錯誤已記錄
            }
            $done++;
            if ($done % 100 === 0) {
                $this->reportProgress('validating', $done);
            }
        }
        $this->reportProgress('validating', $done);

        return count($this->errorRows);
    }

    private function validateRow(int $row)
    {
        $typeString = $this->cell($row, 'type');
        $authorNamesString = $this->cell($row, 'authors');
        $publishYear = $this->cell($row, 'publish_year');
        $articleTitle = $this->cell($row, 'article_title') ?? '';
        $bookTitle = $this->cell($row, 'book_title');
        $languageString = $this->cell($row, 'language') ?? '';
        $pageRange = $this->cell($row, 'page_range') ?? '';

        $type = null;
        if (!$typeString) {
            $this->addError($row, '文獻類型 必填');
        } elseif (!isset($this->typeMap[$typeString])) {
            $this->addError($row, '文獻類型 格式錯誤');
        } else {
            $type = (int) $this->typeMap[$typeString];
        }

        if (!$publishYear) {
            $this->addError($row, '發表年份 必填');
        }

        // if ($pageRange !== '' && !str_contains($pageRange, '–')) {
        //     $this->addError($row, '頁碼範圍 格式錯誤');
        // }

        if ($languageString !== '' && !isset($this->languageMapping[$languageString])) {
            $this->addError($row, "語言 格式錯誤：{$languageString}");
        }

        // 作者存在性
        $authorNames = explode('|', $authorNamesString);
        $authorsAllExist = true;
        foreach ($authorNames as $name) {
            if (!isset($this->authors[$name])) {
                $this->addError($row, "{$name} 人名不存在");
                $authorsAllExist = false;
            }
        }

        // 去重需 type、作者、發表年份皆解析成功，否則略過（錯誤已記錄）
        if ($type === null || !$authorsAllExist || !$publishYear) {
            return;
        }

        $authorIds = $this->authors->whereIn('original_full_name', $authorNames)
            ->values()
            ->map(function ($author) {
                return $author->id;
            })
            ->toArray();

        $volume = $this->volume($row, $type);
        $edition = $this->cell($row, 'edition') ?? '';
        $chapter = $this->cell($row, 'chapter') ?? '';

        $title = ReferenceService::generateTitle($type, $articleTitle, $bookTitle, $edition, $volume, $chapter);

        // (1) 檔案內去重（比對欄位與資料庫判重一致：title/year/authors/book_title/volume/page_range）
        $key = "{$title}|{$publishYear}|{$authorNamesString}|{$bookTitle}|{$volume}|{$pageRange}";
        if (isset($this->uniqueReference[$key])) {
            $this->addError($row, "資料重複：與檔案內第 {$this->uniqueReference[$key]} 筆重複");
        } else {
            $this->uniqueReference[$key] = $row;
        }

        // 解析 book_id（書籍尚未建立時為 null，hasReferenceExist 對空值不比對）
        $bookId = null;
        if (!empty($bookTitle)) {
            $bookId = Book::where('title', $bookTitle)->value('id');
        }

        $refService = new ReferenceService(new Reference());

        // (2) 資料庫去重（已發表）
        $existReferences = $refService->hasReferenceExist(
            $title, $publishYear, $authorIds, true, $bookId, $volume, $pageRange, true
        );
        if ($existReferences) {
            $this->addError($row, "資料重複：與資料庫 #{$existReferences->first()->id}");
        }

        // (3) 資料庫去重（草稿，is_publish = false）
        $existDraftReferences = $refService->hasReferenceExist(
            $title, $publishYear, $authorIds, false, $bookId, $volume, $pageRange, true
        );
        if ($existDraftReferences) {
            $this->addError($row, "資料重複：資料庫已存在草稿 #{$existDraftReferences->first()->id}");
        }

    }

    /** 分批 commit（每 200 筆），回傳成功筆數 */
    public function save(): int
    {
        $this->buildColumnMap();

        $highest = $this->sheet->getHighestRow();
        $batchSize = 200;
        $count = 0;
        $minReferenceId = 0;

        for ($start = 2; $start <= $highest; $start += $batchSize) {
            $end = min($start + $batchSize - 1, $highest);

            DB::beginTransaction();
            try {
                for ($row = $start; $row <= $end; $row++) {
                    $reference = $this->saveReference($row);
                    if ($minReferenceId === 0) {
                        $minReferenceId = $reference->id;
                    }
                    $count++;
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw new \Exception($e->getMessage());
            }

            // commit 後回報，輪詢連線讀得到
            $this->reportProgress('saving', $count);
        }

        // 全部 commit 後才呼叫外部 API
        if ($minReferenceId > 0) {
            $referenceUpdateAPI = env('TAICOL_API_ROOT') . '/update/reference?min_reference_id=' . $minReferenceId;
            @file_get_contents($referenceUpdateAPI);
        }

        return $count;
    }

    public function totalRows(): int
    {
        return max(0, $this->sheet->getHighestRow() - 1);
    }

    private function addError(int $row, string $message): void
    {
        $key = $row - 1;
        if (!isset($this->errorRows[$key])) {
            $this->errorRows[$key] = ['messages' => []];
        }
        $this->errorRows[$key]['messages'][] = $message;
        $this->errorRows[$key]['message'] = implode('；', $this->errorRows[$key]['messages']);
    }

    private function hasRowError(int $row): bool
    {
        return isset($this->errorRows[$row - 1]);
    }

    private function saveReference(int $row)
    {
        $typeString = $this->cell($row, 'type');
        $type = (int) $this->typeMap[$typeString];
        $publishYear = $this->cell($row, 'publish_year');
        $articleTitle = $this->cell($row, 'article_title') ?? '';
        $bookTitle = $this->cell($row, 'book_title');
        $bookAbbreviation = $this->cell($row, 'book_abbreviation');
        $volume = $this->volume($row, $type);
        $issue = $this->cell($row, 'issue') ?? '';
        $edition = $this->cell($row, 'edition') ?? '';
        $pageRange = $this->cell($row, 'page_range') ?? '';
        $chapter = $this->cell($row, 'chapter') ?? '';

        $articleNumber = $this->cell($row, 'article_number') ?? '';
        $doi = $this->cell($row, 'doi');
        $url = $this->cell($row, 'url');
        $languageString = $this->cell($row, 'language');
        $copyright = $this->cell($row, 'copyright');
        $note = $this->cell($row, 'note');

        $authorNamesString = $this->cell($row, 'authors');
        $authorNames = explode('|', $authorNamesString);
        $authors = $this->authors->whereIn('original_full_name', $authorNames)
            ->sortBy(function ($model) use ($authorNames) {
                return array_search($model->original_full_name, $authorNames);
            })
            ->values();
        $authorsLast = $authors->pluck('last_name')->toArray();

        $title = ReferenceService::generateTitle($type, $articleTitle, $bookTitle, $edition, $volume, $chapter);
        $subtitle = ReferenceService::generateSubtitle(
            $type,
            $publishYear,
            $authorsLast,
            $bookAbbreviation,
            $edition,
            $issue,
            $volume,
            $chapter,
            $pageRange,
            $articleNumber,
        );

        $properties = [
            'article_title' => $articleTitle ?? '',
            'book_title' => $bookTitle ?? '',
            'book_title_abbreviation' => $bookAbbreviation ?? '',
            'volume' => $volume,
            'issue' => $issue ?? '',
            'edition' => $edition ?? '',
            'pages_range' => $pageRange ?? '',
            'doi' => $doi ?? '',
            'article_number' => $articleNumber ?? '',
            'chapter' => $chapter ?? '',
            'copyright' => $copyright ?? '',
            'url' => $url,
        ];

        $reference = new Reference();
        $service = new ReferenceService($reference);

        $service->create([
            'type' => $type,
            'title' => $title,
            'subtitle' => $subtitle,
            'publish_year' => $publishYear,
            'language' => $languageString ? $this->languageMapping[$languageString] : '',
            'properties' => $properties,
            'note' => $note,
        ]);

        $reference->saveAuthors($authors->map(function ($authors, $order) {
            return [
                'person_id' => $authors->id,
                'order' => $order
            ];
        }));

        $service->saveBook($bookTitle, $bookAbbreviation ?? '', true);

        $logService = new LogService();
        $logService->writeImportLog(LogType::REFERENCE, $reference->id);

        return $reference;
    }

    public function getErrorRows()
    {
        return [
            'repeat_rows' => $this->repeatRows,
            'duplicate_rows' => $this->duplicateRows,
            'error_rows' => $this->errorRows,
            'warning_rows' => $this->warningRows,
            'valid_rows' => $this->validRows,
        ];
    }
}