<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpFoundation\Response;

class StartSessionForTicketAttachments
{
    /**
     * Browser navigations to attachment URLs often omit Origin and Referer
     * (new tab, rel=noreferrer). Sanctum then skips the session, auth fails,
     * and the guest redirect looks up a login route this API does not define.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldStartSession($request)) {
            return $next($request);
        }

        $middleware = array_values(array_filter([
            config('sanctum.middleware.encrypt_cookies', EncryptCookies::class),
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            config('sanctum.middleware.validate_csrf_token'),
            config('sanctum.middleware.authenticate_session', AuthenticateSession::class),
        ]));

        return (new Pipeline(app()))
            ->send($request)
            ->through($middleware)
            ->then(fn (Request $request) => $next($request));
    }

    private function shouldStartSession(Request $request): bool
    {
        if (! $request->isMethod('GET') || EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            return false;
        }

        return $request->is(
            'api/v1/tickets/*/attachment',
            'api/v1/tickets/*/responses/*/attachment',
        );
    }
}
