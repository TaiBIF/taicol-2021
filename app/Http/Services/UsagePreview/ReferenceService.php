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
        if (empty($references)) {
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
            ]));

            // [volume]([issue]): [page_name]
            $volumeParts = array_filter([
                $ref['target']['properties']['volume'] ?? '',
                !empty($ref['target']['properties']['issue']) ? '(' . $ref['target']['properties']['issue'] . ')' : ''
            ]);
            $volume = implode('', $volumeParts);
            if ($page) {
                $volume = $volume ? $volume . ': ' . $page : $page;
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
                ]);
                $title = implode(', ', $titleParts);
            } else {
                $authorBookTitle = implode(', ', array_filter([
                    $this->getAuthorsByMode($authors, $namesMode),
                    $ref['target']['properties']['book_title_abbreviation'] ?? ''
                ]));
                $title = implode(' ', array_filter([$authorBookTitle, $volume]));
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

            $resultParts = array_filter([
                implode('. ', array_filter([$title, $ref['target']['publish_year'] ?? ''])),
                !empty($ref['name_in_reference']) ? "'" . $ref['name_in_reference'] . "'" : '',
                $proParte ? $proParte : ''
            ]);

            $result = implode(', ', $resultParts);

            if (!empty($ref['description'])) {
                $result .= ' (' . $ref['description'] . ')';
            }

            $results[] = $result;
        }

        return implode('; ', array_filter($results));
    }

    /**
     * 動物參考文獻組合
     */
    public function comboAnimal($references, $namesMode = 'combo_last')
    {
        if (empty($references)) {
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
            ]));

            $showPage = '';
            if (!empty($ref['target']['properties']['article_number']) && !empty($ref['show_page'])) {
                $showPage = $ref['target']['properties']['article_number'] . ' (' . $ref['show_page'] . ')';
            } elseif (!empty($ref['show_page'])) {
                $showPage = $ref['show_page'];
            }

            $page = implode(', ', array_filter([
                $showPage,
                $ref['figure'] ?? ''
            ]));

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

            $mainPart = $page ? $title . ': ' . $page : $title;
            $resultParts = array_filter([
                $mainPart,
                !empty($ref['name_in_reference']) ? "'" . $ref['name_in_reference'] . "'" : '',
                $proParte ? $proParte : ''
            ]);

            $result = implode(', ', $resultParts);

            if (!empty($ref['description'])) {
                $result .= ' (' . $ref['description'] . ')';
            }

            $results[] = $result;
        }

        return implode('; ', array_filter($results));
    }

    /**
     * 根據模式獲取作者名稱
     */
    protected function getAuthorsByMode($authors, $mode)
    {
        if (empty($authors)) {
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
            ]));
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
        ]));
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
        $volumeIssue = $volume . $issue;
        $pageOrArticle = !empty($reference['properties']['article_number']) ? 
            $reference['properties']['article_number'] : 
            ($reference['properties']['pages_range'] ?? '');
            
        if ($volumeIssue && $pageOrArticle) {
            $lastPart = $volumeIssue . ': ' . $pageOrArticle;
        } elseif ($volumeIssue) {
            $lastPart = $volumeIssue;
        } elseif ($pageOrArticle) {
            $lastPart = $pageOrArticle;
        }

        return implode(' ', array_filter([$t, $lastPart]));
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
        ]));
    }

    /**
     * 渲染書籍文章副標題
     */
    protected function renderBookArticleSubtitle($reference)
    {
        $bookSubtitle = $this->renderBookSubtitle($reference);
        $pagesRange = $reference['properties']['pages_range'] ?? '';
        
        return implode(': ', array_filter([$bookSubtitle, $pagesRange]));
    }
}