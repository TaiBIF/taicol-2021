<?php

namespace App\Http\Requests;

use App\Rank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxonNameRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rank = Rank::find($this->get('rank_id'));

        // 物種階層
        $speciesRank = Rank::where('key', 'species')->first();
        $hybridParents = array_filter($this->get('hybrid_parents'));
        $id = $this->get('id');

        return [
            'nomenclature_id' => 'required|integer|exists:nomenclatures,id',
            'rank_id' => 'required|integer|exists:ranks,id',
            'authors' => 'exists:persons,id|array',
            'ex_authors' => 'array',
            'is_hybrid_formula' => 'boolean|',
            'hybrid_parents.0' => [
                Rule::requiredIf($rank && $rank->key === 'hybrid-formula'),
                Rule::requiredIf(count($hybridParents) === 1),
                function ($attribute, $value, $fail) use ($id, $hybridParents) {
                    if ($id === ($value['id'] ?? '')) {
                        $fail('不能為自己');
                    }
                },
            ],
            'hybrid_parents.1' => [
                Rule::requiredIf($rank && $rank->key === 'hybrid-formula'),
                Rule::requiredIf(count($hybridParents) === 1),
                function ($attribute, $value, $fail) use ($id, $hybridParents) {
                    if ($id === ($value['id'] ?? '')) {
                        $fail('不能為自己');
                    }
                },
            ],
            'latin_name' => [
                Rule::requiredIf(function () use ($rank, $speciesRank) {
                    return $rank && $rank->order < $speciesRank->order;
                }),
            ],
            'latin_genus' => [
                Rule::requiredIf(function () use ($rank, $speciesRank) {
                    return $rank && $rank->order == $speciesRank->order;
                }),
            ],
            'latin_s1' => [
                Rule::requiredIf(function () use ($rank, $speciesRank) {
                    return $rank && $rank->order == $speciesRank->order;
                }),
            ],
            'species_id' => [
                Rule::requiredIf(function () use ($rank, $speciesRank) {
                    return $rank && $rank->order > $speciesRank->order
                        && $rank->key != 'hybrid-formula';
                }),
                'exists:taxon_names,id'
            ],
            'species_layers' => 'array',
            'species_layers.*.latin_name' => 'required',
            'species_layers.*.rank_abbreviation' => 'required|exists:ranks,abbreviation',
            'type_specimens' => 'array',
            'type_specimens.*.use' => 'required',
            'type_specimens.*.kind' => 'required|integer',
            'type_specimens.*.country' => 'required_if:type_specimens.*.kind,1',
            'type_specimens.*.specimens.*.herbarium' => 'required_if:type_specimens.*.kind,1',
            'type_specimens.*.collection_year' => 'max:4',
            'type_specimens.*.collection_day' => 'max:2',
            'type_specimens.*.collectors' => 'required_if:type_specimens.*.kind,1|array',
            'type_specimens.*.collectors.*.id' => 'exists:persons,id',
            'type_specimens.*.isotypes.*.herbarium' => 'required',
            'publish_year' => 'nullable|integer',

            'usage.show_page' => [
                Rule::requiredIf(function() {
                    $usage = $this->get('usage');
                    return isset($usage['reference_id']);
                }),
                'integer',
                'nullable'
            ],
        ];
    }

    public function attributes()
    {
        return [
            'reference_name' => '文獻',
            'usage.show_page' => '學名出現頁碼',
        ];
    }

    public function messages()
    {
        return [
            'min' => '必填',
            'integer' => '須為數字',
            'not_in' => '必填',
            'required' => '必填',
            'required_if' => '必填',
            'required_without' => '必填',
        ];
    }
}
