<?php

namespace App\Services;

use App\Models\SubmitOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminSubmitOrderService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return SubmitOrder::latest()->paginate($perPage);
    }

    public function find(int $id): SubmitOrder
    {
        return SubmitOrder::query()->findOrFail($id);
    }
}
