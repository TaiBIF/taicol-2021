<?php

namespace App\Http\Services\UsagePreview;

use App\Http\Services\UsagePreview\Traits\ArrayConversionTrait;
use Illuminate\Support\Facades\Log;

class ReferenceService
{
    use ArrayConversionTrait;
    const TYPE_JOURNAL = 1;
    const TYPE_BOOK_ARTICLE = 2;
    const TYPE_BOOK = 3;
    const TYPE_CHECKLIST = 5;

    protected $personNameService;

    public function __construct(PersonNameService $personNameService)
    {
        $this->personNameService = $personNameService;
    }

    /**
     * 植物參考文獻組合
     */
    public function comboPlant($references, $namesMode = 'combo_abbr')
    {
        if (count($references)==0) {
            return '';
        }

        $results = [];
        foreach ($references as $ref) {
            if (empty($ref)) {
                continue;
            }

            $showPage = '';
            if (!empty($ref['target']['properties']['article_number']) && !empty($ref['show_page'])) {
                $showPage = $ref['target']['properties']['article_number'] . ' (' . $ref['show_page'] . ')';
            } elseif (!empty($ref['show_page'])) {
                $showPage = $ref['show_page'];
            }

            $page = implode(', ', array_filter([
                $showPage,
                $ref['figure'] ?? ''
            ], function($value) {
                return !empty($value);
            }));

            // [volume]([issue]): [page_name]
            $volumeParts = array_filter([
                $ref['target']['properties']['volume'] ?? '',
                !empty($ref['target']['properties']['issue']) ? '(' . $ref['target']['properties']['issue'] . ')' : ''
            ], function($value) {
                return !empty($value);
            });
            
            $volume = implode('', $volumeParts);
            
            // 修正：只有當 volume 和 page 都不為空時才組合
            if (!empty($volume) && !empty($page)) {
                $volume = $volume . ': ' . $page;
            } elseif (!empty($page)) {
                $volume = $page;
            }

            $title = '';
            $authors = $ref['target']['authors'] ?? [];

            // 書籍或書籍章節有版本
            if (in_array($ref['target']['type'] ?? '', [self::TYPE_BOOK, self::TYPE_BOOK_ARTICLE])) {
                $titleParts = array_filter([
                    $this->getAuthorsByMode($authors, $namesMode),
                    $ref['target']['properties']['book_title_abbreviation'] ?? '',
                    !empty($ref['target']['properties']['edition']) ? $ref['target']['properties']['edition'] . ' ed.' : '',
                    $volume
                ], function($value) {
                    return !empty($value);
                });
                $title = implode(', ', $titleParts);
            } else {
                $authorBookTitle = implode(', ', array_filter([
                    $this->getAuthorsByMode($authors, $namesMode),
                    $ref['target']['properties']['book_title_abbreviation'] ?? ''
                ], function($value) {
                    return !empty($value);
                }));
                $title = implode(' ', array_filter([$authorBookTitle, $volume], function($value) {
                    return !empty($value);
                }));
            }

            $proParte = '';
            if ($ref['pro_parte'] ?? false) {
                if (!empty($ref['pro_parte_type'])) {
                    $proParte = str_replace('＿', '', $ref['pro_parte_type']);
                    if (!empty($ref['pro_parte_text'])) {
                        $proParte .= ' ' . $ref['pro_parte_text'];
                    }
                } else {
                    $proParte = 'pro parte';
                }
            }


            $resultParts = [];

            // 避免年份重複：只在 title 沒包含年份時才加
            $publishYear = $ref['target']['publish_year'] ?? '';
            $titleStr = $title;

            if (!empty($publishYear) && strpos($title, (string)$publishYear) === false) {
                $titleStr = implode('. ', array_filter([$title, $publishYear], function($value) {
                    return !empty($value);
                }));
            }

            $resultParts[] = $titleStr;

            if (!empty($ref['name_in_reference'])) {
                $resultParts[] = "'" . $ref['name_in_reference'] . "'";
            }

            if (!empty($proParte)) {
                $resultParts[] = $proParte;
            }

            $result = implode(', ', array_filter($resultParts, function($value) {
                return !empty($value);
            }));

            if (!empty($ref['description'])) {
                $result .= ' (' . $ref['description'] . ')';
            }

            $results[] = $result;
        }

        return implode('; ', array_filter($results, function($value) {
            return !empty($value);
        }));
    }

