@php
    $includeValidationErrors = $includeValidationErrors ?? true;
    $flashItems = [];

    $pushFlash = static function (string $type, ?string $message) use (&$flashItems): void {
        $message = trim((string) $message);
        if ($message === '') {
            return;
        }

        $flashItems[] = ['type' => $type, 'message' => $message];
    };

    $pushFlash('success', session('success'));
    $pushFlash('success', session('message'));
    $pushFlash('info', session('status'));
    $pushFlash('danger', session('error'));

    if ($includeValidationErrors && isset($errors) && $errors->any()) {
        foreach ($errors->all() as $errorMessage) {
            $pushFlash('danger', $errorMessage);
        }
    }

    $flashPriority = ['danger' => 4, 'warning' => 3, 'success' => 2, 'info' => 1];
    $flashType = collect($flashItems)
        ->sortByDesc(fn ($item) => $flashPriority[$item['type']] ?? 0)
        ->first()['type'] ?? null;

    $flashConfig = [
        'success' => ['title' => 'Success', 'icon' => 'fa-circle-check'],
        'danger' => ['title' => 'Action needed', 'icon' => 'fa-circle-exclamation'],
        'warning' => ['title' => 'Please check', 'icon' => 'fa-triangle-exclamation'],
        'info' => ['title' => 'Notice', 'icon' => 'fa-circle-info'],
    ][$flashType] ?? null;
@endphp

@if($flashConfig && ! empty($flashItems))
    <div class="modal fade sr-feedback-modal sr-flash-modal" id="srFlashModal" tabindex="-1" aria-labelledby="srFlashModalTitle" aria-hidden="true" data-sr-flash-modal data-feedback-tone="{{ $flashType }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="sr-feedback-heading">
                        <span class="sr-feedback-icon" aria-hidden="true"><i class="fa-solid {{ $flashConfig['icon'] }}"></i></span>
                        <h5 class="modal-title" id="srFlashModalTitle">{{ $flashConfig['title'] }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sr-feedback-message-list">
                        @foreach($flashItems as $item)
                            <div class="sr-feedback-message" data-sr-flash-message="{{ $item['message'] }}" data-feedback-tone="{{ $item['type'] }}">
                                <i class="fa-solid {{ $item['type'] === 'danger' ? 'fa-circle-exclamation' : ($item['type'] === 'info' ? 'fa-circle-info' : ($item['type'] === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-check')) }}" aria-hidden="true"></i>
                                <span>{{ $item['message'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn sr-feedback-action" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="srFlashModalPayload">@json($flashItems)</script>
    <script>
        (function () {
            var modalEl = document.getElementById('srFlashModal');
            var payloadEl = document.getElementById('srFlashModalPayload');
            if (!modalEl || !payloadEl) return;

            var flashItems = [];
            try { flashItems = JSON.parse(payloadEl.textContent || '[]'); } catch (error) { flashItems = []; }

            var normalize = function (value) { return String(value || '').replace(/\s+/g, ' ').trim(); };
            var messages = flashItems.map(function (item) { return normalize(item.message); }).filter(Boolean);

            function hideMatchingInlineAlerts() {
                if (messages.length === 0) return;
                document.querySelectorAll('.alert, .learning-notice, .err-msg').forEach(function (element) {
                    if (modalEl.contains(element)) return;
                    var text = normalize(element.textContent);
                    if (text && messages.some(function (message) { return text.indexOf(message) !== -1; })) {
                        element.setAttribute('hidden', 'hidden');
                        element.classList.add('d-none', 'sr-inline-flash-hidden');
                    }
                });
            }

            function showFlashModal() {
                hideMatchingInlineAlerts();
                if (window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, keyboard: true }).show();
                }
            }

            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', showFlashModal, { once: true });
            else showFlashModal();
        }());
    </script>
@endif
