<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
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

        if (!$user->is_admin) {
            return redirect('/dashboard');
        }

        return $this->withoutRestrictedCountryText($next($request));
    }

    private function withoutRestrictedCountryText(Response $response): Response
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        if (
            $contentType !== ''
            && ! str_contains($contentType, 'text/html')
            && ! str_contains($contentType, 'application/json')
            && ! str_contains($contentType, 'text/plain')
        ) {
            return $response;
        }

        $hasOriginal = isset($response->original);
        $original = $hasOriginal ? $response->original : null;
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return $response;
        }

        $response->setContent(admin_without_restricted_country_text($content));
        if ($hasOriginal) {
            $response->original = $original;
        }
        $response->headers->remove('Content-Length');

        return $response;
    }
}
