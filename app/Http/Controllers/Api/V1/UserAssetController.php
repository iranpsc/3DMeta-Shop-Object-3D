<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAssetController extends Controller
{
    /**
     * Get the categories of the user's products.
     *
     * @return JsonResponse
     */
    public function getCategories()
    {
        $user = request()->user();

        if (request()->query('defaults') == true) {
            $categories = Category::doesntHave('children')->withCount('products')->get();

            $categories = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'quantity' => $category->products_count,
                ];
            });
        } else {
            $categories = $user->products()->with('category')->get();

            $categories = $categories->map(function ($product) use ($categories) {

                $category = $product->category;
                $quantity = $categories->where('category_id', $category->id)->count();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'quantity' => $quantity,
                ];
            })->unique();
        }

        return response()->json(['data' => $categories]);
    }

    /**
     * Get the products of the user's category.
     *
     * @return JsonResponse
     */
    public function getCategoryProducts(Category $category)
    {
        $user = request()->user();

        if (request()->query('defaults') == true) {
            $products = Product::where('category_id', $category->id)
                ->with('oldestImage')
                ->get();

            $products = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->oldestImage->url,
                ];
            });
        } else {
            $products = $user->products()
                ->where('category_id', $category->id)
                ->with('oldestImage')
                ->withPivot('quantity')
                ->orderByPivot('updated_at', 'desc')
                ->get();

            $products = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->oldestImage->url,
                    'quantity' => $product->pivot->quantity,
                ];
            });
        }

        return response()->json(['data' => $products]);
    }

    /**
     * Get the product of the user.
     *
     * @return JsonResponse
     */
    public function getProduct(Product $product)
    {
        $product = $product->load('files', 'attributes', 'attributes', 'images')
            ->loadAvg('reviews', 'rating');

        return response()->json(['data' => [
            'id' => $product->id,
            'name' => $product->name,
            'files' => $product->files->map(fn ($file) => $file->url),
            'rating' => $product->reviews_avg_rating,
            'images' => $product->images->map(function ($image) {
                return $image->url;
            }),
            'attributes' => $product->attributes->map(function ($attribute): array {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'value' => $attribute->pivot->value,
                ];
            }),
        ]]);
    }

    /**
     * Search the products of the user.
     *
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string',
        ]);

        $user = request()->user();

        if (request()->query('defaults') == true) {
            $products = Product::where('name', 'like', '%'.$request->query('q').'%')
                ->with('oldestImage')
                ->get();

            $products = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->oldestImage->url,
                ];
            });
        } else {
            $products = $user->products()
                ->where('name', 'like', '%'.$request->query('q').'%')
                ->with('oldestImage')
                ->withPivot('quantity')
                ->orderByPivot('updated_at', 'desc')
                ->get();

            $products = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->oldestImage->url,
                    'quantity' => $product->pivot->quantity,
                ];
            });
        }

        return response()->json(['data' => $products]);
    }

    /**
     * Get the avatars of the user.
     *
     * @return JsonResponse
     */
    public function getAvatars()
    {
        $avatarCategory = cache()->remember('avatar_category', now()->addWeek(), function () {
            return Category::where('slug', 'avatar')->first();
        });

        $avatarCategoryIds = cache()->remember('avatar_category_ids', now()->addWeek(), function () use ($avatarCategory) {
            return $avatarCategory->children()->pluck('id')->push($avatarCategory->id);
        });

        $user = request()->user();

        if (request()->query('defaults') == true) {
            $avatars = Product::whereIn('category_id', $avatarCategoryIds)
                ->with('oldestImage')
                ->get();

            $avatars = $avatars->map(function ($avatar) {
                return [
                    'id' => $avatar->id,
                    'name' => $avatar->name,
                    'image' => $avatar->oldestImage->url,
                ];
            });
        } else {
            $avatars = $user->products()
                ->whereIn('category_id', $avatarCategoryIds)
                ->with('oldestImage')
                ->withPivot('quantity')
                ->orderByPivot('updated_at', 'desc')
                ->get();

            $avatars = $avatars->map(function ($avatar) {
                return [
                    'id' => $avatar->id,
                    'name' => $avatar->name,
                    'image' => $avatar->oldestImage->url,
                    'quantity' => $avatar->pivot->quantity,
                ];
            });
        }

        return response()->json(['data' => $avatars]);
    }

    /**
     * Get the avatar of the user.
     *
     * @return JsonResponse
     */
    public function getAvatar(Request $request, int $avatarId)
    {
        $user = $request->user();

        $avatar = $user->products()
            ->where('product_id', $avatarId)
            ->with('files', 'attributes', 'attributes', 'images')
            ->withAvg('reviews', 'rating')
            ->withPivot('quantity')
            ->first();

        abort_if(is_null($avatar), 404, 'Avatar not found.');

        return response()->json(['data' => [
            'id' => $avatar->id,
            'name' => $avatar->name,
            'files' => $avatar->files->map(fn ($file) => $file->url),
            'quantity' => $avatar->pivot->quantity,
            'rating' => $avatar->reviews_avg_rating,
            'images' => $avatar->images->map(function ($image) {
                return $image->url;
            }),
            'attributes' => $avatar->attributes->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'value' => $attribute->pivot->value,
                ];
            }),
        ]]);
    }
}
