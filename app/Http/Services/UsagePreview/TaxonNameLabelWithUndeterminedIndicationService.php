<?php

namespace App\Http\Services\UsagePreview;

class TaxonNameLabelWithUndeterminedIndicationService
{
    const GENUS_RANK_ORDER = 36;
    const SPECIES_RANK_ORDER = 40;

    /**
     * 渲染帶有未確定指示的分類名稱標籤
     */
    public function render($taxonName, $indication)
    {
        if (empty($taxonName['rank'])) {
            return '';
        }

        // 處理 hybrid-formula
        if (($taxonName['rank']['key'] ?? '') === 'hybrid-formula' && !empty($taxonName['hybrid_parents'])) {
            $result = $this->render($taxonName['hybrid_parents'][0], $indication);
            // 這裡需要加入 AuthorName，但為了簡化先省略
            $result .= ' × ';
            $result .= $this->render($taxonName['hybrid_parents'][1], $indication);
            return $result;
        }

        return $this->buildNameWithIndication($taxonName, $indication);
    }

    /**
     * 構建帶有指示的名稱
     */
    protected function buildNameWithIndication($taxonName, $indication)
    {
        $t = $taxonName;
        $rankOrder = $t['rank']['order'] ?? 0;

        // 屬(包含)以下的學名斜體 (屬以上不斜題)
        $isItalic = $rankOrder >= self::GENUS_RANK_ORDER;

        // 種以下才可能有 speciesName
        $speciesName = '';
        if (!empty($t['species'])) {
            $speciesNameParts = [];
            
            if (!empty($t['species']['properties']['latin_genus'])) {
                $speciesNameParts[] = '<i>' . $t['species']['properties']['latin_genus'] . '</i>';
            }

            // 檢查是否需要在這裡插入指示
            $shouldInsertIndicationHere = empty($t['species']['species_layers']) &&
                !empty($t['species']['properties']['latin_genus']) &&
                !empty($t['species']['properties']['latin_s1']);

            if ($shouldInsertIndicationHere) {
                $speciesNameParts[] = $indication;
            }

            if (!empty($t['species']['properties']['latin_s1'])) {
                $speciesNameParts[] = '<i>' . $t['species']['properties']['latin_s1'] . '</i>';
            }

            $speciesName = implode(' ', $speciesNameParts);
        }

        // 種以上
        $latinName = '';
        if ($rankOrder < self::SPECIES_RANK_ORDER) {
            $latinNameParts = [$indication];
            
            if (!empty($t['properties']['latin_name'])) {
                if ($isItalic) {
                    $latinNameParts[] = '<i>' . $t['properties']['latin_name'] . '</i>';
                } else {
                    $latinNameParts[] = $t['properties']['latin_name'];
                }
            }
            
            $latinName = implode(' ', $latinNameParts);
        }

        $prevName = $speciesName ?: $latinName;
        
        // 如果以上都沒有，使用基本格式
        if (!$prevName) {
            $prevNameParts = [];
            
            if (!empty($t['properties']['latin_genus'])) {
                $prevNameParts[] = '<i>' . $t['properties']['latin_genus'] . '</i>';
            }

            if (empty($t['species_layers'])) {
                $prevNameParts[] = $indication;
            }

            if (!empty($t['properties']['latin_s1'])) {
                $prevNameParts[] = '<i>' . $t['properties']['latin_s1'] . '</i>';
            }

            $prevName = implode(' ', $prevNameParts);
        }

        // 處理雜交種
        if (($t['rank']['key'] ?? '') === 'species' && ($t['properties']['is_hybrid'] ?? false)) {
            $prevName = implode(' × ', array_filter([
                !empty($t['properties']['latin_genus']) ? '<i>' . $t['properties']['latin_genus'] . '</i>' : '',
                !empty($t['properties']['latin_s1']) ? '<i>' . $t['properties']['latin_s1'] . '</i>' : ''
            ]));
        }

        // 處理 sub layers
        $layers = '';
        if (!empty($t['species_layers']) && is_array($t['species_layers'])) {
            $layerParts = [];
            $totalLayers = count($t['species_layers']);
            
            foreach ($t['species_layers'] as $index => $layer) {
                // 動物不用 s2rank (只要 s2latinName)
                if ($index === 0 && ($t['nomenclature']['group'] ?? '') === 'animal') {
                    $layerParts[] = !empty($layer['latin_name']) ? '<i>' . $layer['latin_name'] . '</i>' : '';
                } else {
                    $layerSubParts = [];
                    
                    if (!empty($layer['rank']['abbreviation'])) {
                        $layerSubParts[] = $layer['rank']['abbreviation'];
                    }

                    // 在最後一層插入指示
                    if ($index === $totalLayers - 1) {
                        $layerSubParts[] = $indication;
                    }

                    if (!empty($layer['latin_name'])) {
                        $layerSubParts[] = '<i>' . $layer['latin_name'] . '</i>';
                    }

                    $layerParts[] = implode(' ', $layerSubParts);
                }
            }
            
            $layers = implode(' ', array_filter($layerParts));
        }

        return trim(implode(' ', array_filter([$prevName, $layers])));
    }
}