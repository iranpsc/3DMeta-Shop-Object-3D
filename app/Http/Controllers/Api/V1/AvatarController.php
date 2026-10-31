<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvatarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $avatars = $this->query($request->query('category'))
            ->with(['category', 'oldestImage'])
            ->paginate(min(max((int) $request->query('limit', 15), 1), 100));

        $avatars->getCollection()->transform(fn (Product $avatar) => $this->summary($avatar));

        return response()->json(['data' => $avatars]);
    }

    public function show(int $avatar): JsonResponse
    {
        $product = $this->query()->with(['category', 'oldestImage', 'files', 'tags', 'attributes'])->findOrFail($avatar);

        $file = $product->files->first();

        return response()->json(['data' => array_merge($this->summary($product), [
            'format' => $file ? strtolower(pathinfo($file->name, PATHINFO_EXTENSION)) : null,
            'file_size' => $file?->size,
            'rigging' => $product->attributes->firstWhere('slug', 'rigging')?->pivot?->value,
            'attributes' => $product->attributes->map(fn ($attribute) => [
                'id' => (string) $attribute->id,
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'value' => $attribute->pivot->value,
            ])->values(),
            'tags' => $product->tags->pluck('slug')->values(),
            'files' => $product->files->map(fn ($item) => [
                'name' => $item->name,
                'size' => $item->size,
                'format' => strtolower(pathinfo($item->name, PATHINFO_EXTENSION)),
            ])->values(),
        ])]);
    }

    public function download(int $avatar): BinaryFileResponse
    {
        $file = $this->query()->findOrFail($avatar)->files()->first();

        abort_if(!$file || !Storage::disk('local')->exists($file->path), 404, 'Avatar model not found.');

        return response()->download(Storage::disk('local')->path($file->path), $file->name);
    }

    private function query(?string $category = null)
    {
        return Product::query()
            ->whereDoesntHave('users')
            ->whereHas('category', function ($query) use ($category) {
                $query->where('slug', $category ?: 'avatar')
                    ->when(!$category, fn ($query) => $query->orWhereHas('parent', fn ($parent) => $parent->where('slug', 'avatar')));
            });
    }

    private function summary(Product $avatar): array
    {
        return [
            'id' => (string) $avatar->id,
            'name' => $avatar->name,
            'thumbnail_url' => $avatar->oldestImage?->url,
            'category' => $avatar->category?->slug,
        ];
    }
}
