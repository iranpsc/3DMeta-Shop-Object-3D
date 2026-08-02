<?php

namespace App\Http\Requests\Api;

use App\Rules\SecureFile;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'message' => ['required', 'string', 'min:3', 'max:5000'],
            'priority' => ['required', 'in:low,medium,high'],
            'attachment' => ['nullable', 'file', 'max:1024', new SecureFile(['pdf', 'jpg', 'jpeg', 'png'])],
        ];
    }
}
