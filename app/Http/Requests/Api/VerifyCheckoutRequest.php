<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCheckoutRequest extends FormRequest
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
        return [
            'Token' => ['required'],
            'status' => ['nullable'],
            'OrderId' => ['nullable'],
            'TerminalNo' => ['nullable'],
            'RRN' => ['nullable'],
            'HashCardNumber' => ['nullable'],
            'Amount' => ['nullable'],
        ];
    }
}
