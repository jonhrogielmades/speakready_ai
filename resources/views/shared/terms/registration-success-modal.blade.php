@php
    $termsSuccessMessage = trim((string) session('success'));
    $registrationRecoveryCode = trim((string) session('recovery_code'));
@endphp

@if($termsSuccessMessage !== '')
    <div class="terms-success-modal is-open" id="srTermsSuccessModal" role="dialog" aria-modal="true" aria-labelledby="srTermsSuccessTitle" aria-describedby="srTermsSuccessMessage" data-terms-success-modal>
        <div class="terms-success-backdrop" data-terms-success-close></div>
        <div class="terms-success-dialog" role="document">
            <div class="terms-success-icon" aria-hidden="true">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <p class="terms-success-kicker">Account Ready</p>
            <h2 class="terms-success-title" id="srTermsSuccessTitle">Welcome to SpeakReady AI</h2>
            <p class="terms-success-copy" id="srTermsSuccessMessage">{{ $termsSuccessMessage }}</p>
            @if($registrationRecoveryCode !== '')
                <div style="margin-top:16px;padding:14px;border:1px solid rgba(59,130,246,.25);border-radius:14px;background:rgba(59,130,246,.08);text-align:left;">
                    <strong style="display:block;color:var(--tx,#111827);font-size:.88rem;">Save your recovery code</strong>
                    <span style="display:block;color:var(--tx2,#475569);font-size:.78rem;line-height:1.45;margin-top:4px;">Save this backup code. You can also request a new one-time code through your recovery email.</span>
                    <code style="display:block;margin-top:10px;padding:10px;border-radius:10px;background:rgba(15,23,42,.08);color:var(--tx,#111827);font-size:1rem;letter-spacing:.08em;text-align:center;user-select:all;">{{ $registrationRecoveryCode }}</code>
                    <button type="button" class="terms-success-button" style="min-height:40px;margin-top:10px;background:#2563eb;" data-copy-recovery-code="{{ $registrationRecoveryCode }}">
                        <i class="fa-regular fa-copy" aria-hidden="true"></i> Copy Recovery Code
                    </button>
                    <label style="display:flex;align-items:flex-start;gap:9px;margin-top:12px;color:var(--tx,#111827);font-size:.8rem;line-height:1.45;cursor:pointer;">
                        <input type="checkbox" data-recovery-code-saved-ack style="width:17px;height:17px;margin-top:1px;accent-color:#2563eb;flex:0 0 auto;" @checked(old('recovery_code_saved'))>
                        <span>I saved this recovery code in a secure place.</span>
                    </label>
                    @error('recovery_code_saved')
                        <p role="alert" style="margin:8px 0 0;color:#dc2626;font-size:.76rem;font-weight:750;line-height:1.4;">{{ $message }}</p>
                    @enderror
                </div>
            @endif
            @if(Auth::user())
                @if(auth()->user()?->hasVerifiedEmail() && filled(auth()->user()?->google_id))
                    <p class="terms-success-note"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Your recovery email was verified by Google and is ready for password recovery.</p>
                @elseif(auth()->user()?->hasVerifiedEmail())
                    <p class="terms-success-note"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Your recovery email is verified and ready for password recovery.</p>
                @else
                    <p class="terms-success-note"><i class="fa-regular fa-envelope" aria-hidden="true"></i> Check your recovery email for a verification link before using email password recovery.</p>
                @endif
            @endif
            <p class="terms-success-note">One quick agreement step unlocks your dashboard, practice tools, and AI feedback workspace.</p>
            <button class="terms-success-button" type="button" data-terms-success-continue {{ $registrationRecoveryCode !== '' ? 'disabled' : '' }}>
                Continue Review <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('srTermsSuccessModal');
            if (!modal) {
                return;
            }

            const continueButton = modal.querySelector('[data-terms-success-continue]');
            const savedAcknowledgement = modal.querySelector('[data-recovery-code-saved-ack]');
            const savedInput = document.getElementById('recoveryCodeSaved');
            const recoveryCodeRequired = Boolean(savedAcknowledgement);

            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                window.setTimeout(function () {
                    modal.remove();
                }, 180);
            }

            function syncRecoveryAcknowledgement() {
                const acknowledged = !recoveryCodeRequired || Boolean(savedAcknowledgement.checked);

                if (continueButton) {
                    continueButton.disabled = !acknowledged;
                    continueButton.setAttribute('aria-disabled', acknowledged ? 'false' : 'true');
                }

                if (savedInput) {
                    savedInput.value = acknowledged ? '1' : '0';
                }
            }

            savedAcknowledgement?.addEventListener('change', syncRecoveryAcknowledgement);
            continueButton?.addEventListener('click', function () {
                if (continueButton.disabled) return;
                closeModal();
            });

            const copyButton = modal.querySelector('[data-copy-recovery-code]');
            if (copyButton) {
                copyButton.addEventListener('click', function () {
                    const code = copyButton.getAttribute('data-copy-recovery-code') || '';
                    if (!navigator.clipboard || !code) {
                        return;
                    }

                    navigator.clipboard.writeText(code).then(function () {
                        copyButton.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Recovery Code Copied';
                    }).catch(function () {
                        // The visible code remains selectable if clipboard access is unavailable.
                    });
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && document.body.contains(modal) && !recoveryCodeRequired) {
                    closeModal();
                }
            });

            syncRecoveryAcknowledgement();

            if (savedAcknowledgement) {
                savedAcknowledgement.focus({ preventScroll: true });
            } else if (continueButton) {
                continueButton.focus({ preventScroll: true });
            }
        })();
    </script>
@endif
