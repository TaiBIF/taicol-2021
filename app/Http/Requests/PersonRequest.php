<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PersonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'last_name' => 'required',
            'first_name' => 'required',
            'year_of_birth' => 'nullable|digits:4|integer|min:1100',
            'year_of_death' => 'nullable|digits:4|integer|min:1100',
            'biology_departments' => 'required|array',
            'original_full_name' => 'required',
            'abbreviation_name' => [
                Rule::requiredIf(in_array('plantae', $this->get('biology_departments', [])))
            ]
        ];
    }

    public function messages()
    {
        return [
            'required' => 'person.required',
            'min' => 'person.min',
            'digits' => 'person.digits',
            'integer' => 'person.integer',
        ];
    }
}
