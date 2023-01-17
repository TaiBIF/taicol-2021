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
            'properties.volume' => 'required_if:type,1|nullable|regex:/^[0-9a-zA-Z]+/',
            'properties.book_title' => 'required_if:type,1|required_if:type,2|required_if:type,3',
            'properties.pages_range' => 'nullable|regex:/^[0-9\-–,]+$/u',
            'properties.edition' => 'nullable',
            'properties.chapter' => 'nullable|regex:/^[0-9a-zA-Z]+/',
            'properties.url' => 'nullable|url',
        ];
    }

    public function messages()
    {
        return [
            'min' => '必填',
            'not_in' => '必填',
            'required' => '必填',
            'required_if' => '必填',
            'integer' => '須為數字',
            'properties.volume.regex' => '只允許「數字」、「英文」',
            'properties.chapter.regex' => '只允許「數字」、「英文」',
            'properties.pages_range.regex' => '只允許「數字」、「–」、「,」',
            'regex' => '格式不符',
            'image' => '格式不符 (jpg, jpeg, png)'
        ];
    }
}