    /**
     * 動物參考文獻組合
     */
    public function comboAnimal($references, $namesMode = 'combo_last')
    {
        if (count($references)==0) {
            return '';
        }

        $results = [];
        foreach ($references as $ref) {
            if (empty($ref)) {
                continue;
            }

            $publishYear = $ref['target']['publish_year'] ?? '';
            $authors = $ref['target']['authors'] ?? [];

            $title = implode(', ', array_filter([
                $this->getAuthorsByMode($authors, $namesMode),
                $publishYear
            ], function($value) {
                return !empty($value);
            }));

            $showPage = '';
            if (!empty($ref['target']['properties']['article_number']) && !empty($ref['show_page'])) {
                $showPage = $ref['target']['properties']['article_number'] . ' (' . $ref['show_page'] . ')';
            } elseif (!empty($ref['show_page'])) {
                $showPage = $ref['show_page'];
            }

            $page = implode(', ', array_filter([
                $showPage,
                $ref['figure'] ?? ''
            ], function($value) {
                return !empty($value);
            }));

            $proParte = '';
            if ($ref['pro_parte'] ?? false) {
                if (!empty($ref['pro_parte_type'])) {
                    $proParte = str_replace('＿', '', $ref['pro_parte_type']);
                    if (!empty($ref['pro_parte_text'])) {
                        $proParte .= ' ' . $ref['pro_parte_text'];
                    }
                } else {
                    $proParte = 'pro parte';
                }
            }

            // 修正：只有當 title 和 page 都不為空時才用冒號連接
            $mainPart = '';
            if (!empty($title) && !empty($page)) {
                $mainPart = $title . ': ' . $page;
            } elseif (!empty($title)) {
                $mainPart = $title;
            } elseif (!empty($page)) {
                $mainPart = $page;
            }

            $resultParts = array_filter([
                $mainPart,
                !empty($ref['name_in_reference']) ? "'" . $ref['name_in_reference'] . "'" : '',
                $proParte ? $proParte : ''
            ], function($value) {
                return !empty($value);
            });

            $result = implode(', ', $resultParts);

            if (!empty($ref['description'])) {
                $result .= ' (' . $ref['description'] . ')';
            }

            $results[] = $result;
        }

        return implode('; ', array_filter($results, function($value) {
            return !empty($value);
        }));
    }

    /**
     * 根據模式獲取作者名稱
     */
    protected function getAuthorsByMode($authors, $mode)
    {
        if (count($authors)==0) {
            return '';
        }

        // 確保是 array 格式
        $authorsArray = $this->ensureArray($authors);

        switch ($mode) {
            case 'combo_last':
                return $this->personNameService->comboLast($authorsArray);
            case 'combo_abbr':
                return $this->personNameService->comboAbbr($authorsArray);
            case 'combo_full_last':
                return $this->personNameService->comboFullLast($authorsArray);
            case 'combo_full_abbr':
                return $this->personNameService->comboFullAbbr($authorsArray);
            case 'combo_full':
                return $this->personNameService->comboFull($authorsArray);
            case 'empty':
                return '';
            default:
                return $this->personNameService->comboAbbr($authorsArray);
        }
    }

    /**
     * 獲取參考文獻標題
     */
    public function title($reference)
    {
        if (!$reference) {
            return '';
        }

        switch ($reference['type'] ?? '') {
            case self::TYPE_JOURNAL:
                return $reference['properties']['article_title'] ?? '';
            case self::TYPE_BOOK_ARTICLE:
                return $this->renderBookArticleTitle($reference);
            case self::TYPE_BOOK:
                return $this->renderBookTitle($reference);
            case self::TYPE_CHECKLIST:
                return $reference['properties']['book_title'] ?? '';
            default:
                return '';
        }
    }

