<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserOnboardingProgress;
use App\Services\RecoveryEmailChangeNotifier;
use App\Services\RecoveryEmailVerification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecoveryEmailVerificationController extends Controller
{
    public function verify(
        Request $request,
        RecoveryEmailVerification $verification,
        RecoveryEmailChangeNotifier $emailChangeNotifier
    ): RedirectResponse
    {
        $user = User::query()->findOrFail((int) $request->route('id'));

        abort_unless($user->isLocalAccount(), 404);

        $targetEmail = $verification->targetEmail($user);
        abort_unless(filled($targetEmail), 404);

        abort_unless(
            hash_equals((string) $request->route('hash'), sha1($targetEmail)),
            403
        );

        $oldVerifiedEmail = null;
        $completedPendingChange = false;

        if (filled($user->pending_recovery_email)) {
            $result = DB::transaction(function () use ($request, $verification, $user): array {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
                $pendingEmail = $verification->targetEmail($lockedUser);

                abort_unless(
                    filled($pendingEmail)
                    && hash_equals((string) $request->route('hash'), sha1($pendingEmail)),
                    403
                );

                $conflict = User::query()
                    ->where($lockedUser->getKeyName(), '!=', $lockedUser->getKey())
                    ->where('auth_method', User::AUTH_METHOD_LOCAL)
                    ->whereRaw('LOWER(email) = ?', [$pendingEmail])
                    ->exists();

                if ($conflict) {
                    return ['conflict' => true, 'user' => $lockedUser, 'old_email' => null];
                }

                $oldEmail = $lockedUser->hasVerifiedEmail()
                    ? strtolower(trim((string) $lockedUser->email))
                    : null;

                $lockedUser->forceFill([
                    'email' => $pendingEmail,
                    'email_verified_at' => now(),
                    'pending_recovery_email' => null,
                    'pending_recovery_email_requested_at' => null,
                ])->save();

                return ['conflict' => false, 'user' => $lockedUser, 'old_email' => $oldEmail];
            });

            if ($result['conflict']) {
                $destination = Auth::check() && Auth::id() === $user->getKey()
                    ? redirect()->route('user.account', sr_layout_query($request))
                    : redirect('/');

                return $destination->with('error', 'That recovery email is already in use. Your existing verified address was not changed.');
            }

            $user = $result['user'];
            $oldVerifiedEmail = $result['old_email'];
            $completedPendingChange = true;
            event(new Verified($user));
        } elseif (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($completedPendingChange && filled($oldVerifiedEmail)) {
            $emailChangeNotifier->completed($oldVerifiedEmail, (string) $user->email);
        }

        if (Auth::check() && Auth::id() === $user->getKey()) {
            if (! $user->hasAcceptedCurrentTerms()) {
                return redirect()->route('terms.acceptance.show', sr_layout_query($request))
                    ->with('success', 'Recovery email verified. Email password recovery is now available.');
            }

            $onboardingState = UserOnboardingProgress::forUser($user)->toStateArray($user);
            $onboardingAutoStartPending = $request->session()->get('onboarding_auto_start') === true;

            if ($onboardingAutoStartPending || ! ($onboardingState['first_steps_complete'] ?? false)) {
                return redirect()->route('dashboard', array_merge(
                    sr_layout_query($request),
                    ['onboarding' => 'first-steps']
                ))->with('success', 'Recovery email verified. Continue your remaining setup steps.');
            }

            return redirect()->route('user.account', sr_layout_query($request))
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

        $targetEmail = $verification->targetEmail($user);

        if (blank($targetEmail)) {
            return back()->with('error', 'Add a recovery email before requesting verification.');
        }

        if (blank($user->pending_recovery_email) && $user->hasVerifiedEmail()) {
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

        return back()->with('status', filled($user->pending_recovery_email)
            ? 'Verification link sent to your pending recovery email.'
            : 'Verification link sent. Check your inbox and spam folder.');
    }
}
