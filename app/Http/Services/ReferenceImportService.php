<?php


namespace App\Http\Services;


use App\Person;
use App\Reference;
use Illuminate\Support\Facades\DB;

class ReferenceImportService
{
    private $validRows = [];
    private $allKey = [];

    private $duplicateRows = []; // 與資料庫比對 unique key 重複
    private $repeatRows = []; // 檔案內部重複
    private $errorRows = [];
    private $warningRows = []; //

    private $authors;

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

    public function handle(): int
    {
        $this->validateSheetRows();

        DB::beginTransaction();

        try {
            $count = 0;
            for ($row = 2; $row <= $this->sheet->getHighestRow(); $row++) {
                $this->saveReference($row);
                $count++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->throwError($row, $e->getMessage() . $e->getTraceAsString());
        }

        return $count;
    }

    private function validateSheetRows()
    {
        $sheet = $this->sheet;

        $authorStringMap = [];

        // calculate max high row
        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $typeString = $sheet->getCell('A' . $row)->getValue();
            $authorNamesString = $sheet->getCell('B' . $row)->getValue();
            $publishYear = $sheet->getCell('C' . $row)->getValue();
            $languageString = $this->sheet->getCell('P' . $row)->getValue() ?? '';
            $pageRange = $this->sheet->getCell('K' . $row)->getValue() ?? '';

            if (!$this->sheet->getCell('A' . $row)->getValue()) {
                $this->throwError($row, '文獻類型 必填');
            }

            if (!isset($this->typeMap[$typeString])) {
                $this->throwError($row, '格式錯誤');
            };

            // check publish year
            if (!$publishYear) {
                $this->throwError($row, '發表年份 必填');
            }

            // check page range
            if ($pageRange !== '' && !str_contains($pageRange, '–')) {
                $this->throwError($row, '頁碼範圍 格式錯誤');
            }

            // check language
            if ($languageString !== '' && !isset($this->languageMapping[$languageString])) {
                $this->throwError($row, "語言 格式錯誤：{$languageString}");
            }

            // collect authors
            $authorsStrings = explode('|', $authorNamesString);
            foreach ($authorsStrings as $authorsString) {
                $authorStringMap[$authorsString] = true;
            }

            $this->maxHighRows = $row;
        }

        // find authors
        $this->authors = Person::select('id', 'original_full_name', 'last_name')
            ->whereIn('original_full_name', array_keys($authorStringMap))
            ->get()
            ->keyBy('original_full_name');

        $uniqueReference = [];
        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $typeString = $this->sheet->getCell('A' . $row)->getValue();
            $type = (int) $this->typeMap[$typeString];
            $authorNamesString = $sheet->getCell('B' . $row)->getValue();
            $authorNames = explode('|', $authorNamesString);
            $publishYear = $this->sheet->getCell('C' . $row)->getValue();
            $articleTitle = $this->sheet->getCell('D' . $row)->getValue() ?? '';
            $bookTitle = $this->sheet->getCell('E' . $row)->getValue();
            $volume = (
                $type === Reference::TYPE_JOURNAL ?
                    $this->sheet->getCell('G' . $row)->getValue() : $this->sheet->getCell('I' . $row)->getValue()
                ) ?? '';
            $edition = $this->sheet->getCell('J' . $row)->getValue() ?? '';
            $chapter = $this->sheet->getCell('L' . $row)->getValue() ?? '';

            foreach ($authorsStrings as $authorsString) {
                if (!isset($this->authors[$authorsString])) {
                    $this->throwError($row, "{$authorsString} 人名不存在");
                }
            }

            $authorIds = $this->authors->whereIn('original_full_name', $authorNames)
                ->values()
                ->map(function ($authors) {
                    return $authors->id;
                })
                ->toArray();

            /**
             * check unique
             *
             * (1) check in file unique
             * (2) check database unique
             **/
            $title = ReferenceService::generateTitle($type, $articleTitle, $bookTitle, $edition, $volume, $chapter);

            $key = "{$title}{$publishYear}{$authorsString}";
            if (isset($uniqueReference[$key])) {
                $this->throwError($row, "資料重複：與第 {$uniqueReference[$key]} 筆");
            }

            $uniqueReference[$key] = $row;


            $existReference = Reference::query()
                ->where('title', $title)
                ->where('publish_year', $publishYear)
                ->whereHas('authors', function ($query) use ($authorIds) {
                    $query->whereIn('persons.id', $authorIds);
                }, '=', count($authorIds))
                ->first();

            if ($existReference) {
                $this->throwError($row, "資料重複：與資料庫 #{$existReference->id}");
            }
        }
    }

    private function throwError(int $row, string $message)
    {
        $this->errorRows[$row - 1] = ['message' => $message];
        throw new \Exception($message);
    }

    private function saveReference(int $row)
    {
        $typeString = $this->sheet->getCell('A' . $row)->getValue();
        $type = (int) $this->typeMap[$typeString];
        $publishYear = $this->sheet->getCell('C' . $row)->getValue();
        $articleTitle = $this->sheet->getCell('D' . $row)->getValue() ?? '';
        $bookTitle = $this->sheet->getCell('E' . $row)->getValue();
        $bookAbbreviation = $this->sheet->getCell('F' . $row)->getValue();
        $volume = (
            $type === Reference::TYPE_JOURNAL ?
                $this->sheet->getCell('G' . $row)->getValue() : $this->sheet->getCell('I' . $row)->getValue()
            ) ?? '';
        $issue = $this->sheet->getCell('H' . $row)->getValue() ?? '';
        $edition = $this->sheet->getCell('J' . $row)->getValue() ?? '';
        $pageRange = $this->sheet->getCell('K' . $row)->getValue() ?? '';
        $chapter = $this->sheet->getCell('L' . $row)->getValue() ?? '';

        $articleNumber = $this->sheet->getCell('M' . $row)->getValue();
        $doi = $this->sheet->getCell('N' . $row)->getValue();
        $url = $this->sheet->getCell('O' . $row)->getValue();
        $languageString = $this->sheet->getCell('P' . $row)->getValue();
        $copyright = $this->sheet->getCell('Q' . $row)->getValue();
        $note = $this->sheet->getCell('R' . $row)->getValue();

        $authorNamesString = $this->sheet->getCell('B' . $row)->getValue();
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

        $authorIds = $authors->pluck('id')->toArray();
        if ($service->checkExistWithNewMeta($title, $publishYear, $authorIds)) {
            throw new \Exception('資料重複');
        }

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

        $service->saveBook($bookTitle, $bookAbbreviation ?? '');

        $logService = new LogService();
        $logService->writeImportLog(LogType::REFERENCE, $reference->id);
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
