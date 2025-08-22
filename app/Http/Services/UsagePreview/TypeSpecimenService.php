<?php

namespace App\Http\Services\UsagePreview;

use App\Http\Services\UsagePreview\Traits\ArrayConversionTrait;

class TypeSpecimenService
{
    use ArrayConversionTrait;
    protected $personNameService;

    // Type specimen kind constants
    const TYPE_SPECIMEN_KINDS = [
        1 => ['key' => 'specimen', 'display' => 'Specimen'],
        2 => ['key' => 'image', 'display' => 'Illustration'],
        3 => ['key' => 'photo', 'display' => 'Photo'],
        4 => ['key' => 'DNA', 'display' => 'DNA'],
        5 => ['key' => 'strain', 'display' => 'Strain'],
    ];

    public function __construct(PersonNameService $personNameService)
    {
        $this->personNameService = $personNameService;
    }

    /**
     * Type specimens 組合邏輯
     */
    public function combo($typeSpecimens)
    {
        if (count($typeSpecimens)==0) {
            return '';
        }

        $grouped = $this->groupBy($typeSpecimens, 'use');
        $results = [];

        foreach ($grouped as $useKey => $specimens) {
            if ($useKey === 'null' || $useKey === null) {
                continue;
            }

            $useString = ucfirst($useKey);
            $typeSpecimensString = '';

            if ($useKey === 'lectotype') {
                $parts = [];
                foreach ($specimens as $specimen) {
                    if (($specimen['kind'] ?? 0) === 1) {
                        $parts[] = $this->renderLectotypeTypeSpecimen($specimen);
                    } else {
                        $parts[] = $this->renderLectotypeOtherTypeSpecimen($specimen);
                    }
                }
                $typeSpecimensString = implode('; ', $parts);
            } elseif ($useKey === 'type') {
                $parts = [];
                foreach ($specimens as $specimen) {
                    if (($specimen['kind'] ?? 0) === 5) {
                        $useString = null;
                        return $this->comboTypeStrain($specimens);
                    } elseif (($specimen['kind'] ?? 0) === 1) {
                        $parts[] = $this->renderGeneralTypeSpecimen($specimen);
                    } else {
                        $parts[] = $this->renderGeneralOtherTypeSpecimen($specimen);
                    }
                }
                $typeSpecimensString = implode('; ', $parts);
            } else {
                $parts = [];
                foreach ($specimens as $specimen) {
                    if (($specimen['kind'] ?? 0) === 1) {
                        $parts[] = $this->renderGeneralTypeSpecimen($specimen);
                    } else {
                        $parts[] = $this->renderGeneralOtherTypeSpecimen($specimen);
                    }
                }
                $typeSpecimensString = implode('; ', $parts);
            }

            if ($useString && $typeSpecimensString) {
                $results[] = $useString . ': ' . $typeSpecimensString;
            } elseif ($typeSpecimensString) {
                $results[] = $typeSpecimensString;
            }
        }

        return implode('. ', $results);
    }

    /**
     * Type Strain 組合
     */
    public function comboTypeStrain($typeSpecimens)
    {
        if (count($typeSpecimens)==0) {
            return '';
        }
        
        $specimensArray = $this->ensureArray($typeSpecimens);
        $citationNumbers = array_map(function($specimen) {
            return $specimen['citation_note_number'] ?? '';
        }, $specimensArray);
        
        return 'Type Strain: ' . implode('; ', array_filter($citationNumbers)) . '.';
    }

