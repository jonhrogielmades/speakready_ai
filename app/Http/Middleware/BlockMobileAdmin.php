<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockMobileAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->get('is_mobile', false)) {
            return $next($request);
        }

        if (! $request->isMethod('GET')) {
            abort(403, 'The admin area is temporarily available on desktop only.');
        }

        return response(mobile_view('admin.mobile-locked'), 403);
    }
}
