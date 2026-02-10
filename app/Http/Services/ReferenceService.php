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

            $query->where(function ($q) use ($bookId, $volume, $pagesRange, $year, $authors) {
                // Rule 1: 位置重複
                $q->orWhere(function ($sub) use ($bookId, $volume, $pagesRange) {
                    $sub->where('book_id', $bookId);
                    $sub->where(function ($w) use ($volume) {
                        $w->where('properties->volume', $volume);
                        if ($volume === '') $w->orWhereNull('properties->volume');
                    });
                    $sub->where(function ($w) use ($pagesRange) {
                        $w->where('properties->pages_range', $pagesRange);
                        if ($pagesRange === '') $w->orWhereNull('properties->pages_range');
                    });
                });

                // Rule 2: 著作重複
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
        // =========================================================
        $fuzzyDuplicates = collect([]);
        $shouldCheckFuzzy = false;
        $inputTitle = '';

        if ($type == 1 && !empty($data['article_title'])) {
            $shouldCheckFuzzy = true;
            $inputTitle = $data['article_title'];
        // } elseif ($type == 3 && empty($bookId) && !empty($data['book_title'])) {
        //     $shouldCheckFuzzy = true;
        //     $inputTitle = $data['book_title'];
        }

        if ($shouldCheckFuzzy) {

            // 1. 正規化 (注意：這裡長度改用 strlen 計算 Bytes，與 similar_text 統一單位)
            $cleanInput = $this->normalizeString($inputTitle);
            $byteLenInput = strlen($cleanInput); // 用 byte 長度

            // 防呆：byte 長度至少要 10 (約 3 個中文字或 10 個英文字)
            if ($byteLenInput >= 10) {

                $query = Reference::query()
                    ->select('id','title')
                    ->where('type', $type);

                if ($currentId) $query->where('id', '!=', $currentId);
                
                $candidates = $query->get();

                $fuzzyDuplicates = $candidates->map(function ($item) use ($cleanInput, $byteLenInput) {

                $dbTitleRaw = $item->title ?? '';

                    $cleanDb = $this->normalizeString($dbTitleRaw);
                    $byteLenDb = strlen($cleanDb); // 用 byte 長度
                    
                    if ($byteLenDb < 10) return null;

                    // A. 計算長度倍數 (判斷是否為並列題名)
                    $maxLen = max($byteLenInput, $byteLenDb);
                    $minLen = min($byteLenInput, $byteLenDb); // 分母
                    $lengthRatio = $maxLen / $minLen;

                    // B. 字元包含率 (bytes)
                    $matchingBytes = 0;
                    similar_text($cleanInput, $cleanDb, $matchingBytes); 
                    
                    $overlapRatio = ($matchingBytes / $minLen) * 100;

                    if ($overlapRatio < 80) return null; // 第一關淘汰

                    // C. LCS 連續性檢查 (改用 mb_ 函式處理中文字)
                    $lcsLengthChars = $this->getMultibyteLCSLength($cleanInput, $cleanDb);
                    
                    // 這裡要把 byte 長度轉回 char 長度來算比例，因為 LCS 是算 char
                    // 但為了簡單與效能，我們可以估算：LCS 越長越好
                    // 我們用 byte 來算 LCS 的比例 (近似值)
                    // 注意：getMultibyteLCSLength 回傳的是 Char 數，轉成 Byte 數大約 * 3 (中文) 或 * 1 (英文)
                    // 這邊為了精準，我們把分母轉成 Char 數
                    $charLenInput = mb_strlen($cleanInput);
                    $charLenDb = mb_strlen($cleanDb);
                    $minCharLen = min($charLenInput, $charLenDb);

                    if ($minCharLen == 0) return null;

                    $lcsRatio = ($lcsLengthChars / $minCharLen) * 100;

                    // D. 門檻決策 & 評分
                    $passed = false;
                    
                    // 如果長度差很多 (倍數 > 2.5)，要求高連續性 (75%)
                    if ($lengthRatio > 2.5) {
                         $passed = ($lcsRatio >= 75);
                    } else {
                         // 長度差不多，允許錯字，連續性低標 40%
                         $passed = ($lcsRatio >= 40);
                    }

                    if ($passed) {
                        // 計算最終分數：綜合 Overlap 和 LCS
                        // 加權：LCS 連續性更重要 (權重 0.6)，包含率次之 (0.4)
                        $score = ($overlapRatio * 0.4) + ($lcsRatio * 0.6);
                        
                        // 寫入屬性以便稍後排序
                        $item->match_score = round($score, 1); 
                        $item->match_reason = 'Fuzzy Match';
                        return $item;
                    }

                    return null;

                })->filter(); // 移除 null
            }
        }

        // =========================================================
        // 合併與排序 (Sorting) - 這是您想要的功能
        // =========================================================
        return $directDuplicates->merge($fuzzyDuplicates)
            ->unique('id')
            ->sortByDesc('match_score') // 依照分數由高到低排序
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    // 'match_score' => $item->match_score ?? 0, // 回傳分數給前端參考
                    'publish_year' => $item->publish_year,
                    'title' => $item->title,
                    'subtitle' => $item->subtitle,
                ];
            })
            ->values();
    }

    /**
     * [修正版] 清理字串：移除中文無意義連接詞
     */
    private function normalizeString($str)
    {
        // 1. 轉小寫
        $str = mb_strtolower($str);
        
        // 2. 移除常見中文雜訊字 (之, 的, 暨, 等, 關於, 研究)
        // 這一步對於 "臺灣之昆蟲" vs "臺灣昆蟲" 的比對非常關鍵
        $str = str_replace(['之', '的', '暨', '等', '關於', '研究', '報告'], '', $str);

        // 3. 只保留英數字與中文字 (移除標點符號)
        return preg_replace('/[^a-z0-9\x{4e00}-\x{9fa5}]/u', '', $str);
    }

    /**
     * [修正版] LCS 計算：支援 UTF-8 Multibyte (解決中文亂碼問題)
     * 回傳：最長連續相同的「字元數 (Chars)」
     */
    private function getMultibyteLCSLength($str1, $str2)
    {
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        if ($len1 == 0 || $len2 == 0) return 0;

        $short = ($len1 < $len2) ? $str1 : $str2;
        $long = ($len1 < $len2) ? $str2 : $str1;
        $shortLen = mb_strlen($short);

        // 快速檢查包含
        if (mb_strpos($long, $short) !== false) return $shortLen;

        // 滑動視窗 (Multibyte Safe)
        for ($len = $shortLen; $len > 0; $len--) {
            for ($start = 0; $start <= $shortLen - $len; $start++) {
                // 關鍵修正：使用 mb_substr 避免切斷中文字
                $sub = mb_substr($short, $start, $len);
                if (mb_strpos($long, $sub) !== false) {
                    return $len;
                }
            }
        }
        return 0;
    }



    // public function getPotentialDuplicates(array $data)
    // {
    //     $query = Reference::query();

    //     // 1. 排除自己 (如果是編輯模式)
    //     if (isset($this->reference->id)) {
    //         $query->where('id', '!=', $this->reference->id);
    //     }

    //     // 2. 套用 5 大比對規則
    //     $query->where(function ($q) use ($data) {
            
    //         // 規則 1: 期刊文章 (Type=1)
    //         if (isset($data['type']) && $data['type'] == 1 && !empty($data['article_title'])) {
    //             $q->orWhere(function ($sub) use ($data) {
    //                 $sub->where('type', 1)
    //                     ->where('title', 'like', '%' . $data['article_title'] . '%');
    //                 if (!empty($data['publish_year'])) {
    //                      $sub->where('publish_year', $data['publish_year']);
    //                 }
    //             });
    //         }

    //         // 規則 2: 書本 (Type=3) 
    //         // 比對: title (主欄位) + properties->volume 部冊號
    //         // TODO 這邊還有問題 可能是volume沒有寫入的時候會沒辦法正常判斷

    //         if (isset($data['type']) && $data['type'] == 3 && !empty($data['book_title'])) {
    //             $q->orWhere(function ($sub) use ($data) {
    //                 $sub->where('type', 3)
    //                     ->where('properties->book_title', $data['book_title']) // 書名通常存在主標題 title
    //                     ->where('properties->volume', $data['volume'] ?? '');
    //             });
    //         }

    //         // TODO 這邊還有問題 可能是volume & pages_range沒有寫入的時候會沒辦法正常判斷
    //         // 規則 3: 書籍文章 (Type=2)
    //         // 比對: properties->bookTitle (JSON) + volume + pagesRange
    //         if (isset($data['type']) && $data['type'] == 2 && !empty($data['book_title'])) {
    //             $q->orWhere(function ($sub) use ($data) {
    //                 $sub->where('type', 2)
    //                     // 書籍文章的 "書名" 通常存在 properties->bookTitle，主 title 是文章名
    //                     ->where('properties->book_title', $data['book_title']) 
    //                     ->where('properties->volume', $data['volume'] ?? '')
    //                     ->where('properties->pages_range', $data['pages_range'] ?? '');
    //             });
    //         }

    //         // 規則 4: 作者 + 年代 + book_id
    //         if (!empty($data['authors']) && !empty($data['publish_year']) && !empty($data['book_id'])) {
    //             $q->orWhere(function ($sub) use ($data) {
    //                 $sub->where('publish_year', $data['publish_year'])
    //                     ->where('book_id', $data['book_id'])
    //                     ->whereHas('authors', function ($authorQuery) use ($data) {
    //                         $authorQuery->whereIn('persons.id', $data['authors']);
    //                     }, '=', count($data['authors']));
    //             });
    //         }

    //         // 規則 5: book_id + volume + pages_range
    //         // 比對: book_id + properties->volume + properties->pagesRange
    //         if (!empty($data['book_id']) && !empty($data['pages_range'])) {
    //             $q->orWhere(function ($sub) use ($data) {
    //                 $sub->where('book_id', $data['book_id'])
    //                     ->where('properties->volume', $data['volume'] ?? '')
    //                     ->where('properties->pages_range', $data['pages_range']);
    //             });
    //         }
    //     });

    //     // 3. 執行查詢並整理回傳格式
    //     return $query->get()->map(function ($item) {
    //         return [
    //             'id' => $item->id,
    //             'subtitle' => $item->subtitle,
    //             'publish_year' => $item->publish_year,
    //             'title' => $item->title,
    //         ];
    //     });
    // }

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