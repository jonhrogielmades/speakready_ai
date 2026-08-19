@php
    $includeValidationErrors = $includeValidationErrors ?? true;
    $flashItems = [];

    $pushFlash = static function (string $type, ?string $message) use (&$flashItems): void {
        $message = trim((string) $message);
        if ($message === '') {
            return;
        }

        $flashItems[] = [
            'type' => $type,
            'message' => $message,
        ];
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
        'success' => [
            'title' => 'Success',
            'icon' => 'fa-circle-check',
            'accent' => '#22c55e',
            'soft' => 'rgba(34,197,94,.12)',
            'border' => 'rgba(34,197,94,.32)',
        ],
        'danger' => [
            'title' => 'Action Needed',
            'icon' => 'fa-circle-exclamation',
            'accent' => '#ef4444',
            'soft' => 'rgba(239,68,68,.12)',
            'border' => 'rgba(239,68,68,.32)',
        ],
        'warning' => [
            'title' => 'Please Check',
            'icon' => 'fa-triangle-exclamation',
            'accent' => '#f59e0b',
            'soft' => 'rgba(245,158,11,.12)',
            'border' => 'rgba(245,158,11,.32)',
        ],
        'info' => [
            'title' => 'Notice',
            'icon' => 'fa-circle-info',
            'accent' => '#3b82f6',
            'soft' => 'rgba(59,130,246,.12)',
            'border' => 'rgba(59,130,246,.32)',
        ],
    ][$flashType] ?? null;
@endphp

@if($flashConfig && ! empty($flashItems))
    <div class="modal fade sr-flash-modal" id="srFlashModal" tabindex="-1" aria-labelledby="srFlashModalTitle" aria-hidden="true" data-sr-flash-modal>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:var(--sf,#fff);color:var(--tx,#0f172a);border:1px solid {{ $flashConfig['border'] }};border-radius:14px;box-shadow:0 24px 70px rgba(15,23,42,.26);overflow:hidden;">
                <div class="modal-header" style="border-bottom:1px solid var(--bd,rgba(148,163,184,.2));background:{{ $flashConfig['soft'] }};">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;border-radius:10px;background:{{ $flashConfig['soft'] }};color:{{ $flashConfig['accent'] }};">
                            <i class="fa-solid {{ $flashConfig['icon'] }}"></i>
                        </span>
                        <h5 class="modal-title mb-0" id="srFlashModalTitle" style="font-size:1rem;font-weight:800;color:var(--tx,#0f172a);">{{ $flashConfig['title'] }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding:18px 20px 20px;">
                    <div class="sr-flash-message-list" style="display:grid;gap:10px;">
                        @foreach($flashItems as $item)
                            <div class="sr-flash-message" data-sr-flash-message="{{ $item['message'] }}" style="display:flex;gap:10px;align-items:flex-start;padding:12px 13px;border-radius:10px;border:1px solid {{ ($flashConfig['border']) }};background:{{ $item['type'] === 'danger' ? 'rgba(239,68,68,.08)' : ($item['type'] === 'info' ? 'rgba(59,130,246,.08)' : 'rgba(34,197,94,.08)') }};">
                                <i class="fa-solid {{ $item['type'] === 'danger' ? 'fa-circle-exclamation' : ($item['type'] === 'info' ? 'fa-circle-info' : 'fa-circle-check') }} mt-1" style="color:{{ $item['type'] === 'danger' ? '#ef4444' : ($item['type'] === 'info' ? '#3b82f6' : '#22c55e') }};"></i>
                                <div style="min-width:0;overflow-wrap:anywhere;font-size:.94rem;line-height:1.5;font-weight:650;color:var(--tx,#0f172a);">{{ $item['message'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd,rgba(148,163,184,.2));padding:14px 20px;">
                    <button type="button" class="btn bgrd" data-bs-dismiss="modal" style="min-width:108px;border-radius:10px;font-weight:800;">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="srFlashModalPayload">@json($flashItems)</script>
    <script>
        (function() {
            const modalEl = document.getElementById('srFlashModal');
            const payloadEl = document.getElementById('srFlashModalPayload');
            if (!modalEl || !payloadEl) return;

            let flashItems = [];
            try {
                flashItems = JSON.parse(payloadEl.textContent || '[]');
            } catch (error) {
                flashItems = [];
            }

            const normalize = (value) => String(value || '').replace(/\s+/g, ' ').trim();
            const messages = flashItems.map((item) => normalize(item.message)).filter(Boolean);

            function hideMatchingInlineAlerts() {
                if (messages.length === 0) return;

                document.querySelectorAll('.alert, .learning-notice, .err-msg').forEach((el) => {
                    if (modalEl.contains(el)) return;

                    const text = normalize(el.textContent);
                    if (!text) return;

                    const matchesFlash = messages.some((message) => text.includes(message));
                    if (matchesFlash) {
                        el.setAttribute('hidden', 'hidden');
                        el.classList.add('d-none', 'sr-inline-flash-hidden');
                    }
                });
            }

            function showFlashModal() {
                hideMatchingInlineAlerts();

                if (window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl, {
                        backdrop: true,
                        keyboard: true,
                    }).show();
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showFlashModal, { once: true });
            } else {
                showFlashModal();
            }
        })();
    </script>
@endif
