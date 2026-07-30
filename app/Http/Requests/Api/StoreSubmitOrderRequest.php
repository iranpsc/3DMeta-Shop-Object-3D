<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmitOrderRequest extends FormRequest
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
        $guest = ! $this->user();

        return [
            'name' => [
                'nullable',
                Rule::requiredIf($guest),
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                Rule::requiredIf($guest),
                'string',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                Rule::requiredIf($guest),
                'string',
                'max:255',
            ],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:1024'],
        ];
    }
}
