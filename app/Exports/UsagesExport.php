<?php

namespace App\Exports; 

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\MyNamespaceUsage;
use App\TaxonName;
use Illuminate\Support\Facades\Log;

class UsagesExport implements FromArray, WithHeadings
{


    private function extractSplitCleanTexts($html)
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);
        $specialSpans = $xpath->query('//span[@data-v-e95d815c=""]');

        if ($specialSpans->length === 0) {
            return [strip_tags($html), ''];
        }

        // 取出 span 的文字（第二段）
        $secondPartHtml = $dom->saveHTML($specialSpans->item(0));
        $secondPart = strip_tags($secondPartHtml);

        // 把 span 從 DOM 中移除
        $specialSpans->item(0)->parentNode->removeChild($specialSpans->item(0));

        // 再抓整個 <div> 中剩下的 HTML
        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $firstPartHtml = '';
        foreach ($wrapper->childNodes as $child) {
            $firstPartHtml .= $dom->saveHTML($child);
        }

        $firstPart = strip_tags($firstPartHtml);

        return [trim($firstPart), trim($secondPart)];
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
                'usage_references_text', 'type'];
    }

    public function array(): array
    {

        $usages = MyNamespaceUsage::where('namespace_id', $this->namespaceId)->get()->map(function ($usage) {

            $taxonName = TaxonName::find($usage->taxon_name_id);
            $parentTaxonName = TaxonName::find($usage->parent_taxon_name_id);
            
            $perUsages = empty($usage->per_usages)
                ? []
                : collect($usage->per_usages)->map(function($per) {
                    return 
                        ($per['reference_id'] ?? '') . ',' .
                        ($per['show_page'] ?? '') . ',' .
                        ($per['figure'] ?? '') . ',' .
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

            if (isset($usage->name_remark)) {
                $res = $this->extractSplitCleanTexts($usage->name_remark);
                $usage_references_text =  $res[0];
                $type =  $res[1];
            }

            return [
                $taxonName->nomenclature->name,
                $taxonName->rank->key,
                $taxonName->name,
                $taxonName->formatted_authors,
                $parentTaxonName->name ?? '',
                $usage->status,
                $usage->is_title ? 1 : 0,
                $usage->is_indent ? 1 : 0,
                isset($usage->properties['indications']) ? implode('|', $usage->properties['indications']) : '', 
                implode('|', $perUsages),
                implode('|', $commonNames),
                $usage->properties['is_in_taiwan'] ?? '',
                $usage->properties['distribution_in_tw'] ?? '',
                $usage->properties['is_endemic'] ?? '',
                $usage->properties['alien_type'] ?? '',
                $usage->properties['is_fossil'] ?? '',
                $usage->properties['is_terrestrial'] ?? '',
                $usage->properties['is_freshwater'] ?? '',
                $usage->properties['is_brackish'] ?? '',
                $usage->properties['is_marine'] ?? '',
                $usage->properties['alien_status_note'] ?? '',
                $usage->properties['is_new_record'] ?? '',
                $additionalFields['description'] ?? '',
                $additionalFields['diagnosis'] ?? '',
                $additionalFields['distribution'] ?? '',
                $additionalFields['etymology'] ?? '',
                $additionalFields['habitat'] ?? '',
                $additionalFields['substrata'] ?? '',
                $additionalFields['measurements'] ?? '',
                $additionalFields['coloration'] ?? '',
                $additionalFields['other_examined_material'] ?? '',
                $customFields['custom_field1'] ?? '',
                $customFields['custom_field2'] ?? '',
                $customFields['custom_field3'] ?? '',
                $customFields['custom_field4'] ?? '',
                $customFields['custom_field5'] ?? '',

                $usage->properties['note'] ?? '',
                $usage_references_text ?? '',
                $type ?? '',

            ];
        });


        return $usages->toArray();
    }
}
