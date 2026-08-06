<?php


namespace App\Http\Services;


use App\Person;
use App\Rank;
use App\TaxonName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Exceptions\ImportRowException;
use Illuminate\Support\Facades\Log;

class TaxonNameImportService
{
    private $duplicateRows = []; // 與資料庫比對 unique key 重複
    private $repeatRows = []; // 檔案內部重複
    private $errorRows = [];
    private $validRows = [];
    private $warningRows = []; //

    private const LOOKUP_CHUNK = 500;
    private const REQUIRED_COLUMNS = ['nomenclature', 'rank', 'latin_name'];

    /** @var callable|null  function(string $phase, int $done) */
    public $onProgress = null;

    private function reportProgress(string $phase, int $done): void
    {
        if (is_callable($this->onProgress)) {
            ($this->onProgress)($phase, $done);
        }
    }

    // 作者欄位：field => [名稱欄名, id 欄名]，id 欄有值時優先
    private const AUTHOR_COLUMNS = [
        'origin_author'   => ['original_name_author',   'original_name_author_id'],
        'origin_exauthor' => ['original_name_exauthor', 'original_name_exauthor_id'],
        'name_author'     => ['name_authors',           'name_authors_id'],
        'name_exauthor'   => ['name_ex_authors',        'name_ex_authors_id'],
    ];

    private array $rows = [];
    private array $columnMap = [];      // 欄名 => 數字索引
    private Collection $personIdMap;    // key: person id
    private Collection $personNameMap;  // key: original_full_name => Collection<Person>
    private Collection $kingdomMap;
    private Collection $speciesMap;     // key: "latin_genus latin_s1" => TaxonName
    private array $originalTaxonNameCache = []; // key: 列號 => TaxonName|null（驗證階段解析、寫入階段重用）
    private Collection $ranks;
    private Worksheet $sheet;

    private $nomenclatureMapping = [
        'ICZN' => 1,
        'ICN' => 2,
        'ICNP' => 3,
        'ICVCN' => 4,
    ];

    public function __construct($sheet)
    {
        $this->sheet = $sheet;
    }

    public function validate(): int
    {
        $this->ranks = Rank::all()->keyBy('key');
        $this->rows = $this->sheet->toArray(null, true, false, false);

        $this->buildColumnMap();
        $this->assertRequiredColumns();
        $this->prefetchMaps();
        $this->validateSheetRows();

        return count($this->errorRows);
    }

