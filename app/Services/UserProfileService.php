<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;

class UserProfileService
{
    /**
     * @param  array{name?: string, email?: string, phone?: string}  $data
     * @return array{user: User, message: string, info: string|null}
     */
    public function update(User $user, array $data, ?UploadedFile $avatar = null): array
    {
        $info = null;

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (isset($data['phone'])) {
            $user->phone = $data['phone'];
        }

        if ($avatar) {
            $user->avatar = $avatar->store('avatars', 'public');
        }

        $user->save();

        if ($user->wasChanged('email')) {
            $user->email_verified_at = null;
            $user->save();
            $user->sendEmailVerificationNotification();
            $info = 'ایمیل تایید حساب کاربری برای شما ارسال شد.';
        }

        return [
            'user' => $user->fresh(),
            'message' => 'اطلاعات کاربری شما با موفقیت بروزرسانی شدند.',
            'info' => $info,
        ];
    }
}
