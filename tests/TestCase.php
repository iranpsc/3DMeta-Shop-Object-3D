<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Headers that enable Sanctum stateful session on API routes (matches Next.js SPA).
     *
     * @return array<string, string>
     */
    protected function statefulApiHeaders(): array
    {
        return [
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/',
        ];
    }

    /**
     * Act as a verified user with Sanctum stateful headers.
     */
    protected function actingAsVerifiedApiUser(User $user): self
    {
        return $this->actingAs($user)->withHeaders($this->statefulApiHeaders());
    }

    /**
     * Act as a verified admin with Sanctum stateful headers.
     */
    protected function actingAsAdminApiUser(?User $user = null): self
    {
        $user ??= User::factory()->admin()->create();

        return $this->actingAsVerifiedApiUser($user);
    }
}
