<?php

namespace App\Services;

use App\Models\Attribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class AdminAttributeService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Attribute::latest()->paginate($perPage);
    }

    /**
     * @param  array{name: string, slug: string}  $data
     */
    public function store(array $data): Attribute
    {
        Gate::authorize('create', Attribute::class);

        return Attribute::create($data);
    }

    public function delete(Attribute $attribute): void
    {
        $attribute->delete();
    }
}