    /**
     * 渲染一般標本
     */
    protected function renderGeneralTypeSpecimen($typeSpecimen)
    {
        $s = $typeSpecimen;

        // [type_sex] [type_country],
        $sexCountry = implode(' ', array_filter([
            $s['sex']['name'] ?? '',
            !empty($s['country']['display']['en-us']) ? strtoupper($s['country']['display']['en-us']) : ''
        ], function($value) {
            return !empty($value);
        }));

        $locality = '';
        $hasLocality = !empty($s['locality']);
        $hasLocalityVerbatim = !empty($s['locality_verbatim']);

        if ($hasLocality && $hasLocalityVerbatim) {
            // 兩者都存在：使用[type_locality] ([type_locality_verbatim])
            $locality = $s['locality'] . ' (' . $s['locality_verbatim'] . ')';
        } elseif ($hasLocality) {
            // 只有locality：不加括號
            $locality = $s['locality'];
        } elseif ($hasLocalityVerbatim) {
            // 只有locality_verbatim：不加括號
            $locality = $s['locality_verbatim'];
        }

        // [collection_day] [collection_month] [collection_year],
        $collectionInfo = implode(' ', array_filter([
            $s['collection_day'] ?? '',
            $s['collection_month'] ?? '',
            $s['collection_year'] ?? ''
        ], function($value) {
            return !empty($value);
        }));

        // [collector] [collector_number]
        $collectors = '';
        if (!empty($s['collectors'])) {
            $collectorsArray = $this->ensureArray($s['collectors']);
            $collectorNames = array_map(function($c) {
                return $this->personNameService->fullNameAbbreviation($c, true);
            }, $collectorsArray);
            $collectors = implode(', ', array_filter($collectorNames, function($value) {
                return !empty($value);
            }));
        }

        


        // [collector] [collector_number] - 使用帶空格的縮寫
        $collectors = '';
        if (!empty($s['collectors'])) {
            $collectorsArray = $this->ensureArray($s['collectors']);
            $collectorNames = array_map(function($c) {
                $firstName = $c['first_name'] ?? '';
                $middleName = $c['middle_name'] ?? '';
                $lastName = $c['last_name'] ?? '';

                // 使用帶空格的縮寫方法
                $firstNameAbbr = $this->personNameService->toFirstnameAbbr($firstName);
                $middleNameAbbr = $this->personNameService->toMiddlenameAbbrWithSpaces($middleName);

                $fullFirstName = trim($firstNameAbbr . ' ' . $middleNameAbbr);
                return trim($fullFirstName . ' ' . $lastName);
            }, $collectorsArray);
            
            $collectors = implode(', ', array_filter($collectorNames, function($value) {
                return !empty($value);
            }));
        }

        $collectorsInfo = implode(' ', array_filter([
            $collectors,
            $s['collector_number'] ?? ''
        ], function($value) {
            return !empty($value);
        }));

        // 模式標本 ([herbarium] [[accession_number]], iso[type_use] [[accession_number]]).
        $ss = [];
        if (!empty($s['specimens'])) {
            $specimensArray = $this->ensureArray($s['specimens']);
            $ss = array_map(function($specimen) {
                return implode(' ', array_filter([
                    $specimen['herbarium'] ?? '',
                    !empty($specimen['accession_number']) ? '[' . $specimen['accession_number'] . ']' : ''
                ], function($value) {
                    return !empty($value);
                }));
            }, $specimensArray);
            // 過濾掉空的標本
            $ss = array_filter($ss, function($value) {
                return !empty($value);
            });
        }

        $useString = strtolower($typeSpecimen['use'] ?? '');
        $isoString = $this->isoString($useString);
        
        $specimens = '';
        if (!empty($ss)) {
            $firstTwo = array_slice($ss, 0, 2);
            $rest = array_slice($ss, 2);
            
            $specimenParts = [];
            if (!empty($firstTwo)) {
                $specimenParts[] = implode('; ' . $isoString . ': ', $firstTwo);
            }
            if (!empty($rest)) {
                $specimenParts[] = implode(', ', $rest);
            }
            
            $specimens = implode(', ', array_filter($specimenParts, function($value) {
                return !empty($value);
            }));
        }

        // 組合主要部分
        $mainParts = array_filter([
            $sexCountry,
            $locality,
            $collectionInfo,
            $collectorsInfo
        ], function($value) {
            return !empty($value);
        });

        $mainText = implode(', ', $mainParts);

        // 關鍵修正：當有標本信息時，檢查主要部分是否以逗號結尾
        if (!empty($specimens)) {
            if (!empty($mainText)) {
                // 如果主要文字不為空，添加空格後再加括號（避免逗號+括號的情況）
                return $mainText . ' (' . $specimens . ')';
            } else {
                // 如果主要文字為空，直接返回括號內容
                return '(' . $specimens . ')';
            }
        } else {
            // 沒有標本信息，直接返回主要文字
            return $mainText;
        }
    }

