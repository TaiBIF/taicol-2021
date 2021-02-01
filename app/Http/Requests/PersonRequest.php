<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'year_of_publication' => 'nullable|digits:4|integer|min:1900',
            'biology_departments' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'required' => '必填',
            'min' => '最少 1100 年後',
            'digits' => '只能為西元年',
            'integer' => '只能為數字',
        ];
    }
}
