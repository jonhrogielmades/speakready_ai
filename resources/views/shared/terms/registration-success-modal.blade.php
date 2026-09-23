@php
    $termsSuccessMessage = trim((string) session('success'));
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
