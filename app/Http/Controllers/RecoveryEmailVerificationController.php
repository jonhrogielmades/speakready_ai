<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RecoveryEmailVerification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecoveryEmailVerificationController extends Controller
{
    public function verify(Request $request): RedirectResponse
    {
        $user = User::query()->findOrFail((int) $request->route('id'));

        abort_unless($user->isLocalAccount(), 404);

        abort_unless(
            hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())),
            403
        );

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if (Auth::check() && Auth::id() === $user->getKey()) {
            $route = $user->hasAcceptedCurrentTerms()
                ? 'user.account'
                : 'terms.acceptance.show';

            return redirect()->route($route, sr_layout_query($request))
                ->with('success', 'Recovery email verified. Email password recovery is now available.');
        }

        return redirect('/')
            ->with('success', 'Recovery email verified. You can now sign in and use email password recovery.');
    }

    public function send(Request $request, RecoveryEmailVerification $verification): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isLocalAccount()) {
            return back()->with('error', 'Google Login accounts do not use local recovery-email verification.');
        }

        if (blank($user->email)) {
            return back()->with('error', 'Add a recovery email before requesting verification.');
        }

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Your recovery email is already verified.');
        }

        try {
            $verification->send($user);
        } catch (Throwable $error) {
            Log::warning('Recovery email verification could not be delivered.', [
                'user_id' => $user->getKey(),
                'exception_type' => $error::class,
            ]);

            return back()->with('error', 'We could not send the verification email. Please try again later.');
        }

        return back()->with('status', 'Verification link sent. Check your inbox and spam folder.');
    }
}