    public function save(): int
    {
        $count = 0;
        $min_taxon_name_id = 0;
        $highest = $this->sheet->getHighestRow();
        $batchSize = 200;

        for ($start = 2; $start <= $highest; $start += $batchSize) {
            $end = min($start + $batchSize - 1, $highest);

            DB::beginTransaction();
            try {
                for ($row = $start; $row <= $end; $row++) {
                    $taxonName = $this->saveTaxonName($row);
                    (new LogService())->writeImportLog(LogType::TAXON_NAME, $taxonName->id);
                    if ($min_taxon_name_id === 0) {
                        $min_taxon_name_id = $taxonName->id;
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

        $nameUpdateAPI = env('TAICOL_API_ROOT') . '/update/name?min_taxon_name_id=' . $min_taxon_name_id;
        @file_get_contents($nameUpdateAPI);

        return $count;
    }

    public function totalRows(): int
    {
        return max(0, $this->sheet->getHighestRow() - 1);
    }

    private function buildColumnMap()
    {
        $header = $this->rows[0] ?? [];   // 數字索引下，索引 0 為表頭列
        foreach ($header as $index => $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                $this->columnMap[$name] = $index;
            }
        }
    }

    private function assertRequiredColumns()
    {
        $missing = [];
        foreach (self::REQUIRED_COLUMNS as $col) {
            if (!isset($this->columnMap[$col])) {
                $missing[] = $col;
            }
        }
        if ($missing) {
            throw new \Exception('匯入檔缺少必要欄位（請檢查第一列表頭）：' . implode('、', $missing));
        }
    }

    // $row 為 Excel 列號（1 起算，第 2 列為第一筆資料）；陣列索引 = $row - 1
    private function cell(int $row, string $columnName)
    {
        $index = $this->columnMap[$columnName] ?? null;
        if ($index === null) {
            return null; // 該欄不存在（如舊檔無 id 欄）→ 視為空，交由 fallback 處理
        }
        return $this->rows[$row - 1][$index] ?? null;
    }

    private function prefetchMaps()
    {
        $authorIds = [];
        $authorNames = [];
        $kingdomNames = [];
        $speciesNames = [];

        for ($row = 2; $row <= $this->sheet->getHighestRow(); $row++) {
            foreach (self::AUTHOR_COLUMNS as [$nameCol, $idCol]) {
                $idString = trim((string) $this->cell($row, $idCol));
                if ($idString !== '') {
                    foreach (explode('|', $idString) as $id) {
                        $authorIds[] = (int) trim($id);
                    }
                } else {
                    $nameString = trim((string) $this->cell($row, $nameCol));
                    if ($nameString !== '') {
                        foreach (explode('|', $nameString) as $n) {
                            $authorNames[] = $n;
                        }
                    }
                }
            }

            $kingdom = trim((string) $this->cell($row, 'kingdom_name'));
            if ($kingdom !== '') {
                $kingdomNames[] = $kingdom;
            }

            // 與 saveTaxonName 的 "$latinGenus $latinS1" 組法一致
            $genus = trim(str_replace("\x00", "", (string) $this->cell($row, 'latin_genus')));
            $s1 = trim(str_replace("\x00", "", (string) $this->cell($row, 'latin_s1')));
            $speciesName = "$genus $s1";
            if (trim($speciesName) !== '') {
                $speciesNames[] = $speciesName;
            }
        }

        $authorIds = array_values(array_unique($authorIds));
        $authorNames = array_values(array_unique($authorNames));
        $kingdomNames = array_values(array_unique($kingdomNames));
        $speciesNames = array_values(array_unique($speciesNames));

        // 依 id 建 map
        $this->personIdMap = collect();
        collect($authorIds)->chunk(self::LOOKUP_CHUNK)->each(function ($chunk) {
            Person::whereIn('id', $chunk->values()->all())
                ->get()->each(fn ($p) => $this->personIdMap->put($p->id, $p));
        });

        // 依名稱分組建 map（保留同名多筆以偵測歧義）
        $this->personNameMap = collect();
        collect($authorNames)->chunk(self::LOOKUP_CHUNK)->each(function ($chunk) {
            Person::whereIn('original_full_name', $chunk->values()->all())
                ->get()->groupBy('original_full_name')
                ->each(fn ($group, $name) => $this->personNameMap->put($name, $group));
        });

        // kingdom map（同名取第一筆，對應原 ->first()）
        $this->kingdomMap = collect();
        collect($kingdomNames)->chunk(self::LOOKUP_CHUNK)->each(function ($chunk) {
            TaxonName::whereIn('name', $chunk->values()->all())
                ->where('rank_id', 3)->get()
                ->each(function ($t) {
                    if (!$this->kingdomMap->has($t->name)) {
                        $this->kingdomMap->put($t->name, $t);
                    }
                });
        });

        // species map（依 name 取第一筆，對應原 saveTaxonName 的 ->first()）
        $this->speciesMap = collect();
        collect($speciesNames)->chunk(self::LOOKUP_CHUNK)->each(function ($chunk) {
            TaxonName::whereIn('name', $chunk->values()->all())->get()
                ->each(function ($t) {
                    if (!$this->speciesMap->has($t->name)) {
                        $this->speciesMap->put($t->name, $t);
                    }
                });
        });
    }

    private function validateSheetRows()
    {
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
    }

    private function validateRow(int $row)
    {
        $service = new TaxonNameService(new TaxonName());

        $nomenclature = $this->nomenclatureMapping[$this->cell($row, 'nomenclature')] ?? null;
        $rankString = $this->cell($row, 'rank');
        $name = trim(str_replace("\x00", "", (string) $this->cell($row, 'latin_name')));
        $referenceId = (int) $this->cell($row, 'reference_id') ?: null;

        if (!$nomenclature) {
            $this->addError($row, 'nomenclature 錯誤');
        }
        if (!$rankString) {
            $this->addError($row, 'rank 未填寫');
        }
        if (!$name) {
            $this->addError($row, 'name 未填寫');
        }
        $rankId = ($rankString && isset($this->ranks[strtolower($rankString)]))
            ? $this->ranks[strtolower($rankString)]->id
            : null;
        if ($rankString && $rankId === null) {
            $this->addError($row, 'rank 錯誤');
        }

        // 作者解析（內部會累積錯誤）
        $authors = $this->resolveAuthorsByField($row, 'name_author');
        $this->resolveAuthorsByField($row, 'name_exauthor');

        // 學名重複檢查需 nomenclature / name / rank / 作者皆無誤，否則略過
        if ($nomenclature && $name && $rankId !== null && !$this->hasRowError($row)) {
            $authorIds = $authors->pluck('id')->toArray();
            if ($service->hasTaxonNameExist($nomenclature, $rankId, $name, $referenceId, $authorIds, true)) {
                $this->addError($row, '學名重複');
            } else if ($service->hasTaxonNameExist($nomenclature, $rankId, $name, $referenceId, $authorIds, false)) {
                $this->addError($row, '學名已存在於草稿');
            }
        }

        $originNameString = trim((string) $this->cell($row, 'original_name'));
        if ($originNameString !== '') {
            $originalTaxonName = $this->findOriginalTaxonName($originNameString, $row);
            if (!$originalTaxonName) {
                $this->addError($row, "找不到 $originNameString");
            } else {
                $this->originalTaxonNameCache[$row] = $originalTaxonName;
            }
        }

        $kingdomNameString = trim((string) $this->cell($row, 'kingdom_name'));
        if ($kingdomNameString !== '' && !$this->findKingdomTaxonName($kingdomNameString, $row)) {
            $this->addError($row, "找不到 $kingdomNameString");
        }
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

    private function saveTaxonName(int $row)
    {
        $taxonName = new TaxonName();
        $service = new TaxonNameService($taxonName);

        $nomenclature = $this->nomenclatureMapping[$this->cell($row, 'nomenclature')];
        $rankString = $this->cell($row, 'rank');
        $name = trim(str_replace("\x00", "", (string) $this->cell($row, 'latin_name')));
        $latinGenus = trim(str_replace("\x00", "", (string) $this->cell($row, 'latin_genus')));
        $latinS1 = trim(str_replace("\x00", "", (string) $this->cell($row, 'latin_s1')));
        $s2Rank = $this->cell($row, 's2_rank');
        $latinS2 = trim(str_replace("\x00", "", (string) $this->cell($row, 'latin_s2')));

        $originNameString = trim((string) $this->cell($row, 'original_name'));
        $formattedAuthorsString = $this->cell($row, 'formatted_authors');

        $referenceName = $this->cell($row, 'reference_name');
        $referenceId = (int) $this->cell($row, 'reference_id') ?: null;
        $page = $this->cell($row, 'page');
        $citeFigure = $this->cell($row, 'cite_figure');
        $publishYear = $this->cell($row, 'year');
        $note = $this->cell($row, 'note');
        $kingdomNameString = trim((string) $this->cell($row, 'kingdom_name'));

        $originalTaxonName = $this->originalTaxonNameCache[$row] ?? null;
        $kingdomTaxonName = $kingdomNameString !== '' ? $this->findKingdomTaxonName($kingdomNameString, $row) : null;

        $species = $this->speciesMap->get("$latinGenus $latinS1");

        $authors = $this->resolveAuthorsByField($row, 'name_author');
        $exAuthors = $this->resolveAuthorsByField($row, 'name_exauthor');

        $taxonName = $service->saveAll([
            'nomenclature_id' => $nomenclature,
            'rank_id' => $this->ranks[strtolower($rankString)]->id,
            'name' => $name,
            'formatted_authors' => $formattedAuthorsString,
            'original_taxon_name_id' => $originalTaxonName ? $originalTaxonName->id : null,
            'kingdom_taxon_name_id' => $kingdomTaxonName ? $kingdomTaxonName->id : null,
            'type_specimens' => [],
            'publish_year' => $publishYear,
            'note' => $note,
            'is_hybrid' => false,
            'latin_genus' => $latinGenus,
            'latin_name' => $name,
            'latin_s1' => $latinS1,
            'reference_name' => $referenceName,
            'species_id' => $species ? $species->id : null,
            'species_layers' => $s2Rank ? [
                [
                    'rank_abbreviation' => $s2Rank,
                    'latin_name' => $latinS2,
                ]
            ] : [],
            'type_name' => '',
            'usage' => $referenceId ? [
                'reference_id' => $referenceId,
                'figure' => $citeFigure,
                'name_in_reference' => '',
                'show_page' => $page,
            ] : [],
            'is_approved_list' => false,
            'initial_year' => '',
            'genome_composition' => '',
            'host' => '',
            'from_import' => true,
        ],
            $authors->pluck('id')->toArray(),
            $exAuthors->pluck('id')->toArray(),
            $referenceId ? [
                'reference_id' => $referenceId,
                'figure' => $citeFigure,
                'name_in_reference' => '',
                'show_page' => $page,
            ] : []
        );

        if ($nomenclature != 4) {
            $service->getAndUpdateObjectGroups();
        }
        return $taxonName;
    }

    private function findOriginalTaxonName(string $originNameString, int $row)
    {
        $query = TaxonName::where('name', $originNameString);

        $authors = $this->resolveAuthorsByField($row, 'origin_author');
        if ($authors->isNotEmpty()) {
            $query->whereHas('authors', function ($q) use ($authors) {
                $q->whereIn('persons.id', $authors->pluck('id')->toArray());
            }, '=', $authors->count());
        }

        $exAuthors = $this->resolveAuthorsByField($row, 'origin_exauthor');
        if ($exAuthors->isNotEmpty()) {
            $query->whereHas('exauthors', function ($q) use ($exAuthors) {
                $q->whereIn('persons.id', $exAuthors->pluck('id')->toArray());
            }, '=', $exAuthors->count());
        }

        return $query->first();
    }

    private function findKingdomTaxonName(string $kingdomNameString, int $row)
    {
        return $this->kingdomMap->get($kingdomNameString);
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

    private function resolveAuthorsByField(int $row, string $field)
    {
        [$nameCol, $idCol] = self::AUTHOR_COLUMNS[$field];
        $idString = trim((string) $this->cell($row, $idCol));
        $nameString = trim((string) $this->cell($row, $nameCol));

        if ($idString !== '') {
            return $this->resolveAuthorsById($row, $idString);
        }
        if ($nameString !== '') {
            return $this->resolveAuthorsByName($row, $nameString);
        }
        return collect([]);
    }

    private function resolveAuthorsById(int $row, string $idString)
    {
        $authors = collect();
        foreach (explode('|', $idString) as $idStr) {
            $id = (int) trim($idStr);
            $person = $this->personIdMap->get($id);
            if (!$person) {
                $this->addError($row, "作者 id「{$id}」不存在");
                continue;
            }
            $authors->push($person);
        }
        return $authors;
    }

    private function resolveAuthorsByName(int $row, string $nameString)
    {
        $names = explode('|', $nameString);

        if (count($names) !== count(array_unique($names))) {
            $this->addError($row, "作者名重複填寫：「{$nameString}」");
        }

        $authors = collect();
        foreach ($names as $name) {
            $group = $this->personNameMap->get($name);

            if (!$group || $group->isEmpty()) {
                $this->addError($row, "找不到作者「{$name}」");
                continue;
            }
            if ($group->count() > 1) {
                $ids = $group->pluck('id')->implode(', ');
                $this->addError($row, "作者「{$name}」有多筆同名（id: {$ids}），請改用對應的 id 欄位指定");
                continue;
            }

            $authors->push($group->first());
        }
        return $authors;
    }
}
