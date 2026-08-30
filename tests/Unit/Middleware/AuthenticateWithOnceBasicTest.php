<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AuthenticateWithOnceBasic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthenticateWithOnceBasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_invalid_oauth_token(): void
    {
        config(['app.oauth_server_url' => 'https://accounts.example.com']);

        Http::fake([
            'https://accounts.example.com/api/user' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $request = Request::create('/api/v1/user/assets/categories', 'GET');
        $request->headers->set('Authorization', 'Bearer bad-token');

        $response = (new AuthenticateWithOnceBasic)->handle($request, fn () => response('ok'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Unauthorized', $response->getData(true)['message']);
    }

    public function test_authenticates_valid_oauth_token(): void
    {
        config(['app.oauth_server_url' => 'https://accounts.example.com']);
        $user = User::factory()->create(['email' => 'oauth@example.com']);

        Http::fake([
            'https://accounts.example.com/api/user' => Http::response([
                'email' => $user->email,
                'name' => $user->name,
            ], 200),
        ]);

        $request = Request::create('/api/v1/user/assets/categories', 'GET');
        $request->headers->set('Authorization', 'Bearer good-token');

        $response = (new AuthenticateWithOnceBasic)->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue(Cache::has('user:'.$user->email));
    }
}
