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
        if (($taxonName['rank']['key'] ?? '') === 'hybrid-formula') {
            // 缺雜交親本（例如舊的匯入資料）時直接顯示 name，避免匯出失敗
            if (count($this->ensureArray($taxonName['hybrid_parents'] ?? [])) < 2) {
                return htmlspecialchars($taxonName['name'] ?? '', ENT_NOQUOTES);
            }

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
        // 栽培植物（品種群 / 品種）：前綴分類群學名 + 品種名（不斜體、無命名者）
        $rankKey = $taxonName['rank']['key'] ?? '';
        if (in_array($rankKey, ['cultivar-group', 'cultivar'], true)) {
            return $this->buildCultivarName($taxonName, $rankKey);
        }

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
     * 構建栽培植物學名
     * 品種：[前綴分類群] '[cv_name]'
     * 品種群：[前綴分類群] ([cv_name] Group)
     */
    protected function buildCultivarName($taxonName, $rankKey)
    {
        $cvTaxonName = $taxonName['cv_taxon_name'] ?? null;
        if ($cvTaxonName instanceof \Illuminate\Http\Resources\Json\JsonResource) {
            $cvTaxonName = $cvTaxonName->toArray(request());
        }

        $base = $cvTaxonName ? $this->buildTaxonName($cvTaxonName) : '';
        $cvName = htmlspecialchars(trim($taxonName['properties']['cv_name'] ?? ''), ENT_NOQUOTES);

        if ($cvName === '') {
            return $base;
        }

        $suffix = $rankKey === 'cultivar-group' ? "({$cvName} Group)" : "'{$cvName}'";

        return implode(' ', array_filter([$base, $suffix]));
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