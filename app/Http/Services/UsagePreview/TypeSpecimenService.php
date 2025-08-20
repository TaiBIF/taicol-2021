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
        if (empty($typeSpecimens)) {
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
        if (empty($typeSpecimens)) {
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
        ]));

        // [type_locality]([type_locality_verbatim]),
        $locality = $s['locality'] ?? '';
        if (!empty($s['locality_verbatim'])) {
            $locality .= '(' . $s['locality_verbatim'] . ')';
        }

        // [collection_day] [collection_month] [collection_year],
        $collectionInfo = implode(' ', array_filter([
            $s['collection_day'] ?? '',
            $s['collection_month'] ?? '',
            $s['collection_year'] ?? ''
        ]));

        // [collector] [collector_number]
        $collectors = '';
        if (!empty($s['collectors']) && !empty($s['collectors'])) {
            $collectorsArray = $this->ensureArray($s['collectors']);
            $collectorNames = array_map(function($c) {
                return $this->personNameService->fullNameAbbreviation($c, true);
            }, $collectorsArray);
            $collectors = implode(', ', $collectorNames);
        }

        $collectorsInfo = implode(' ', array_filter([
            $collectors,
            $s['collector_number'] ?? ''
        ]));

        // 模式標本 ([herbarium] [[accession_number]], iso[type_use] [[accession_number]]).
        $ss = [];
        if (!empty($s['specimens']) && !empty($s['specimens'])) {
            $specimensArray = $this->ensureArray($s['specimens']);
            $ss = array_map(function($specimen) {
                return implode(' ', array_filter([
                    $specimen['herbarium'] ?? '',
                    !empty($specimen['accession_number']) ? '[' . $specimen['accession_number'] . ']' : ''
                ]));
            }, $specimensArray);
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
            
            $specimens = implode(', ', $specimenParts);
        }

        $mainParts = array_filter([
            $sexCountry,
            $locality,
            $collectionInfo,
            $collectorsInfo
        ]);

        return implode(', ', array_filter([
            implode(', ', $mainParts),
            $specimens ? '(' . $specimens . ')' : ''
        ]));
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
            if (!!empty($result[$groupKey])) {
                $result[$groupKey] = [];
            }
            $result[$groupKey][] = $item;
        }
        return $result;
    }
}