<?php

namespace App\Http\Services;

use App\TaxonName;
use Illuminate\Support\Facades\DB;

class ParentTaxonService
{
    /**
     * 解析單一 taxon_name 的 parent
     */
    public function resolveOne(int $taxonNameId, ?int $userProvided = null): ?int
    {
        if ($userProvided) {
            return $userProvided;
        }

        $parent = $this->findFromReferenceUsages([$taxonNameId])[$taxonNameId] ?? null;
        if ($parent) {
            return $parent;
        }

        $nowName = TaxonName::find($taxonNameId);
        if (!$nowName || $nowName->nomenclature_id == 4) {
            return null;
        }

        return $this->fallbackByProperties($nowName);
    }

    /**
     * 批次解析多個 taxon_name 的 parent
     *
     * @param array $taxonNameIds
     * @param array $userProvidedMap [taxonNameId => parentId] 使用者已指定的對應
     * @return array [taxonNameId => parentId|null]
     */
    public function resolveMany(array $taxonNameIds, array $userProvidedMap = []): array
    {
        $taxonNameIds = array_values(array_unique(array_map('intval', $taxonNameIds)));
        $result = [];
        $needResolve = [];

        foreach ($taxonNameIds as $id) {
            if (!empty($userProvidedMap[$id])) {
                $result[$id] = $userProvidedMap[$id];
            } else {
                $needResolve[] = $id;
            }
        }

        if (empty($needResolve)) {
            return $result;
        }

        // 第一步：從 reference_usages 排序撈
        $fromUsages = $this->findFromReferenceUsages($needResolve);
        $stillNeedFallback = [];
        foreach ($needResolve as $id) {
            if (!empty($fromUsages[$id])) {
                $result[$id] = $fromUsages[$id];
            } else {
                $stillNeedFallback[] = $id;
            }
        }

        // 第二步：剩下的用 properties fallback
        if (!empty($stillNeedFallback)) {
            $fallback = $this->fallbackByPropertiesBatch($stillNeedFallback);
            foreach ($stillNeedFallback as $id) {
                $result[$id] = $fallback[$id] ?? null;
            }
        }

        return $result;
    }

    /**
     * 從 reference_usages 撈各 taxon 的最佳 parent（一次撈多筆）
     */
    private function findFromReferenceUsages(array $taxonNameIds): array
    {
        if (empty($taxonNameIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($taxonNameIds), '?'));

        $sql = "
            SELECT taxon_name_id, parent_taxon_name_id FROM (
                SELECT
                    ru.taxon_name_id,
                    ru.parent_taxon_name_id,
                    ROW_NUMBER() OVER (
                        PARTITION BY ru.taxon_name_id
                        ORDER BY
                            CASE r.type WHEN 6 THEN 1 WHEN 1 THEN 2 WHEN 2 THEN 2 WHEN 3 THEN 2 WHEN 5 THEN 3 WHEN 4 THEN 4 ELSE 5 END ASC,
                            r.publish_year DESC,
                            CASE ru.status WHEN 'accepted' THEN 1 WHEN 'not-accepted' THEN 2 WHEN 'misapplied' THEN 3 ELSE 4 END ASC
                    ) AS rn
                FROM reference_usages ru
                JOIN `references` r ON ru.reference_id = r.id
                WHERE ru.taxon_name_id IN ($placeholders)
            ) t
            WHERE rn = 1
        ";

        $rows = DB::select($sql, $taxonNameIds);
        $map = [];
        foreach ($rows as $row) {
            if (!empty($row->parent_taxon_name_id)) {
                $map[(int) $row->taxon_name_id] = (int) $row->parent_taxon_name_id;
            }
        }
        return $map;
    }

    /**
     * 單筆 fallback：靠 properties 找
     */
    private function fallbackByProperties(TaxonName $nowName): ?int
    {
        $nomenclatureId = $nowName->nomenclature_id;
        $speciesLayer = $nowName->properties['species_layers'] ?? [];

        if (count($speciesLayer) == 1) {
            return $nowName->properties['species_id'] ?? null;
        }

        $parentNameString = $this->buildParentNameString($nowName, $speciesLayer);
        if (!$parentNameString) {
            return null;
        }

        // limit(2) 是為了區分「唯一」和「多筆同名」
        $candidates = TaxonName::where('name', $parentNameString)
            ->where('nomenclature_id', $nomenclatureId)
            ->limit(2)
            ->pluck('id');

        return $candidates->count() === 1 ? (int) $candidates->first() : null;
    }

    /**
     * 批次 fallback：靠 properties 找
     */
    private function fallbackByPropertiesBatch(array $taxonNameIds): array
    {
        $names = TaxonName::whereIn('id', $taxonNameIds)->get()->keyBy('id');

        $result = [];
        $nameLookupNeeded = [];           // [taxonNameId => ['name'=>..., 'nomenclature_id'=>...]]
        $pendingByNomenclature = [];      // [nomenclatureId => [names]]

        foreach ($taxonNameIds as $id) {
            $nowName = $names[$id] ?? null;
            if (!$nowName || $nowName->nomenclature_id == 4) {
                $result[$id] = null;
                continue;
            }

            $speciesLayer = $nowName->properties['species_layers'] ?? [];

            if (count($speciesLayer) == 1) {
                $result[$id] = $nowName->properties['species_id'] ?? null;
                continue;
            }

            $parentNameString = $this->buildParentNameString($nowName, $speciesLayer);
            if ($parentNameString) {
                $nameLookupNeeded[$id] = [
                    'name' => $parentNameString,
                    'nomenclature_id' => $nowName->nomenclature_id,
                ];
                $pendingByNomenclature[$nowName->nomenclature_id][] = $parentNameString;
            } else {
                $result[$id] = null;
            }
        }

        // 批次撈名稱比對結果，順便 group 起來判斷重複
        $nameMap = []; // ["nomenclature|name" => ['count'=>n, 'id'=>x]]
        foreach ($pendingByNomenclature as $nomenclatureId => $namesList) {
            $rows = DB::table('taxon_names')
                ->select('name', DB::raw('COUNT(*) as cnt'), DB::raw('MIN(id) as id'))
                ->whereIn('name', array_values(array_unique($namesList)))
                ->where('nomenclature_id', $nomenclatureId)
                ->groupBy('name')
                ->get();

            foreach ($rows as $row) {
                $nameMap[$nomenclatureId . '|' . $row->name] = [
                    'count' => (int) $row->cnt,
                    'id' => (int) $row->id,
                ];
            }
        }

        // 只有 count == 1 才採用
        foreach ($nameLookupNeeded as $id => $info) {
            $key = $info['nomenclature_id'] . '|' . $info['name'];
            $hit = $nameMap[$key] ?? null;
            $result[$id] = ($hit && $hit['count'] === 1) ? $hit['id'] : null;
        }

        return $result;
    }

    /**
     * 組出種下下 / 種的 parent 名稱字串
     */
    private function buildParentNameString(TaxonName $nowName, array $speciesLayer): ?string
    {
        if (count($speciesLayer) == 2) {
            return $nowName->properties['latin_genus'] . ' ' . $nowName->properties['latin_s1']
                . ' ' . $speciesLayer[0]['rank_abbreviation'] . ' ' . $speciesLayer[0]['latin_name'];
        }
        if ($nowName->rank_id == 34) {
            return $nowName->properties['latin_genus'];
        }
        return null;
    }
}