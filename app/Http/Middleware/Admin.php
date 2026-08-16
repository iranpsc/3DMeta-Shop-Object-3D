<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->hasRole('admin')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'data' => null,
                    'message' => 'شما اجازه دسترسی به این صفحه را ندارید.',
                ], 403);
            }

            return redirect()->back()->with('error', 'شما اجازه دسترسی به این صفحه را ندارید.');
        }

        return $next($request);
    }
}
