<?php

namespace App\Http\Services;

use App\Book;
use App\FavoriteMineItem;
use App\Http\Entities\ReferenceOtherPropertiesFactory;
use App\Reference;
use App\ReferenceUsage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ReferenceService
{
    protected $reference;

    public function __construct(Model $reference)
    {
        $this->reference = $reference;
    }

    public static function generateTitle(int $type, string $articleTitle, string $bookTitle = '', string $edition = '', string $volume = '', string $chapter = ''): string
    {
        $chapter = $chapter ? "ch. $chapter" : '';
        $volume = $volume ? "vol. $volume" : $chapter;

        $edition = $edition ? "$edition ed." : '';

        $elements = array_filter([
            $bookTitle,
            $edition,
            $volume,
        ]);

        switch ($type) {
            case Reference::TYPE_JOURNAL:
                return $articleTitle;
            case Reference::TYPE_BOOK_ARTICLE:
                return $articleTitle ?: implode(', ', $elements);
            case Reference::TYPE_BOOK:
                return implode(', ', $elements);
            default:
                return '';
        }
    }

    /**
     * @param int $type
     * @param string $publishYear
     * @param array $authorsLast #should be ordered author list
     * @param string $bookTitleAbbreviation
     * @param string $edition
     * @param string $issue
     * @param string $volume
     * @param string $chapter
     * @param string $pageRange
     * @return string
     * @throws \Exception
     */
    public static function generateSubtitle(int $type, string $publishYear, array $authorsLast, string $bookTitleAbbreviation = '', string $edition = '', string $issue = '', string $volume = '', string $chapter = '', string $pageRange = '', string $articleNumber = ''): string
    {
        $lastNames = count($authorsLast) >= 3 ? "$authorsLast[0] et al." : implode(' & ', $authorsLast);

        switch ($type) {
            case Reference::TYPE_JOURNAL:
                $issue = $issue ? "($issue)" : '';

                // 如果有電子文章編號要優先採用
                
                $bookInfo = implode(': ', array_filter([
                    $volume . $issue,
                    $articleNumber ? $articleNumber : $pageRange
                ]));

                $subTitle = implode(' ', array_filter([
                    $bookTitleAbbreviation,
                    $bookInfo
                ]));
                break;
            case Reference::TYPE_BOOK_ARTICLE:
                $chapter = $chapter ? "ch. $chapter" : '';
                $edition = $edition ? "$edition ed." : '';

                $subTitle = implode(', ', array_filter([
                    $bookTitleAbbreviation,
                    $edition,
                    implode(': ', [
                        $volume ?: $chapter,
                        $pageRange
                    ])
                ]));
                break;
            case Reference::TYPE_BOOK:
                $chapter = $chapter ? "ch. $chapter" : '';
                $edition = $edition ? "$edition ed." : '';

                $subTitle = implode(', ', array_filter([
                    $bookTitleAbbreviation,
                    $edition,
                    $volume ?: $chapter
                ]));
                break;
            default:
                throw new \Exception();
        }

        return implode(', ', array_filter([
            $lastNames,
            $publishYear,
            $subTitle,
        ]));
    }

    // public function hasReferenceExist($title, $publishYear, $authors, bool $isPublish, bool $returnReference = false)
    // {
    //     $existQuery = Reference::query()
    //         ->where('title', $title)
    //         ->where('publish_year', $publishYear)
    //         ->where('is_publish', $isPublish)
    //         ->whereHas('authors', function ($query) use ($authors) {
    //             $query->whereIn('persons.id', $authors);
    //         }, '=', count($authors));

    //     if ($this->reference->id) {
    //         $existQuery->where('id', '!=', $this->reference->id);
    //     }

    //     if ($returnReference) {
    //         $references = $existQuery->get();
    //         return $references->isNotEmpty() ? $references : false;
    //     }

    //     return $existQuery->count() > 0;
    // }


    public function hasReferenceExist(
        $title, 
        $publishYear, 
        $authors, 
        bool $isPublish, 
        $bookId = null,      // 新增: 書籍ID
        $volume = null,      // 新增: 卷數
        $pagesRange = null,  // 新增: 頁碼範圍
        bool $returnReference = false
    )
    {
        $existQuery = Reference::query()
            ->where('title', $title)
            ->where('publish_year', $publishYear)
            ->where('is_publish', $isPublish)
            ->whereHas('authors', function ($query) use ($authors) {
                $query->whereIn('persons.id', $authors);
            }, '=', count($authors));

        // 1. 檢查 book_id (若傳入值不為空才檢查)
        if (!empty($bookId)) {
            $existQuery->where('book_id', $bookId);
        }

        // 2. 檢查 volume (假設位於 properties JSON 欄位中)
        // 注意：如果您的 volume 是獨立欄位，請改為 where('volume', $volume)
        if (!empty($volume)) {
            $existQuery->where('properties->volume', $volume);
        }

        // 3. 檢查 pages_range (假設位於 properties JSON 欄位中)
        if (!empty($pagesRange)) {
            $existQuery->where('properties->pages_range', $pagesRange);
        }

        // 排除自己 (編輯模式)
        if ($this->reference->id) {
            $existQuery->where('id', '!=', $this->reference->id);
        }

        if ($returnReference) {
            $references = $existQuery->get();
            return $references->isNotEmpty() ? $references : false;
        }

        return $existQuery->count() > 0;
    }

    public function hasReferenceWithFile($title, $publishYear, $authors, bool $isPublish): array
    {
        $existQuery = Reference::query()
            ->where('title', $title)
            ->where('publish_year', $publishYear)
            ->where('is_publish', $isPublish)
            ->whereHas('authors', function ($query) use ($authors) {
                $query->whereIn('persons.id', $authors);
            }, '=', count($authors));

        if ($this->reference->id) {
            $existQuery->where('id', '!=', $this->reference->id);
        }

        $references = $existQuery->get();

        if ($references->isEmpty()) {
            return ['exists' => false];
        }

        // 檢查是否有檔案不為空的文獻
        foreach ($references as $reference) {
            $properties = is_string($reference->properties) 
                ? json_decode($reference->properties, true) 
                : $reference->properties;
                
            $file = $properties['file'] ?? null;
            
            if (!empty($file) && $file !== '' && $file !== null) {
                return [
                    'exists' => true,
                    'reference' => [
                        'id' => $reference->id,
                        'title' => $reference->title
                    ]
                ];
            }
        }

        return ['exists' => false];
    }
    public function hasReferenceWithUsage($title, $publishYear, $authors, bool $isPublish): array
    {
        $existQuery = Reference::query()
            ->where('title', $title)
            ->where('publish_year', $publishYear)
            ->where('is_publish', $isPublish)
            ->whereHas('authors', function ($query) use ($authors) {
                $query->whereIn('persons.id', $authors);
            }, '=', count($authors));

        if ($this->reference->id) {
            $existQuery->where('id', '!=', $this->reference->id);
        }

        $references = $existQuery->get();

        if ($references->isEmpty()) {
            return ['exists' => false];
        }

        $referenceIds = $references->pluck('id');
        
        $hasUsage = ReferenceUsage::whereIn('reference_id', $referenceIds)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasUsage) {
            $firstReference = $references->first();
            return [
                'exists' => true,
                'reference' => [
                    'id' => $firstReference->id,
                    'title' => $firstReference->title
                    // 不需要 URL，讓前端處理
                ]
            ];
        }

        return ['exists' => false];
    }

    /**
     * 取得潛在重複的文獻清單
     *
     * @param array $data 輸入的資料 (type, title, etc.)
     * @return \Illuminate\Database\Eloquent\Collection
     */

    public function getPotentialDuplicates(array $data)
    {
        $currentId   = $this->reference->id ?? null;
        $type        = $data['type'];
        $year        = $data['publish_year'];
        $authors     = $data['authors'];
        $bookId      = $data['book_id'] ?? null;
        $volume      = $data['volume'] ?? '';
        $pagesRange  = $data['pages_range'] ?? '';
        
        // =========================================================
        // [第一階段] 精準比對 (SQL) - 給予最高分 100
        // =========================================================
        $directDuplicates = collect([]); 

        if ($bookId) {
            $query = Reference::query();
            if ($currentId) $query->where('id', '!=', $currentId);

            $query->where(function ($q) use ($bookId, $volume, $pagesRange, $year, $authors, $type) {
                
                // Rule 1: 位置重複
                $q->orWhere(function ($sub) use ($bookId, $volume, $pagesRange, $type) {
                    $sub->where('book_id', $bookId);
                    
                    // 比對 Volume (包含空值)
                    $sub->where(function ($w) use ($volume) {
                        $w->where('properties->volume', $volume);
                        if ($volume === '') $w->orWhereNull('properties->volume');
                    });
                    
                    // [關鍵] 只有 Type 1, 2 才比對頁碼，Type 3 (書籍) 忽略頁碼
                    if ($type != 3) {
                        $sub->where(function ($w) use ($pagesRange) {
                            $w->where('properties->pages_range', $pagesRange);
                            if ($pagesRange === '') $w->orWhereNull('properties->pages_range');
                        });
                    }
                });

                // Rule 2: 著作重複 (同書、同年、同作者群)
                $q->orWhere(function ($sub) use ($bookId, $year, $authors) {
                    $sub->where('book_id', $bookId)
                        ->where('publish_year', $year)
                        ->whereHas('authors', function ($q) use ($authors) {
                            $q->whereIn('persons.id', $authors);
                        }, '=', count($authors));
                });
            });

            // 為 SQL 抓到的結果加上滿分
            $directDuplicates = $query->get()->map(function($item) {
                $item->match_score = 100; // 精準命中
                $item->match_reason = 'Exact Match (SQL)';
                return $item;
            });
        }

        // =========================================================
        // [第二階段] 模糊比對 (PHP) - 計算分數 (0~99)
        // 專注處理 Type 1 (期刊文章) 的標題錯字與中英並列題名
        // =========================================================
        $fuzzyDuplicates = collect([]);
        $shouldCheckFuzzy = false;
        $inputTitle = '';

        // 依照您的需求，模糊比對目前只針對 Type 1 (期刊文章) 執行
        if ($type == 1 && !empty($data['article_title'])) {
            $shouldCheckFuzzy = true;
            $inputTitle = $data['article_title'];
        }

        if ($shouldCheckFuzzy) {

            // 1. 正規化 (清理雜訊)
            $cleanInput = $this->normalizeString($inputTitle);
            $byteLenInput = strlen($cleanInput); // Byte 長度 (配合 similar_text)

            // 防呆：清理雜訊後，至少要有 5 Bytes (約2個中文字或5個英文字) 才比對
            if ($byteLenInput >= 5) {

                // 2. 撈取候選名單 (只撈同類型，不限年份以抓出售錯年份的資料)
                $query = Reference::query()->where('type', $type);
                if ($currentId) $query->where('id', '!=', $currentId);
                
                $candidates = $query->get();

                // 3. 核心演算法
                $fuzzyDuplicates = $candidates->map(function ($item) use ($cleanInput, $byteLenInput) {
                    
                    $dbTitleRaw = ($item->type == 1) 
                        ? ($item->properties['articleTitle'] ?? $item->title ?? '') 
                        : ($item->title ?? '');

                    $cleanDb = $this->normalizeString($dbTitleRaw);
                    $byteLenDb = strlen($cleanDb); 
                    
                    if ($byteLenDb < 5) return null;

                    $maxLen = max($byteLenInput, $byteLenDb);
                    $minLen = min($byteLenInput, $byteLenDb);

                    if ($minLen == 0) return null;

                    // A. 取得相同位元組數 (similar_text 回傳值)
                    $matchingBytes = similar_text($cleanInput, $cleanDb); 

                    // B. 計算兩種包含率
                    $overlapRatioMin = ($matchingBytes / $minLen) * 100; // 針對較短字串 (判斷並列)
                    $overlapRatioMax = ($matchingBytes / $maxLen) * 100; // 針對較長字串 (判斷錯字)

                    // C. 計算連續性 (LCS，使用 Char 長度)
                    $lcsLengthChars = $this->getMultibyteLCSLength($cleanInput, $cleanDb);
                    $charLenInput = mb_strlen($cleanInput);
                    $charLenDb = mb_strlen($cleanDb);
                    $minCharLen = min($charLenInput, $charLenDb);

                    if ($minCharLen == 0) return null;
                    $lcsRatio = ($lcsLengthChars / $minCharLen) * 100;

                    $passed = false;
                    $score = 0;

                    // =========================================================
                    // 雙重假設檢定 (完美分離並列題名與錯字)
                    // =========================================================

                    // 假設 1：這是「並列題名 / 子字串包含」嗎？
                    // 條件：短字串的包含率極高 (>= 90%)
                    if ($overlapRatioMin >= 90) {
                        
                        // 防禦機制：如果短標題非常短 (< 10 字元，例如 "hyphar" 或 "黑熊")
                        // 必須「100% 完整連續」出現，防止短短的拉丁字根誤判成包含關係。
                        if ($minCharLen < 10) {
                            if ($lcsRatio == 100) {
                                $passed = true;
                                $score = 100;
                            }
                        } else {
                            // 若標題夠長，容許稍微斷開或打錯字
                            if ($lcsRatio >= 80) {
                                $passed = true;
                                $score = ($overlapRatioMin * 0.4) + ($lcsRatio * 0.6);
                            }
                        }
                    }

                    // 假設 2：這是「錯字 / 漏字」嗎？ (如果假設 1 失敗)
                    // 條件：互相包含率都很高 (看 Max)，並允許 LCS 稍微斷開
                    if (!$passed) {
                        if ($overlapRatioMax >= 80 && $lcsRatio >= 40) {
                            $passed = true;
                            $score = ($overlapRatioMax * 0.4) + ($lcsRatio * 0.6);
                        }
                    }

                    if ($passed) {
                        $item->match_score = round($score, 1); 
                        $item->match_reason = 'Fuzzy Match';
                        return $item;
                    }

                    return null;

                })->filter(); // 移除 null
            }
        }

        // =========================================================
        // [第三階段] 合併與排序 (Sorting)
        // =========================================================
        return $directDuplicates->merge($fuzzyDuplicates)
            ->unique('id')
            ->sortByDesc('match_score') // 依照分數由高到低排序，最像的在最上面
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'publish_year' => $item->publish_year,
                    'title' => $item->title,
                    'subtitle' => $item->subtitle,
                    // 'match_score' => $item->match_score ?? 0, // 開發除錯時可打開這行
                ];
            })
            ->values();
    }

    /**
     * [國際化完美版] 清理字串：支援中、英、日、法、德、俄等多國語言
     */
    private function normalizeString($str)
    {
        // 1. 轉小寫 (mb_strtolower 支援將 É 轉為 é 等多國語言轉換)
        $str = mb_strtolower($str);

        // 2. 英文停用詞庫
        $engStopWords = [
            'of', 'the', 'and', 'from', 'in', 'on', 'with', 'to', 'for', 'an', 'a',
            'taiwan', 'formosa', 'china', 'japan',
            'species', 'genus', 'flora', 'sp', 'genera', 'fishes', 'plants', 'coleoptera', 'lepidoptera',
            'new', 'newly', 'two', 'three', 'notes', 'studies', 'study', 'description', 'descriptions', 
            'revision', 'taxonomic', 'systematic', 'science', 'journal', 'bulletin', 'vol', 
            'record', 'records', 'natural', 'review', 'museum', 'zoological', 'zootaxa', 'taiwania',
            'et', 'de', 'der', 'nov'
        ];
        
        $pattern = '/\b(' . implode('|', $engStopWords) . ')\b/u';
        $str = preg_replace($pattern, '', $str);

        // 3. 亞洲語系 (繁體、簡體、日文) 停用詞庫
        $asianStopWords = [
            // 長詞先濾 (加入簡體對應)
            '新紀錄種', '新记录种', '一新種', '一新种', '真菌學', '真菌学', '學會會刊', '学会会刊', '博物館', '博物馆', '多樣性', '多样性', '臺灣產', '台湾产', 
            '新紀錄', '新记录',
            
            // 雙字詞 (加入簡體對應)
            '臺灣', '台灣', '中華', '中华', '中國', '中国', '國立', '国立', 
            '植物', '昆蟲', '昆虫', '真菌', '貝類', '贝类', '魚類', '鱼类',
            '研究', '紀錄', '纪录', '記錄', '记录', '分類', '分类', '調查', '调查', '學會', '学会', '會刊', '会刊', '學報', '学报', '季刊', '博物', '新種', '新种',
            
            // 單字 (包含日文助詞與簡繁體單字)
            '之', '的', '及', '與', '与', '產', '产', '科', '類', '类', '種', '种', '目', '屬', '属', '物', '蟲', '虫', '學', '学', '誌', '志', '錄', '录',
            'の', 'に', 'と', 'や'
        ];
        
        $str = str_replace($asianStopWords, '', $str);

        // 4. 終極標點符號過濾器
        // \p{L} 代表「任何語言的文字」，\p{N} 代表「任何數字」
        // 這樣寫可以完美保留法文 é、德文 ö、俄文 Д、日文、中文，並將所有的空格、標點符號(無論全半形)全部刪除！
        return preg_replace('/[^\p{L}\p{N}]/u', '', $str);
    }
    
    /**
     * [LCS 計算] 支援 UTF-8 Multibyte (解決中文亂碼與連續性計算問題)
     */
    private function getMultibyteLCSLength($str1, $str2)
    {
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        if ($len1 == 0 || $len2 == 0) return 0;

        $short = ($len1 < $len2) ? $str1 : $str2;
        $long = ($len1 < $len2) ? $str2 : $str1;
        $shortLen = mb_strlen($short);

        // 快速檢查：若完整包含，直接回傳最大長度
        if (mb_strpos($long, $short) !== false) return $shortLen;

        // 滑動視窗尋找最長連續字串 (Multibyte Safe)
        for ($len = $shortLen; $len > 0; $len--) {
            for ($start = 0; $start <= $shortLen - $len; $start++) {
                $sub = mb_substr($short, $start, $len);
                if (mb_strpos($long, $sub) !== false) {
                    return $len;
                }
            }
        }
        return 0;
    }

    public function create(array $data): Model
    {
        $this->reference->type = $data['type'];
        $this->reference->title = $data['title'] ?? '';
        $this->reference->subtitle = $data['subtitle'] ?? '';
        $this->reference->publish_year = $data['publish_year'];
        $this->reference->language = $data['language'];

        $this->reference->properties = (new ReferenceOtherPropertiesFactory())
            ->createPropertiesFromType($data['type'])
            ->setProperties($data['properties'])
            ->toArray();

        $this->reference->note = $data['note'] ?? '';
        $this->reference->is_publish = $data['is_publish'] ?? true;
        $this->reference->save();

        return $this->reference;
    }

    public function saveBook(string $title, string $titleAbbreviation = '', bool $isPublish)
    {
        if ($title == '') throw new \Exception('book title require.');

        $existBook = Book::query()
            ->where('title', $title)
            ->first();

        if ($existBook) {
            // update title abbreviation
            $existBook->title_abbreviation = $titleAbbreviation;
            $existBook->is_publish = $isPublish;
            $existBook->save();

            $book = $existBook;
        } else {
            $book = new Book();
            $book->title = $title;
            $book->title_abbreviation = $titleAbbreviation;
            $book->is_publish = $isPublish;
            $book->save();
        }

        $this->reference->book()->associate($book);
        $this->reference->save();

        return $book;
    }

    public function saveToMyFavoriteItem()
    {
        $item = new FavoriteMineItem();
        $item->collectable_type = FavoriteMineItem::TYPE_REFERENCE;
        $item->collectable_id = $this->reference->id;
        $item->user_id = Auth::user()->id;
        $item->save();
    }

    public function saveCoverImage($file, $coverPath)
    {
        if ($file) {
            $extension = explode('/', mime_content_type($file))[1];
            $path = sprintf(
                'references/%d-%d.%s',
                $this->reference->id,
                Carbon::now()->unix(),
                $extension
            );

            Storage::disk('images')->put($path, file_get_contents($file));
            $this->reference->cover_path = $path;
            $this->reference->save();
        } else if (!$file && !$coverPath) {
            $this->reference->cover_path = '';
            $this->reference->save();
        }
    }
}