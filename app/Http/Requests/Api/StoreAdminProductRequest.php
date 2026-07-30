<?php

namespace App\Http\Requests\Api;

use App\Services\AdminProductService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminProductRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'slug' => 'required|string|max:255|unique:products,slug',
            'short_description' => 'required|string|max:255',
            'long_description' => 'required|string|max:5000',
            'stock_status' => 'nullable|boolean',
            'quantity' => [
                'nullable', 'numeric', 'min:0', function (string $attribute, mixed $value, Closure $fail) {
                    if ((bool) $this->input('stock_status') === false && $value > 0) {
                        $fail(__('The quantity must not be greater than 0 if the stock status is false.'));
                    }
                }, function (string $attribute, mixed $value, Closure $fail) {
                    if ((bool) $this->input('stock_status') === true && $value <= 0) {
                        $fail(__('The quantity must be greater than 0 if the stock status is true.'));
                    }
                },
            ],
            'delivery_time' => 'nullable|numeric|min:0',
            'customer_can_add_review' => 'required|boolean',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'published' => 'required|boolean',
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|max:5120',
            'files' => 'required|array|min:1|max:20',
            'files.*.path' => 'required|string',
            'files.*.name' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    $extension = strtolower(pathinfo((string) $value, PATHINFO_EXTENSION));
                    if (! in_array($extension, AdminProductService::ALLOWED_EXTENSIONS, true)) {
                        $fail(__('The file extension is not allowed.'));
                    }
                },
            ],
            'files.*.mime_type' => 'required|string',
            'files.*.size' => 'required|string',
            'tags' => 'required|array|min:1',
            'tags.*' => 'required|exists:tags,id',
            'attributes' => 'required|array|min:1',
            'attributes.*.id' => 'required|exists:attributes,id',
            'attributes.*.value' => 'required|string|max:255',
            'meta_description' => 'required|string|max:255',
            'meta_keywords' => 'required|string|max:255',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['tags', 'attributes', 'files', 'stock_status', 'published', 'customer_can_add_review'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $decoded = json_decode($this->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$field => $decoded]);
                }
            }
        }
    }
}
