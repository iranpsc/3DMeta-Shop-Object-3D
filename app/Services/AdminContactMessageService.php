<?php

namespace App\Services;

use App\Models\ContactUsMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminContactMessageService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return ContactUsMessage::latest()->paginate($perPage);
    }
}
