<?php

namespace App\Http\Services\UsagePreview;

class TaxonNameService
{
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
        if (($taxonName['rank']['key'] ?? '') === 'hybrid-formula' && !empty($taxonName['hybrid_parents'])) {
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

        // 種以下才可能有 speciesName
        $speciesName = '';
        if ($rankOrder >= $speciesRankOrder && !empty($taxonName['species'])) {
            $speciesName = implode(' ', array_filter([
                $taxonName['species']['properties']['latin_genus'] ?? '',
                $taxonName['species']['properties']['latin_s1'] ?? ''
            ]));
        }

        // 屬(包含)以上
        $latinName = $rankOrder < $speciesRankOrder ? ($taxonName['properties']['latin_name'] ?? '') : '';

        $prevName = '';
        
        // subgen. / sect. / subsect.
        if (in_array($rankId, [31, 32, 33])) {
            if (!empty($taxonName['genus_taxon_name'])) {
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
        if (!empty($taxonName['species_layers'])) {
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
        $authors = $taxonName['authors'] ?? [];
        $exAuthors = $taxonName['ex_authors'] ?? [];
        $type = $taxonName['nomenclature']['group'] ?? '';
        $publishYear = $taxonName['publish_year'] ?? '';
        $originalTaxonName = $taxonName['original_taxon_name'] ?? null;

        // 如果沒有作者信息但有 authorsName
        if (empty($authors) && empty($exAuthors) && !$originalTaxonName && empty($taxonName['properties']['initial_year'] ?? '')) {
            if (!empty($taxonName['properties']['authors_name'])) {
                return $taxonName['properties']['authors_name'];
            }
        }

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