<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_swagger_ui_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('3D RGB API Documentation')
            ->assertSee('/docs/openapi.yaml');
    }

    public function test_openapi_specification_is_available(): void
    {
        config(['app.url' => 'https://api.example.test']);

        $this->get('/docs/openapi.yaml')
            ->assertOk()
            ->assertHeader('content-type', 'application/yaml; charset=UTF-8')
            ->assertHeader('cache-control', 'no-cache, private')
            ->assertSee('url: https://api.example.test/api/v1');
    }

    public function test_missing_openapi_specification_returns_not_found(): void
    {
        File::shouldReceive('exists')->andReturnFalse();

        $this->get('/docs/openapi.yaml')->assertNotFound();
    }
}
