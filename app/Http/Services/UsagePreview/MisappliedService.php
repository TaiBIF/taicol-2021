<?php

namespace App\Http\Services\UsagePreview;

class MisappliedService
{
    protected $taxonNameService;
    protected $referenceService;
    protected $personNameService;

    public function __construct(TaxonNameService $taxonNameService, ReferenceService $referenceService, PersonNameService $personNameService)
    {
        $this->taxonNameService = $taxonNameService;
        $this->referenceService = $referenceService;
        $this->personNameService = $personNameService;
    }

    /**
     * 渲染 Misapplied 狀態
     */
    public function render($taxonName, $indications, $perUsages, $isSimple)
    {
        if (count($indications)==0) {
            return '[ERROR]:請選擇標註';
        }

        // 設置 is_approved_list 為 false
        if (!empty($taxonName['properties'])) {
            $taxonName['properties']['is_approved_list'] = false;
        }

        $indication = $indications[0];
        $abbreviation = $indication ?? '';

        switch ($abbreviation) {
            case 'auct. non':
                return $this->auctNon($taxonName, $indication, $perUsages, $isSimple);
            case 'nec':
                return $this->nec($taxonName, $indication, $perUsages, $isSimple);
            case 'non':
                return $this->non($taxonName, $indication, $perUsages, $isSimple);
            case 'not of':
                return $this->notOf($taxonName, $indication, $perUsages, $isSimple);
            case 'sensu':
                return $this->sensu($taxonName, $indication, $perUsages, $isSimple);
            case 'sensu auct.':
                return $this->sensuAuct($taxonName, $indication, $perUsages, $isSimple);
            default:
                throw new \Exception('Unknown misapplied indication: ' . $abbreviation);
        }
    }

    /**
     * 處理 auct. non
     */
    protected function auctNon($taxonName, $indication, $perUsages, $isSimple)
    {
        $taxonNameDOM = $this->taxonNameService->renderTaxonNameLabel($taxonName);
        $indicationGroupDOM = $indication ?? '';
        $authorNameDOM = $this->taxonNameService->renderAuthorName($taxonName);

        if ($isSimple) {
            return implode(' ', array_filter([
                $taxonNameDOM,
                $indicationGroupDOM,
                $authorNameDOM
            ]));
        }

        $referenceDOM = $this->getReferencePreview($perUsages, $taxonName['nomenclature']['group'] ?? '');
        
        $innerParts = array_filter([$authorNameDOM, $referenceDOM]);
        $innerResult = implode(': ', $innerParts);

        return implode(' ', array_filter([
            $taxonNameDOM,
            $indicationGroupDOM,
            $innerResult
        ]));
    }

    /**
     * 處理 nec
     */
    protected function nec($taxonName, $indication, $perUsages, $isSimple)
    {
        $taxonNameDOM = $this->taxonNameService->renderTaxonNameLabel($taxonName);
        $indicationGroupDOM = $indication ?? '';
        $authorNameDOM = $this->taxonNameService->renderAuthorName($taxonName);

        if ($isSimple) {
            return implode(' ', array_filter([
                $taxonNameDOM,
                $indicationGroupDOM,
                $authorNameDOM
            ]));
        }

        $referenceDOM = $this->getReferencePreview($perUsages, $taxonName['nomenclature']['group'] ?? '');
        
        $indicationAuthor = implode(' ', array_filter([$indicationGroupDOM, $authorNameDOM]));
        $referencePart = implode(', ', array_filter([$referenceDOM, $indicationAuthor]));

        return implode(': ', array_filter([
            $taxonNameDOM,
            $referencePart
        ]));
    }

    /**
     * 處理 non
     */
    protected function non($taxonName, $indication, $perUsages, $isSimple)
    {
        $taxonNameDOM = $this->taxonNameService->renderTaxonNameLabel($taxonName);
        $indicationGroupDOM = $indication ?? '';
        
        $authorName = $this->getAuthorNameByType($taxonName);

        if ($isSimple) {
            $bracketContent = implode(' ', array_filter([$indicationGroupDOM, $authorName]));
            return implode(' ', array_filter([
                $taxonNameDOM,
                '(' . $bracketContent . ')'
            ]));
        }

        $referenceDOM = $this->getReferencePreview($perUsages, $taxonName['nomenclature']['group'] ?? '');
        
        $bracketContent = implode(' ', array_filter([$indicationGroupDOM, $authorName]));
        $bracket = '(' . $bracketContent . ')';
        
        $innerParts = array_filter([$bracket, $referenceDOM]);
        $innerResult = implode(': ', $innerParts);

        return implode(' ', array_filter([
            $taxonNameDOM,
            $innerResult
        ]));
    }

    /**
     * 處理 not of
     */
    protected function notOf($taxonName, $indication, $perUsages, $isSimple)
    {
        // not of 的處理邏輯與 non 相同
        return $this->non($taxonName, $indication, $perUsages, $isSimple);
    }

    /**
     * 處理 sensu
     */
    protected function sensu($taxonName, $indication, $perUsages, $isSimple)
    {
        $taxonNameDOM = $this->taxonNameService->renderTaxonNameLabel($taxonName);
        $indicationGroupDOM = $indication ?? '';
        $authorNameDOM = $this->taxonNameService->renderAuthorName($taxonName);

        if ($isSimple) {
            return implode(' ', array_filter([
                $taxonNameDOM,
                'non',
                $authorNameDOM
            ]));
        }

        $referenceDOM = $this->getReferencePreview($perUsages, $taxonName['nomenclature']['group'] ?? '');
        
        $nonAuthor = implode(' ', array_filter(['non', $authorNameDOM]));
        $referencePart = implode(', ', array_filter([$referenceDOM, $nonAuthor]));

        return implode(' ', array_filter([
            $taxonNameDOM,
            $indicationGroupDOM,
            $referencePart
        ]));
    }

    /**
     * 處理 sensu auct.
     */
    protected function sensuAuct($taxonName, $indication, $perUsages, $isSimple)
    {
        // sensu auct. 的處理邏輯與 sensu 相同
        return $this->sensu($taxonName, $indication, $perUsages, $isSimple);
    }

    /**
     * 根據類型獲取作者名稱
     */
    protected function getAuthorNameByType($taxonName)
    {
        $type = $taxonName['nomenclature']['group'] ?? '';
        $authors = $taxonName['authors'] ?? [];
        $exAuthors = $taxonName['ex_authors'] ?? [];
        $originalTaxonName = $taxonName['original_taxon_name'] ?? null;
        $publishYear = $taxonName['publish_year'] ?? '';

        switch ($type) {
            case 'animal':
                $authorName = $this->personNameService->animalAuthorNames(
                    $authors,
                    $exAuthors,
                    $originalTaxonName,
                    $publishYear,
                    $taxonName
                );
                break;
            case 'plant':
                $authorName = $this->personNameService->plantAuthorNames(
                    $authors,
                    $exAuthors,
                    $originalTaxonName
                );
                break;
            case 'bacteria':
            case 'virus':
                $authorName = $this->personNameService->bacteriaAuthorNames(
                    $authors,
                    $exAuthors,
                    $originalTaxonName,
                    $publishYear,
                    $taxonName
                );
                break;
            default:
                $authorName = '';
        }

        // 移除括號
        return preg_replace('/[\(\)]/', '', $authorName);
    }

    /**
     * 獲取參考文獻預覽
     */
    protected function getReferencePreview($perUsages, $nomenclatureGroup)
    {
        if (count($perUsages)==0) {
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