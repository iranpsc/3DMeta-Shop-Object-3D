<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('شما اجازه ایجاد مدل‌ها را ندارید.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): Response
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('شما اجازه به‌روزرسانی این مدل را ندارید.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): Response
    {
        $canDelete = $user->hasRole('admin')
            && $category->products()->doesntExist()
            && $category->children()->doesntExist();

        return $canDelete
            ? Response::allow()
            : Response::deny('شما اجازه حذف این مدل را ندارید.');
    }
}
