<?php

namespace App\Http\Services\UsagePreview;

class UndeterminedService
{
    protected $taxonNameService;
    protected $referenceService;

    public function __construct(TaxonNameService $taxonNameService, ReferenceService $referenceService)
    {
        $this->taxonNameService = $taxonNameService;
        $this->referenceService = $referenceService;
    }

    /**
     * 渲染 Undetermined 狀態
     */
    public function render($taxonName, $indications, $perUsages, $isSimple)
    {
        if (empty($indications)) {
            return '[ERROR]:請選擇標註';
        }

        $indication = $indications[0];
        $abbreviation = $indication ?? '';

        switch ($abbreviation) {
            case '?':
            case 'sp.':
                return $this->questionMark($taxonName, $abbreviation);
            case 'aff.':
            case 'cf.':
                return $this->between($taxonName, $abbreviation, $perUsages, $isSimple);
            default:
                return $this->others($taxonName, $indication, $perUsages, $isSimple);
        }
    }

    /**
     * 處理問號類型
     */
    protected function questionMark($taxonName, $indication)
    {
        $taxonNameDOM = $this->taxonNameService->renderTaxonNameLabel($taxonName);
        
        return implode(' ', array_filter([
            $taxonNameDOM,
            $indication
        ]));
    }

    /**
     * 處理 between 類型 (aff., cf.)
     */
    protected function between($taxonName, $indication, $perUsages, $isSimple)
    {
        $taxonNameDOM = $this->renderTaxonNameWithUndeterminedIndication($taxonName, $indication);
        $authorNameDOM = $this->taxonNameService->renderAuthorName($taxonName);
        
        $parts = [$taxonNameDOM, $authorNameDOM];
        
        if (!$isSimple) {
            $referenceDOM = $this->getReferencePreview($perUsages, $taxonName['nomenclature']['group'] ?? '');
            if ($referenceDOM) {
                $parts[] = $referenceDOM;
            }
        }

        return implode(' ', array_filter($parts));
    }

    /**
     * 處理其他類型
     */
    protected function others($taxonName, $indication, $perUsages, $isSimple)
    {
        // 這裡實際上會調用 AcceptedNotAccepted 的邏輯
        // 設置 is_approved_list 為 false
        if (!empty($taxonName['properties'])) {
            $taxonName['properties']['is_approved_list'] = false;
        }

        $taxonNameLabel = $this->taxonNameService->renderTaxonNameLabel($taxonName);
        $authorName = $this->taxonNameService->renderAuthorName($taxonName);
        
        $refPreview = '';
        if (!$isSimple) {
            $refPreview = $this->getReferencePreview($perUsages, $taxonName['nomenclature']['group'] ?? '');
            if ($refPreview && !str_ends_with($refPreview, '.')) {
                $refPreview .= '.';
            }
        }

        $indicationGroup = $indication ?? '';

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

        return implode(' ', array_filter($parts));
    }

    /**
     * 渲染帶有未確定指示的分類名稱標籤
     */
    protected function renderTaxonNameWithUndeterminedIndication($taxonName, $indication)
    {
        // 這是一個簡化版本，實際的實現可能需要更複雜的邏輯
        // 根據原始代碼，這應該會插入 indication 到適當的位置
        $baseName = $this->taxonNameService->renderTaxonNameLabel($taxonName);
        
        // 對於 aff. 和 cf.，通常插入在種名之前
        // 這裡做簡化處理，直接在前面添加
        return $baseName . ' ' . $indication;
    }

    /**
     * 獲取參考文獻預覽
     */
    protected function getReferencePreview($perUsages, $nomenclatureGroup)
    {
        if (empty($perUsages)) {
            return '';
        }

        switch ($nomenclatureGroup) {
            case 'animal':
                $referencePreview = $this->referenceService->comboAnimal($perUsages, 'combo_last');
                break;
            case 'plant':
            case 'bacteria':
                $referencePreview = $this->referenceService->comboPlant($perUsages, 'combo_last');
                break;
            default:
                return '';
        }

        if ($referencePreview && !str_ends_with($referencePreview, '.')) {
            $referencePreview .= '.';
        }

        return $referencePreview;
    }
}