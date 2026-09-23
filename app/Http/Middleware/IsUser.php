<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        if (in_array($user->status, ['inactive', 'suspended'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->withErrors([
                'account_inactive' => 'Your account is inactive. Please request reactivation before continuing.',
            ]);
        }

        if ($user->is_admin) {
            return redirect('/admin/dashboard');
        }

        if (! $user->hasAcceptedCurrentTerms() && ! $request->routeIs('terms.acceptance.*')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please accept the Terms and Conditions before continuing.',
                    'redirect' => route('terms.acceptance.show'),
                ], 409);
            }

            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('terms.acceptance.show');
        }

        return $next($request);
    }
}