    /**
     * 獲取參考文獻副標題
     */
    public function subTitle($reference, $isWithAuthors = true)
    {
        if (!$reference) {
            return '';
        }

        if ($isWithAuthors) {
            $lastNames = $this->personNameService->comboLast($this->ensureArray($reference['authors'] ?? []));
            $subTitleResult = '';
            
            switch ($reference['type'] ?? '') {
                case self::TYPE_JOURNAL:
                    $subTitleResult = $this->renderJournalSubtitle($reference);
                    break;
                case self::TYPE_BOOK_ARTICLE:
                    $subTitleResult = $this->renderBookArticleSubtitle($reference);
                    break;
                case self::TYPE_BOOK:
                    $subTitleResult = $this->renderBookSubtitle($reference);
                    break;
                case self::TYPE_CHECKLIST:
                    $subTitleResult = $reference['properties']['book_title_abbreviation'] ?? '';
                    break;
                default:
                    $subTitleResult = ':Error:';
            }

            return implode(', ', array_filter([
                $lastNames,
                $reference['publish_year'] ?? '',
                $subTitleResult
            ], function($value) {
                return !empty($value);
            }));
        }

        switch ($reference['type'] ?? '') {
            case self::TYPE_JOURNAL:
                return $this->renderJournalSubtitle($reference);
            case self::TYPE_BOOK_ARTICLE:
                return $this->renderBookArticleSubtitle($reference);
            case self::TYPE_BOOK:
                return $this->renderBookSubtitle($reference);
            case self::TYPE_CHECKLIST:
                return $reference['properties']['book_title_abbreviation'] ?? '';
            default:
                return '';
        }
    }

    /**
     * 渲染書籍標題
     */
    protected function renderBookTitle($reference)
    {
        $volume = '';
        if (!empty($reference['properties']['volume'])) {
            $volume = 'vol. ' . $reference['properties']['volume'];
        } elseif (!empty($reference['properties']['chapter'])) {
            $volume = 'ch. ' . $reference['properties']['chapter'];
        }

        return implode(', ', array_filter([
            $reference['properties']['book_title'] ?? '',
            !empty($reference['properties']['edition']) ? $reference['properties']['edition'] . ' ed.' : '',
            $volume
        ], function($value) {
            return !empty($value);
        }));
    }

    /**
     * 渲染書籍文章標題
     */
    protected function renderBookArticleTitle($reference)
    {
        return !empty($reference['properties']['article_title']) ? 
            $reference['properties']['article_title'] : 
            $this->renderBookTitle($reference);
    }

    /**
     * 渲染期刊副標題
     */
    protected function renderJournalSubtitle($reference)
    {
        $t = $reference['properties']['book_title_abbreviation'] ?? '';
        $volume = $reference['properties']['volume'] ?? '';
        $issue = !empty($reference['properties']['issue']) ? '(' . $reference['properties']['issue'] . ')' : '';
        
        $lastPart = '';
        $volumeIssue = trim($volume . $issue);
        $pageOrArticle = !empty($reference['properties']['article_number']) ? 
            $reference['properties']['article_number'] : 
            ($reference['properties']['pages_range'] ?? '');
            
        // 修正：只有當兩個部分都不為空時才用冒號連接
        if (!empty($volumeIssue) && !empty($pageOrArticle)) {
            $lastPart = $volumeIssue . ': ' . $pageOrArticle;
        } elseif (!empty($volumeIssue)) {
            $lastPart = $volumeIssue;
        } elseif (!empty($pageOrArticle)) {
            $lastPart = $pageOrArticle;
        }

        return implode(' ', array_filter([$t, $lastPart], function($value) {
            return !empty($value);
        }));
    }

    /**
     * 渲染書籍副標題
     */
    protected function renderBookSubtitle($reference)
    {
        $volume = '';
        if (!empty($reference['properties']['volume'])) {
            $volume = $reference['properties']['volume'];
        } elseif (!empty($reference['properties']['chapter'])) {
            $volume = 'ch. ' . $reference['properties']['chapter'];
        }

        return implode(', ', array_filter([
            $reference['properties']['book_title_abbreviation'] ?? '',
            !empty($reference['properties']['edition']) ? $reference['properties']['edition'] . ' ed.' : '',
            $volume
        ], function($value) {
            return !empty($value);
        }));
    }

    /**
     * 渲染書籍文章副標題
     */
    protected function renderBookArticleSubtitle($reference)
    {
        $bookSubtitle = $this->renderBookSubtitle($reference);
        $pagesRange = $reference['properties']['pages_range'] ?? '';
        
        return implode(': ', array_filter([$bookSubtitle, $pagesRange], function($value) {
            return !empty($value);
        }));
    }
}