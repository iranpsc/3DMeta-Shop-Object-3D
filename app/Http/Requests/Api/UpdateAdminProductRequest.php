<?php

namespace App\Http\Requests\Api;

use App\Models\Product;
use App\Services\AdminProductService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminProductRequest extends FormRequest
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
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,'.$product->id,
            'slug' => 'required|string|max:255|unique:products,slug,'.$product->id,
            'short_description' => 'required|string|max:500',
            'long_description' => 'required|string|max:5000',
            'stock_status' => 'required|boolean',
            'quantity' => [
                'required', 'numeric', 'min:0', function (string $attribute, mixed $value, Closure $fail) {
                    if ((bool) $this->input('stock_status') === false && $value > 0) {
                        $fail(__('The quantity must not be greater than 0 if the stock status is false.'));
                    }
                }, function (string $attribute, mixed $value, Closure $fail) {
                    if ((bool) $this->input('stock_status') === true && $value <= 0) {
                        $fail(__('The quantity must be greater than 0 if the stock status is true.'));
                    }
                },
            ],
            'delivery_time' => 'required|numeric|min:0',
            'customer_can_add_review' => 'required|boolean',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'published' => 'required|boolean',
            'images' => 'nullable|array|max:3',
            'images.*' => 'nullable|image|max:1024',
            'files' => 'nullable|array|max:20',
            'files.*.path' => 'required_with:files|string',
            'files.*.name' => [
                'required_with:files',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    $extension = strtolower(pathinfo((string) $value, PATHINFO_EXTENSION));
                    if (! in_array($extension, AdminProductService::ALLOWED_EXTENSIONS, true)) {
                        $fail(__('The file extension is not allowed.'));
                    }
                },
            ],
            'files.*.mime_type' => 'required_with:files|string',
            'files.*.size' => 'required_with:files|string',
            'tags' => 'required|array|min:1',
            'tags.*' => 'required|exists:tags,id',
            'attributes' => 'required|array|min:1',
            'attributes.*.id' => 'required|exists:attributes,id',
            'attributes.*.value' => 'required|string|max:255',
            'meta_description' => 'required|string|max:500',
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
