<?php

namespace App\Http\Services;

use App\Http\Services\UsagePreview\PersonNameService;
use App\Http\Services\UsagePreview\ReferenceService;
use App\Http\Services\UsagePreview\TaxonNameService;
use App\Http\Services\UsagePreview\TypeSpecimenService;
use App\Http\Services\UsagePreview\UndeterminedService;
use App\Http\Services\UsagePreview\MisappliedService;

class UsagePreviewService
{
    protected $personNameService;
    protected $referenceService;
    protected $taxonNameService;
    protected $typeSpecimenService;
    protected $undeterminedService;
    protected $misappliedService;

    public function __construct()
    {
        $this->personNameService = new PersonNameService();
        $this->referenceService = new ReferenceService($this->personNameService);
        $this->taxonNameService = new TaxonNameService($this->personNameService);
        $this->typeSpecimenService = new TypeSpecimenService($this->personNameService);
        $this->undeterminedService = new UndeterminedService($this->taxonNameService, $this->referenceService);
        $this->misappliedService = new MisappliedService($this->taxonNameService, $this->referenceService, $this->personNameService);
    }

    /**
     * 主要處理方法
     * 
     * @param mixed $taxonName TaxonNameResource
     * @param array $indications
     * @param mixed $perUsages Collection
     * @param mixed $typeSpecimens Collection
     * @param string $status
     * @param bool $isSimple
     * @return array
     */
    public function process($taxonName, $indications, $perUsages, $typeSpecimens, $status, $isSimple = false)
    {
        // 轉換 TaxonNameResource 為 array
        $taxonNameArray = $this->convertTaxonNameResourceToArray($taxonName);
        
        // 轉換 Collections 為 arrays
        $perUsagesArray = $this->convertPerUsagesToArray($perUsages);
        $typeSpecimensArray = $this->convertTypeSpecimensToArray($typeSpecimens);

        return [
            'per_usages' => $this->processPerUsages($taxonNameArray, $indications, $perUsagesArray, $status, $isSimple),
            'type_specimens' => $this->processTypeSpecimens($taxonNameArray, $typeSpecimensArray, $isSimple)
        ];
    }

    /**
     * 處理 per_usages 部分
     */
    protected function processPerUsages($taxonName, $indications, $perUsages, $status, $isSimple)
    {
        switch ($status) {
            case 'undetermined':
                return $this->undeterminedService->render($taxonName, $indications, $perUsages, $isSimple);
            case 'misapplied':
                return $this->misappliedService->render($taxonName, $indications, $perUsages, $isSimple);
            default:
                return $this->renderAcceptedNotAccepted($taxonName, $indications, $perUsages, $status, $isSimple);
        }
    }

    /**
     * 處理 type_specimens 部分
     */
    protected function processTypeSpecimens($taxonName, $typeSpecimens, $isSimple)
    {
        if ($isSimple) {
            return '';
        }

        $result = '';
        $nomenclatureGroup = $taxonName['nomenclature']['group'] ?? '';
        
        if ($nomenclatureGroup === 'bacteria') {
            $result .= $this->typeSpecimenService->comboTypeStrain($typeSpecimens);
        } else {
            if (!empty($typeSpecimens)) {
                $result .= $this->typeSpecimenService->combo($typeSpecimens) . '.';
            }
            
            if (isset($taxonName['type_name']) && $taxonName['type_name']) {
                $result .= ' Type: ' . $this->taxonNameService->renderTaxonNameLabel($taxonName['type_name']) . 
                          ' ' . $this->taxonNameService->renderAuthorName($taxonName['type_name']);
            }
        }

        return $this->stripHtmlTags(trim($result));
    }

    /**
     * 渲染 AcceptedNotAccepted
     */
    protected function renderAcceptedNotAccepted($taxonName, $indications, $perUsages, $status, $isSimple)
    {
        // 設置 is_approved_list 為 false
        if (isset($taxonName['properties'])) {
            $taxonName['properties']['is_approved_list'] = false;
        }

        $taxonNameLabel = $this->taxonNameService->renderTaxonNameLabel($taxonName, 'is-orange');
        $authorName = $this->taxonNameService->renderAuthorName($taxonName, 'is-inline');
        
        $refPreview = '';
        if (!$isSimple) {
            $refPreview = $this->getRefPreview($taxonName, $perUsages);
            if ($refPreview && !str_ends_with($refPreview, '.')) {
                $refPreview .= '.';
            }
        }

        $indicationGroup = '';
        if (!empty($indications)) {
            $indicationParts = [];
            foreach ($indications as $indication) {
                $class = $status === 'accepted' ? 'has-text-success' : 'has-text-danger';
                $indicationParts[] = $indication ?? '';
            }
            $indicationGroup = implode(', ', $indicationParts);
        }

        $parts = [$taxonNameLabel];
        
        $innerParts = [$authorName];
        if ($refPreview) {
            $innerParts[] = $refPreview;
        }
        
        $separator = ($taxonName['nomenclature']['group'] ?? '') === 'animal' ? ': ' : ', ';
        $parts[] = implode($separator, array_filter($innerParts));
        
        if ($indicationGroup) {
            $parts[] = $indicationGroup;
        }

        return $this->stripHtmlTags(implode(' ', array_filter($parts)));
    }

