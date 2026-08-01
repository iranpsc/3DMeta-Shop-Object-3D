<?php

namespace Tests\Unit\Support;

use App\Support\IntendedUrl;
use Illuminate\Http\Request;
use Tests\TestCase;

class IntendedUrlTest extends TestCase
{
    public function test_resolve_returns_null_for_empty_values(): void
    {
        config(['app.frontend_url' => 'http://localhost:3000']);

        $this->assertNull(IntendedUrl::resolve(null));
        $this->assertNull(IntendedUrl::resolve(''));
    }

    public function test_resolve_rejects_untrusted_and_empty_frontend_url(): void
    {
        config(['app.frontend_url' => '']);
        $this->assertNull(IntendedUrl::resolve('http://localhost:3000/x'));

        config(['app.frontend_url' => 'http://localhost:3000']);
        $this->assertNull(IntendedUrl::resolve('https://evil.example/x'));
        $this->assertSame(
            'http://localhost:3000/checkout',
            IntendedUrl::resolve('http://localhost:3000/checkout')
        );
    }

    public function test_from_request_reads_query_string(): void
    {
        config(['app.frontend_url' => 'http://localhost:3000']);

        $request = Request::create('/login', 'GET', [
            'intended' => 'http://localhost:3000/profile',
        ]);

        $this->assertSame(
            'http://localhost:3000/profile',
            IntendedUrl::fromRequest($request)
        );
    }
}
