<?php

namespace App\Services;

use App\Jobs\DownloadFileJob;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AvatarService
{
    /**
     * @return array{id: int, name: string, slug: string}
     */
    private function avatarCategory(): array
    {
        $category = Category::firstOrCreate(
            ['slug' => 'avatar'],
            ['name' => 'Avatars', 'slug' => 'avatar']
        );

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ];
    }

    public function paginateForUser(User $user, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $category = $this->avatarCategory();

        return $user->products()
            ->where('category_id', $category['id'])
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->whereHas('files')
            ->whereHas('latestImage')
            ->with(['files', 'latestImage'])
            ->orderByDesc('products.created_at')
            ->paginate($perPage);
    }

    /**
     * @return array{product: Product, message: string}
     */
    public function store(User $user, string $name, string $avatarUrl, string $avatarImageUrl): array
    {
        $category = $this->avatarCategory();

        $product = Product::create([
            'category_id' => $category['id'],
            'sku' => $this->generateSku(),
            'name' => $name,
            'slug' => Str::slug($name),
            'price' => 0,
            'published' => true,
            'created_by' => 'user',
        ]);

        DownloadFileJob::dispatch($avatarImageUrl, $product, 'image');
        DownloadFileJob::dispatch($avatarUrl, $product, 'file');

        $randomTag = Tag::inRandomOrder()->first();
        if ($randomTag) {
            $product->tags()->attach($randomTag);
        }

        $randomAttribute = Attribute::inRandomOrder()->first();
        if ($randomAttribute) {
            $product->attributes()->attach($randomAttribute, ['value' => 'Custom Value']);
        }

        $user->products()->attach($product);

        return [
            'product' => $product,
            'message' => 'Avatar created successfully.',
        ];
    }

    private function generateSku(): string
    {
        $lastSku = Product::query()->select('sku')->orderByDesc('id')->value('sku');

        if ($lastSku) {
            $parts = explode('-', $lastSku);

            return '3D-rgb-'.((int) end($parts) + 1);
        }

        return '3D-rgb-10000';
    }
}
