<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class AdminCategoryService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Category::with('parent')->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Category::with('children')->get();
    }

    public function find(int $id): Category
    {
        return Category::with('parent', 'image')->findOrFail($id);
    }

    /**
     * @param  array{name: string, slug: string, parent_id?: int|null, description: string}  $data
     */
    public function store(array $data, ?UploadedFile $image = null): Category
    {
        Gate::authorize('create', Category::class);

        $category = Category::create([
            'name' => $data['name'],
            'slug' => str_replace(' ', '-', trim($data['slug'])),
            'parent_id' => $data['parent_id'] ?? null,
            'description' => $data['description'],
        ]);

        if ($image) {
            $category->image()->create([
                'path' => $image->store('/categories', 'public'),
            ]);
        }

        return $category->load('parent', 'image');
    }

    /**
     * @param  array{name: string, slug: string, parent_id?: int|null, description: string}  $data
     */
    public function update(Category $category, array $data, ?UploadedFile $image = null): Category
    {
        Gate::authorize('update', $category);

        $category->update([
            'name' => $data['name'],
            'slug' => str_replace(' ', '-', trim($data['slug'])),
            'parent_id' => $data['parent_id'] ?? null,
            'description' => $data['description'],
        ]);

        if ($image) {
            $category->image()?->delete();
            $category->image()->create([
                'path' => $image->store('/categories', 'public'),
            ]);
        }

        return $category->load('parent', 'image');
    }

    public function delete(Category $category): void
    {
        Gate::authorize('delete', $category);

        $category->delete();
    }
}
