<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class AdminTagService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Tag::latest()->paginate($perPage);
    }

    /**
     * @param  array{name: string, slug: string}  $data
     */
    public function store(array $data): Tag
    {
        Gate::authorize('create', Tag::class);

        return Tag::create([
            'name' => $data['name'],
            'slug' => str_replace(' ', '-', trim($data['slug'])),
        ]);
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }
}
