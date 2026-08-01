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
}
