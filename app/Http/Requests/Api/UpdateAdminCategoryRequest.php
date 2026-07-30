<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminCategoryRequest extends FormRequest
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
            'name' => 'required|min:3|max:255',
            'slug' => 'required|min:3|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'required|min:3|max:255',
            'image' => 'nullable|image|max:1024',
        ];
    }
}
