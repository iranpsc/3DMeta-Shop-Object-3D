<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_json_request_is_forbidden(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/admin', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);

        $response = (new Admin)->handle($request, fn () => response('ok'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(
            'شما اجازه دسترسی به این صفحه را ندارید.',
            $response->getData(true)['message']
        );
    }

    public function test_non_admin_web_request_redirects_back(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('Referer', 'http://localhost/previous');

        $response = (new Admin)->handle($request, fn () => response('ok'));

        $this->assertTrue($response->isRedirect());
    }

    public function test_admin_passes_through(): void
    {
        $admin = User::factory()->admin()->create();
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $admin);

        $response = (new Admin)->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }
}
