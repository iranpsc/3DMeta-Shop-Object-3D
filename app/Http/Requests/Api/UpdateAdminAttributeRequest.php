<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $attribute = $this->route('attribute');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes', 'name')->ignore($attribute),
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes', 'slug')->ignore($attribute),
            ],
        ];
    }
}
