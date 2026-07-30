<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_oauth_authorization(): void
    {
        config([
            'app.oauth_client_id' => 'test-client-id',
            'app.oauth_server_url' => 'https://accounts.example.com',
        ]);

        $response = $this->get(route('login'));

        $expectedUrl = 'https://accounts.example.com/oauth/authorize?'.http_build_query([
            'client_id' => 'test-client-id',
            'redirect_uri' => route('auth.callback'),
            'response_type' => 'code',
            'scope' => '',
            'state' => session('state'),
        ]);

        $response->assertRedirect($expectedUrl);
    }

    public function test_redirect_stores_intended_frontend_url_in_session(): void
    {
        config([
            'app.oauth_client_id' => 'test-client-id',
            'app.oauth_server_url' => 'https://accounts.example.com',
            'app.frontend_url' => 'http://localhost:3000',
        ]);

        $intended = 'http://localhost:3000/profile';

        $this->get(route('login', ['intended' => $intended]))
            ->assertRedirect();

        $this->assertEquals($intended, session('url.intended'));
    }

    public function test_redirect_ignores_untrusted_intended_url(): void
    {
        config([
            'app.oauth_client_id' => 'test-client-id',
            'app.oauth_server_url' => 'https://accounts.example.com',
            'app.frontend_url' => 'http://localhost:3000',
        ]);

        $this->get(route('login', ['intended' => 'https://evil.example/phish']))
            ->assertRedirect();

        $this->assertNull(session('url.intended'));
    }

    public function test_callback_handles_oauth_response(): void
    {
        config([
            'app.oauth_client_id' => 'test-client-id',
            'app.oauth_server_url' => 'https://accounts.example.com',
            'app.oauth_client_secret' => 'test-client-secret',
            'app.frontend_url' => 'http://localhost:3000',
        ]);

        $state = Str::random(40);
        Session::put('state', $state);

        Http::fake([
            'https://accounts.example.com/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://accounts.example.com/api/user' => Http::response([
                'email' => 'testuser@example.com',
                'name' => 'Test User',
                'mobile' => '1234567890',
                'code' => 'test-code',
            ], 200),
        ]);

        $response = $this->get(route('auth.callback', [
            'state' => $state,
            'code' => 'test-authorization-code',
        ]));

        $user = User::where('email', 'testuser@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('test-access-token', $user->access_token);
        $this->assertTrue(Auth::check());

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $response->assertRedirect($frontendUrl);
    }

    public function test_callback_redirects_to_intended_frontend_url(): void
    {
        config([
            'app.oauth_client_id' => 'test-client-id',
            'app.oauth_server_url' => 'https://accounts.example.com',
            'app.oauth_client_secret' => 'test-client-secret',
            'app.frontend_url' => 'http://localhost:3000',
        ]);

        $state = Str::random(40);
        $intended = 'http://localhost:3000/tickets';

        Session::put('state', $state);
        Session::put('url.intended', $intended);

        Http::fake([
            'https://accounts.example.com/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://accounts.example.com/api/user' => Http::response([
                'email' => 'testuser@example.com',
                'name' => 'Test User',
                'mobile' => '1234567890',
                'code' => 'test-code',
            ], 200),
        ]);

        $response = $this->get(route('auth.callback', [
            'state' => $state,
            'code' => 'test-authorization-code',
        ]));

        $response->assertRedirect($intended);
    }
}
