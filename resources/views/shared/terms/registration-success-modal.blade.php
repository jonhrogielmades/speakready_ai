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
                    <span style="display:block;color:var(--tx2,#475569);font-size:.78rem;line-height:1.45;margin-top:4px;">You need this code to reset your password without email. It is shown only during account setup.</span>
                    <code style="display:block;margin-top:10px;padding:10px;border-radius:10px;background:rgba(15,23,42,.08);color:var(--tx,#111827);font-size:1rem;letter-spacing:.08em;text-align:center;user-select:all;">{{ $registrationRecoveryCode }}</code>
                    <button type="button" class="terms-success-button" style="min-height:40px;margin-top:10px;background:#2563eb;" data-copy-recovery-code="{{ $registrationRecoveryCode }}">
                        <i class="fa-regular fa-copy" aria-hidden="true"></i> Copy Recovery Code
                    </button>
                </div>
            @endif
            <p class="terms-success-note">One quick agreement step unlocks your dashboard, practice tools, and AI feedback workspace.</p>
            <button class="terms-success-button" type="button" data-terms-success-close>
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

            const closeButtons = modal.querySelectorAll('[data-terms-success-close]');
            const actionButton = modal.querySelector('.terms-success-button');

            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                window.setTimeout(function () {
                    modal.remove();
                }, 180);
            }

            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeModal);
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
                if (event.key === 'Escape' && document.body.contains(modal)) {
                    closeModal();
                }
            });

            if (actionButton) {
                actionButton.focus({ preventScroll: true });
            }
        })();
    </script>
@endif
