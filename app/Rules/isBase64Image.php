<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class isBase64Image implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $extension = explode('/', mime_content_type($value))[1];

        return $extension == 'png' || $extension == 'jpg' || $extension == 'jpeg';
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '格式不符合 (jpg, jpeg, png)';
    }
}
