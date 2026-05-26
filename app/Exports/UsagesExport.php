<?php

namespace App\Exports; 

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\MyNamespaceUsage;
use App\TaxonName;
use App\Reference;
use App\Person;
use App\Http\Services\UsagePreviewService;
use App\Http\Resources\TaxonNameSimpleSubResource;
use App\Http\Resources\PersonCollection;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UsagesExport implements FromArray, WithHeadings
{

    function toStringSafe($arr, $key) {
        return isset($arr[$key]) ? strval($arr[$key]) : '';
    }


    private $languageMapping = [
        'en-us' => '英文',
        'zh-tw' => '繁體中文',
        'jp-jp' => '日文',
        'zh-cn' => '簡體中文',
        'de-de' => '德文',
        'fr-fr' => '法文',
        'lat' => '拉丁文',
        'others' => '其他',
    ];

    protected $namespaceId;

    public function __construct($namespaceId)
    {
        $this->namespaceId = $namespaceId;
    }

    public function headings(): array
    {
        return ['nomenclature','rank','name','authors','parant_taxon','usage_status','is_title','is_indent','indications',
                'usage_references','common_name','is_in_taiwan','distribution_in_tw','is_endemic','alien_type',
                'is_fossil','is_terrestrial','is_freshwater','is_brackish','is_marine','alien_status_note','is_new_record',
                'description','diagnosis','distribution','etymology','habitat','substrata','measurements','coloration',
                'other_examined_material','custom_field1','custom_field2','custom_field3','custom_field4','custom_field5','note',
                'usage_references_text','type_specimens'];
    }

    public function array(): array
    {
        // 初始化 UsagePreviewService
        $service = new UsagePreviewService();

        $usages = MyNamespaceUsage::with([
            'parent',
            'taxonName.nomenclature',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
            'namespace'
        ])
            ->where('namespace_id', $this->namespaceId)
            ->orderBy('group')->orderBy('order')
            ->get()
            ->map(function ($usage) use ($service) {

            $taxonName = $usage->taxonName;

            $parentTaxonName = $usage->parent_taxon_name_id ? TaxonName::find([$usage->parent_taxon_name_id])[0] : null;
            
            $perUsages = empty($usage->per_usages)
                ? []
                : collect($usage->per_usages)->map(function($per) {
                    return 
                        ($per['reference_id'] ?? '') . ',' .
                        ($per['show_page'] ?? '') . ',' .
                        (isset($per['figure']) ? (strpos($per['figure'], ',') !== false ? "\"{$per['figure']}\"" : $per['figure']) : '') . ',' .
                        (!empty($per['pro_parte']) ? 'true' : '')
                    ;
                })->toArray();

            $commonNames = empty($usage->properties['common_names'])
                ? []
                : collect($usage->properties['common_names'])->map(function($commonName) {
                    return 
                        ($commonName['name'] ?? '') . '(' .
                        (!empty($commonName['language']) ? $this->languageMapping[$commonName['language']] : '') . ',' .
                        ($commonName['area'] ?? '') . ')'
                    ;
                })->toArray();

            $additionalFields = array();
            if (!empty($usage->properties['additional_fields'])){
                foreach ($usage->properties['additional_fields'] as $item) {
                    $additionalFields[$item['field_name']] = $item['field_value'];
                }
            }

            $customFields = []; // 儲存 custom_field1 ~ 5
            if (!empty($usage->properties['custom_fields'])){

                foreach ($usage->properties['custom_fields'] as $index => $item) {
                    if ($index >= 5) break; // 最多只取前 5 筆

                    $formatted = "{$item['field_name_en']}:{$item['field_value']}";

                    $customFields["custom_field" . ($index + 1)] = $formatted;
                }
            }

            // 處理 type_name
            $typeName = ($usage->properties['type_name'] ?? '') ? TaxonNameSimpleSubResource::collection([
                TaxonName::with([
                    'authors',
                    'exAuthors',
                    'reference',
                    'nomenclature',
                    'originalTaxonName.authors',
                    'originalTaxonName.exauthors'
                ])->find((int) $usage->properties['type_name'])
            ])[0] : null;

            // 使用 UsagePreviewService 處理
            $usageReferencesResult = $service->process(
                TaxonNameSimpleSubResource::collection([$usage->taxonName])[0],
                $usage->properties['indications'] ?? null, 
                collect($usage->per_usages)->map(function ($r) {
                    $r['target'] = isset($r['reference_id']) ? Reference::with('authors')->find($r['reference_id']) : null;
                    return $r;
                }), 
                collect($usage->type_specimens)->map(function ($t) {
                        $t['collectors'] = PersonCollection::collection(Person::whereIn('id', $t['collector_ids'] ?? [])->get());
                        return $t;
                    }) ?? [], 
                $usage->status, 
                false,  
                $typeName
            );

            // 取出 per_usages 和 type_specimens
            $usage_references_text = $usageReferencesResult['per_usages'] ?? '';
            $type_specimens = $usageReferencesResult['type_specimens'] ?? '';

            // 移除 HTML 標籤
            $usage_references_text = html_entity_decode(strip_tags($usage_references_text));
            $type_specimens = html_entity_decode(strip_tags($type_specimens));

            return [
                $taxonName->nomenclature->name,
                $taxonName->rank->key,
                $taxonName->name,
                DB::table('api_names')->where('taxon_name_id', $usage->taxon_name_id)->value('name_author') ?? '',
                $parentTaxonName->name ?? '',
                $usage->status,
                $usage->is_title ? '1' : '0',
                $usage->is_indent ? '1' : '0',
                isset($usage->properties['indications']) ? implode('|', $usage->properties['indications']) : '', 
                implode('|', $perUsages),
                implode('|', $commonNames),
                $this->toStringSafe($usage->properties, 'is_in_taiwan'),
                $usage->properties['distribution_in_tw'] ?? '',
                $this->toStringSafe($usage->properties, 'is_endemic'),
                $usage->properties['alien_type'] ?? '',
                $this->toStringSafe($usage->properties, 'is_fossil'),
                $this->toStringSafe($usage->properties, 'is_terrestrial'),
                $this->toStringSafe($usage->properties, 'is_freshwater'),
                $this->toStringSafe($usage->properties, 'is_brackish'),
                $this->toStringSafe($usage->properties, 'is_marine'),
                $usage->properties['alien_status_note'] ?? '',
                $this->toStringSafe($usage->properties, 'is_new_record'),
                $additionalFields['description'] ?? '',
                $additionalFields['diagnosis'] ?? '',
                $additionalFields['distribution'] ?? '',
                $additionalFields['etymology'] ?? '',
                $additionalFields['habitat'] ?? '',
                $additionalFields['substrata'] ?? '',
                $additionalFields['measurements'] ?? '',
                $additionalFields['coloration'] ?? '',
                $additionalFields['otherExaminedMaterial'] ?? '',
                $customFields['custom_field1'] ?? '',
                $customFields['custom_field2'] ?? '',
                $customFields['custom_field3'] ?? '',
                $customFields['custom_field4'] ?? '',
                $customFields['custom_field5'] ?? '',
                $usage->properties['note'] ?? '',
                $usage_references_text,
                $type_specimens,
            ];
        });


        return $usages->toArray();
    }
}