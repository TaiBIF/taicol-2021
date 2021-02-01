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

        return [
            'nomenclature_id' => 'required|integer|exists:nomenclatures,id',
            'rank_id' => 'required|integer|exists:ranks,id',
            'authors' => 'required_if:nomenclature_id,2|exists:persons,id|array',
            'ex_authors' => 'array',
            'is_hybrid_formula' => 'boolean|',
            'hybrid_parents.0' => 'required_if:is_hybrid_formula,true',
            'hybrid_parents.1' => 'required_if:is_hybrid_formula,true',
            'latin_genus' => [
                Rule::requiredIf(function () use ($rank, $speciesRank) {
                    return $rank && $rank->order <= $speciesRank->order;
                }),
            ],
            'latin_s1' => [
                Rule::requiredIf(function () use ($rank, $speciesRank) {
                    return $rank && $rank->order == $speciesRank->order;
                }),
            ],
            'species_id' => [
                Rule::requiredIf(function () use ($rank, $speciesRank) {
                    return $rank && $rank->order > $speciesRank->order;
                }),
                'exists:taxon_names,id'
            ],
            'species_layers' => 'array',
            'species_layers.*.latin_name' => 'required',
            'species_layers.*.rank_abbreviation' => 'exists:ranks,abbreviation|required',
            'type_specimens' => 'array',
            'type_specimens.*.use' => 'required',
            'type_specimens.*.kind' => 'required|integer',
            'type_specimens.*.country' => 'required_if:type_specimens.*.kind,1',
            'type_specimens.*.specimens.*.herbarium' => 'required|min:1',
            'type_specimens.*.collection_year' => 'max:4',
            'type_specimens.*.collection_month' => 'max:2',
            'type_specimens.*.collection_day' => 'max:2',
            'type_specimens.*.collectors' => 'required_if:type_specimens.*.kind,1|array|exists:persons,id',
            'type_specimens.*.isotypes.*.herbarium' => 'required',
            'publish_year' => 'required',

            'reference_name' => 'required',
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
