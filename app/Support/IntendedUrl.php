<?php

namespace App\Support;

use Illuminate\Http\Request;

class IntendedUrl
{
    public static function resolve(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        if ($frontendUrl === '' || ! str_starts_with($url, $frontendUrl)) {
            return null;
        }

        return $url;
    }

    public static function fromRequest(Request $request): ?string
    {
        return self::resolve($request->query('intended'));
    }
}
