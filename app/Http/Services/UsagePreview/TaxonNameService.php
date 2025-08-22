<?php

namespace App\Http\Services\UsagePreview;

use App\Http\Services\UsagePreview\Traits\ArrayConversionTrait;

class TaxonNameService
{
    use ArrayConversionTrait;
    
    protected $personNameService;

    public function __construct(PersonNameService $personNameService)
    {
        $this->personNameService = $personNameService;
    }

    /**
     * 渲染 TaxonNameLabel
     */
    public function renderTaxonNameLabel($taxonName, $class = '')
    {
        if (empty($taxonName['rank'])) {
            return '';
        }

        // 處理 hybrid-formula
        if (($taxonName['rank']['key'] ?? '') === 'hybrid-formula' && isset($taxonName['hybrid_parents'])) {
            $result = $this->renderTaxonNameLabel($taxonName['hybrid_parents'][0]);
            $result .= $this->renderAuthorName($taxonName['hybrid_parents'][0]);
            $result .= ' × ';
            $result .= $this->renderTaxonNameLabel($taxonName['hybrid_parents'][1]);
            $result .= $this->renderAuthorName($taxonName['hybrid_parents'][1]);
            return $result;
        }

        return $this->buildTaxonName($taxonName);
    }

    /**
     * 構建 taxon name
     */
    protected function buildTaxonName($taxonName)
    {
        $nomenclatureGroup = $taxonName['nomenclature']['group'] ?? '';
        
        if ($nomenclatureGroup === 'virus') {
            $latinName = $taxonName['properties']['latin_name'] ?? '';
            return '<i>' . $latinName . '</i>';
        }

        // 模擬 genusRank 和 speciesRank 的順序
        $genusRankOrder = 36; // 實際值
        $speciesRankOrder = 40; // 實際值
        
        $rankOrder = $taxonName['rank']['order'] ?? 0;
        $rankId = $taxonName['rank']['id'] ?? 0;

        // // 種以下才可能有 speciesName
        $speciesName = '';
        if ($rankOrder >= $speciesRankOrder && !empty( $taxonName['properties']['latin_genus']) && !empty($taxonName['properties']['latin_s1'])) {
            $speciesName = implode(' ', array_filter([
                $taxonName['properties']['latin_genus'] ?? '',
                $taxonName['properties']['latin_s1'] ?? ''
            ]));
        }

        // 屬(包含)以上
        $latinName = $rankOrder < $speciesRankOrder ? ($taxonName['properties']['latin_name'] ?? '') : '';

        $prevName = '';
        
        // subgen. / sect. / subsect.
        if (in_array($rankId, [31, 32, 33])) {
            if (isset($taxonName['genus_taxon_name'])) {
                $prevName = implode(' ', array_filter([
                    '<i>' . ($taxonName['genus_taxon_name']['name'] ?? '') . '</i>',
                    $taxonName['rank']['abbreviation'] ?? '',
                    $latinName ? '<i>' . $latinName . '</i>' : ''
                ]));
            } else {
                $prevName = $latinName ? '<i>' . $latinName . '</i>' : '';
            }
        } else {
            $prevName = $speciesName ?: $latinName ?: implode(' ', array_filter([
                $taxonName['properties']['latin_genus'] ?? '',
                $taxonName['properties']['latin_s1'] ?? ''
            ]));
        }

        // 雜交屬
        if ($rankOrder === $genusRankOrder && ($taxonName['properties']['is_hybrid'] ?? false)) {
            $prevName = '× <i>' . ($taxonName['properties']['latin_name'] ?? '') . '</i>';
        } elseif ($rankOrder >= $genusRankOrder && !in_array($rankId, [31, 32, 33])) {
            $prevName = $prevName ? '<i>' . $prevName . '</i>' : '';
        }

        // 雜交種
        if (($taxonName['rank']['key'] ?? '') === 'species' && ($taxonName['properties']['is_hybrid'] ?? false)) {
            $prevName = implode(' × ', array_filter([
                '<i>' . ($taxonName['properties']['latin_genus'] ?? '') . '</i>',
                '<i>' . ($taxonName['properties']['latin_s1'] ?? '') . '</i>'
            ]));
        }

        // 處理 sub layers
        $layers = '';
        if (isset($taxonName['species_layers'])) {
            $layerParts = [];
            foreach ($taxonName['species_layers'] as $index => $layer) {
                if ($index === 0 && $nomenclatureGroup === 'animal' && ($layer['rank']['abbreviation'] ?? '') === 'subsp.') {
                    $layerParts[] = $layer['latin_name'] ? '<i>' . $layer['latin_name'] . '</i>' : '';
                } else {
                    $layerParts[] = implode(' ', array_filter([
                        $layer['rank']['abbreviation'] ?? '',
                        $layer['latin_name'] ? '<i>' . $layer['latin_name'] . '</i>' : ''
                    ]));
                }
            }
            $layers = implode(' ', array_filter($layerParts));
        }

        return implode(' ', array_filter([$prevName, $layers]));
    }

    /**
     * 渲染 AuthorName
     */
    public function renderAuthorName($taxonName, $class = '')
    {
        // 確保 Collection 轉換為 Array（重要！）
        $authors = $this->ensureArray($taxonName['authors'] ?? []);
        $exAuthors = $this->ensureArray($taxonName['ex_authors'] ?? []);
        $type = $taxonName['nomenclature']['group'] ?? '';
        $publishYear = $taxonName['publish_year'] ?? '';
        $originalTaxonName = $taxonName['original_taxon_name'] ?? null;
        $initialYear = $taxonName['properties']['initial_year'] ?? '';

        // 完全對應 Vue.js AuthorName.vue 的邏輯
        // if (this.authors.length == 0 && this.exAuthors.length == 0 && !this.originalTaxonName && !this.taxonName?.properties.initialYear)
        if (count($authors)==0 && 
            count($exAuthors)==0 && 
            !$originalTaxonName && 
            empty($initialYear)) {
            
            $authorsName = $taxonName['properties']['authors_name'] ?? '';
            if (!empty($authorsName)) {
                return $authorsName;
            }
        }

        // 正常處理邏輯：根據 nomenclature group 調用對應的處理方法
        return $this->personNameService->authorNameStringFactory(
            $type,
            $authors,
            $exAuthors,
            $originalTaxonName,
            $taxonName,
            $publishYear
        );
    }
}