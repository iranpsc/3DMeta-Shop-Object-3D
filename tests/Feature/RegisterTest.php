<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegisterTest extends TestCase
{
    public function test_register_redirects_to_oauth_server()
    {
        config([
            'app.oauth_client_id' => 'test-client-id',
            'app.oauth_server_url' => 'https://accounts.example.com',
        ]);

        $response = $this->get(route('register'));

        $expectedUrl = 'https://accounts.example.com/register?client_id=test-client-id&redirect_uri='
            .urlencode(route('login'));

        $response->assertRedirect($expectedUrl);
    }

    public function test_register_stores_trusted_intended_url(): void
    {
        config([
            'app.oauth_client_id' => 'test-client-id',
            'app.oauth_server_url' => 'https://accounts.example.com',
            'app.frontend_url' => 'http://localhost:3000',
        ]);

        $this->get(route('register', ['intended' => 'http://localhost:3000/profile']))
            ->assertRedirect();

        $this->assertSame('http://localhost:3000/profile', session('url.intended'));
    }
}