    /**
     * 獲取參考文獻預覽
     */
    protected function getRefPreview($taxonName, $perUsages)
    {
        $nomenclatureGroup = $taxonName['nomenclature']['group'] ?? '';

        if ($nomenclatureGroup === 'animal') {
            $firstPerUsage = $perUsages[0] ?? null;
            if ($firstPerUsage && isset($firstPerUsage['target']) && !isset($taxonName['original_taxon_name'])) {
                $firstPerUsage['target']['publish_year'] = '';
            }

            $parts = [];
            if ($firstPerUsage) {
                $parts[] = $this->referenceService->comboAnimal(
                    [$firstPerUsage],
                    isset($taxonName['original_taxon_name']) ? 'combo_last' : 'empty'
                );
            }
            $parts[] = $this->referenceService->comboAnimal(array_slice($perUsages, 1), 'combo_last');

            return implode('; ', array_filter($parts));
        }

        if ($nomenclatureGroup === 'plant' || $nomenclatureGroup === 'bacteria') {
            $firstPerUsage = $perUsages[0] ?? null;
            $parts = [];
            if ($firstPerUsage) {
                $parts[] = $this->referenceService->comboPlant([$firstPerUsage], 'empty');
            }
            $parts[] = $this->referenceService->comboPlant(array_slice($perUsages, 1), 'combo_last');

            return implode('; ', array_filter($parts));
        }

        return '';
    }

    /**
     * 轉換 TaxonNameResource 為 array
     */
    protected function convertTaxonNameResourceToArray($taxonName)
    {
        if (is_array($taxonName)) {
            return $taxonName;
        }

        // 如果是 Laravel JsonResource
        if ($taxonName instanceof \Illuminate\Http\Resources\Json\JsonResource) {
            try {
                return $taxonName->toArray(request());
            } catch (\Exception $e) {
                try {
                    return $taxonName->toArray(new \Illuminate\Http\Request());
                } catch (\Exception $e2) {
                    return (array) $taxonName;
                }
            }
        }

        // 如果是 Model，轉換為 array
        if (is_object($taxonName) && method_exists($taxonName, 'toArray')) {
            try {
                return $taxonName->toArray();
            } catch (\Exception $e) {
                return (array) $taxonName;
            }
        }

        return (array) $taxonName;
    }

    /**
     * 轉換 perUsages Collection 為 array
     */
    protected function convertPerUsagesToArray($perUsages)
    {
        if (is_array($perUsages)) {
            return $perUsages;
        }

        // 如果是 Collection
        if (is_object($perUsages) && method_exists($perUsages, 'toArray')) {
            try {
                return $perUsages->toArray();
            } catch (\Exception $e) {
                // 如果 toArray 失敗，嘗試使用 collect() 轉換
                try {
                    return collect($perUsages)->map(function ($usage) {
                        if (is_array($usage)) {
                            return $usage;
                        }
                        if (is_object($usage) && method_exists($usage, 'toArray')) {
                            return $usage->toArray();
                        }
                        return (array) $usage;
                    })->toArray();
                } catch (\Exception $e2) {
                    return (array) $perUsages;
                }
            }
        }

        return collect($perUsages)->map(function ($usage) {
            if (is_array($usage)) {
                return $usage;
            }
            if (is_object($usage) && method_exists($usage, 'toArray')) {
                try {
                    return $usage->toArray();
                } catch (\Exception $e) {
                    return (array) $usage;
                }
            }
            return (array) $usage;
        })->toArray();
    }

    /**
     * 轉換 typeSpecimens Collection 為 array
     */
    protected function convertTypeSpecimensToArray($typeSpecimens)
    {
        if (is_array($typeSpecimens)) {
            return $typeSpecimens;
        }

        // 如果是 Collection
        if (is_object($typeSpecimens) && method_exists($typeSpecimens, 'toArray')) {
            try {
                return $typeSpecimens->toArray();
            } catch (\Exception $e) {
                // 如果 toArray 失敗，嘗試使用 collect() 轉換
                try {
                    return collect($typeSpecimens)->map(function ($specimen) {
                        if (is_array($specimen)) {
                            return $specimen;
                        }
                        if (is_object($specimen) && method_exists($specimen, 'toArray')) {
                            return $specimen->toArray();
                        }
                        return (array) $specimen;
                    })->toArray();
                } catch (\Exception $e2) {
                    return (array) $typeSpecimens;
                }
            }
        }

        return collect($typeSpecimens)->map(function ($specimen) {
            if (is_array($specimen)) {
                return $specimen;
            }
            if (is_object($specimen) && method_exists($specimen, 'toArray')) {
                try {
                    return $specimen->toArray();
                } catch (\Exception $e) {
                    return (array) $specimen;
                }
            }
            return (array) $specimen;
        })->toArray();
    }

    /**
     * 移除 HTML 標籤（除了斜體 <i>）
     */
    protected function stripHtmlTags($text)
    {
        // 保護 <i> 標籤
        $text = preg_replace('/<i>(.*?)<\/i>/', '###ITALIC_START###$1###ITALIC_END###', $text);
        
        // 移除所有 HTML 標籤
        $text = strip_tags($text);
        
        // 恢復 <i> 標籤
        $text = preg_replace('/###ITALIC_START###(.*?)###ITALIC_END###/', '<i>$1</i>', $text);
        
        return $text;
    }

    /**
     * 輔助方法：interleave
     */
    protected function interleave($array, $separator)
    {
        if (empty($array)) {
            return [];
        }

        $result = [];
        foreach ($array as $index => $item) {
            $result[] = $item;
            if ($index < count($array) - 1) {
                $result[] = $separator;
            }
        }
        return $result;
    }
}