<?php

namespace App\Http\Requests;

use App\Rules\isBase64Image;
use Illuminate\Foundation\Http\FormRequest;

class ReferenceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'required|Integer|not_in:0',
            'authors' => 'required|array|min:1|exists:persons,id',
            'image' => ['nullable', new isBase64Image],
            'publish_year' => 'required|regex:/[1-2][0-9]{3}/',
            'properties.check_list_type' => 'required_if:type,5|nullable|Integer|not_in:0',
            'properties.volume' => 'required_if:type,1|nullable|regex:/^[0-9a-zA-Z]+/',
            'properties.book_title' => 'required_if:type,1|required_if:type,2|required_if:type,5|required_if:type,3',
            'properties.pages_range' => 'nullable|regex:/^[0-9\-–,]+$/u',
            'properties.edition' => 'nullable',
            'properties.chapter' => 'nullable|regex:/^[0-9a-zA-Z]+/',
            'properties.url' => 'nullable|url',
        ];
    }

    public function messages()
    {
        return [
            'min' => 'reference.min', //'必填',
            'not_in' => 'reference.not_in', //'必填',
            'required' => 'reference.required', //'必填',
            'required_if' => 'reference.required_if', //'必填',
            'integer' => 'reference.integer', //'須為數字',
            'properties.volume.regex' => 'reference.properties.volume.regex', //'只允許「數字」、「英文」',
            'properties.chapter.regex' => 'reference.properties.chapter.regex', //'只允許「數字」、「英文」',
            'properties.pages_range.regex' => 'reference.properties.pages_range.regex', //'只允許「數字」、「–」、「,」',
            'regex' => 'reference.regex', //'格式不符',
            'image' => 'reference.image', //'格式不符 (jpg, jpeg, png)'
            'authors.exists' => 'reference.authors.exists', //'作者不存在,
            'properties.url.url' => 'reference.properties.url', //'網址格式不符',
        ];
    }
}