    /**
     * 渲染一般其他標本
     */
    protected function renderGeneralOtherTypeSpecimen($typeSpecimen)
    {
        $kindObject = self::TYPE_SPECIMEN_KINDS[$typeSpecimen['kind'] ?? 0] ?? null;
        
        return implode(' ', array_filter([
            $typeSpecimen['citation_note_number'] ?? '',
            $kindObject ? '[' . $kindObject['key'] . ']' : ''
        ]));
    }

    /**
     * 渲染 Lectotype 標本
     */
    protected function renderLectotypeTypeSpecimen($typeSpecimen)
    {
        if ($typeSpecimen['is_designated'] ?? false) {
            return implode(' ', array_filter([
                $this->renderGeneralTypeSpecimen($typeSpecimen),
                'here designated'
            ]));
        }

        $refString = '';
        if (!empty($typeSpecimen['lecto_designated_reference'])) {
            $ref = $typeSpecimen['lecto_designated_reference'];
            $refParts = array_filter([
                $this->personNameService->comboLast($ref['authors'] ?? []),
                $ref['publish_year'] ?? ''
            ]);
            $refString = implode(', ', $refParts);
            
            if (!empty($typeSpecimen['lecto_cite_page'])) {
                $refString .= ': ' . $typeSpecimen['lecto_cite_page'];
            }
        }

        return implode(' ', array_filter([
            $this->renderGeneralTypeSpecimen($typeSpecimen),
            $refString ? 'designated by ' . $refString : ''
        ]));
    }

    /**
     * 渲染 Lectotype 其他標本
     */
    protected function renderLectotypeOtherTypeSpecimen($typeSpecimen)
    {
        $kindObject = self::TYPE_SPECIMEN_KINDS[$typeSpecimen['kind'] ?? 0] ?? null;

        if ($typeSpecimen['is_designated'] ?? false) {
            return implode(' ', array_filter([
                $typeSpecimen['citation_note_number'] ?? '',
                $kindObject ? '[' . $kindObject['key'] . ']' : '',
                'here designated'
            ]));
        }

        $refString = '';
        if (!empty($typeSpecimen['lecto_designated_reference'])) {
            $ref = $typeSpecimen['lecto_designated_reference'];
            $refParts = array_filter([
                $this->personNameService->comboLast($ref['authors'] ?? []),
                $ref['publish_year'] ?? ''
            ]);
            $refString = implode(', ', $refParts);
            
            if (!empty($typeSpecimen['lecto_cite_page'])) {
                $refString .= ': ' . $typeSpecimen['lecto_cite_page'];
            }
        }

        return implode(' ', array_filter([
            $typeSpecimen['citation_note_number'] ?? '',
            $kindObject ? '[' . $kindObject['key'] . ']' : '',
            $refString ? 'designated by ' . $refString : ''
        ]));
    }

    /**
     * ISO 字符串生成
     */
    protected function isoString($use)
    {
        switch ($use) {
            case 'holotype':
                return 'isotype';
            default:
                return 'iso' . $use;
        }
    }

    /**
     * 群組化數組
     */
    protected function groupBy($array, $key)
    {
        $result = [];
        foreach ($array as $item) {
            $groupKey = $item[$key] ?? 'null';
            if (!isset($result[$groupKey])) {
                $result[$groupKey] = [];
            }
            $result[$groupKey][] = $item;
        }
        return $result;
    }
}