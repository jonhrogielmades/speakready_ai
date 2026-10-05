<div class="modal fade sr-dashboard-coach-modal" id="dashboardCoachModal" tabindex="-1" aria-labelledby="dashboardCoachModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="dashboardCoachForm" action="{{ route('user.coach.chat') }}" method="POST" data-full-coach-url="{{ route('user.coach') }}" data-conversation-url="{{ url('/coach/conversation') }}">
                @csrf
                <div class="modal-header">
                    <div class="sr-dashboard-coach-heading">
                        <span class="sr-dashboard-coach-icon"><i class="fa-solid fa-robot"></i></span>
                        <div>
                            <h5 class="modal-title" id="dashboardCoachModalTitle">AI Coach</h5>
                            <p>Ask for focused interview guidance.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="sr-dashboard-coach-label" for="dashboardCoachMessage">Question or focus</label>
                    <div class="sr-dashboard-coach-input-wrap">
                        <textarea class="sr-dashboard-coach-input" id="dashboardCoachMessage" name="message" rows="4" maxlength="10000" required placeholder="Example: Help me prepare a stronger answer for a customer service interview."></textarea>
                        <button class="sr-dashboard-coach-voice" type="button" id="dashboardCoachVoice" aria-label="Start voice prompt" aria-pressed="false" title="Speak a question">
                            <i class="fa-solid fa-microphone"></i>
                        </button>
                    </div>
                    <div class="sr-dashboard-coach-status" id="dashboardCoachStatus" role="status" aria-live="polite"></div>
                    <div class="sr-dashboard-coach-response" id="dashboardCoachResponse" hidden>
                        <div class="sr-dashboard-coach-response-head">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>Coach reply</span>
                        </div>
                        <div class="sr-dashboard-coach-response-body" id="dashboardCoachResponseBody"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sr-dashboard-coach-danger" id="dashboardCoachClear"><i class="fa-solid fa-broom"></i> Clear convo</button>
                    <a href="{{ route('user.coach') }}" class="sr-dashboard-coach-link">Open full coach</a>
                    <button type="button" class="sr-dashboard-coach-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="sr-dashboard-coach-submit" id="dashboardCoachSubmit"><i class="fa-solid fa-paper-plane"></i> Ask Coach</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        function initDashboardCoachModal() {
            const form = document.getElementById('dashboardCoachForm');
            if (!form || form.dataset.bound === 'true') return;

            form.dataset.bound = 'true';

            const textarea = document.getElementById('dashboardCoachMessage');
            const submitButton = document.getElementById('dashboardCoachSubmit');
            const clearButton = document.getElementById('dashboardCoachClear');
            const voiceButton = document.getElementById('dashboardCoachVoice');
            const status = document.getElementById('dashboardCoachStatus');
            const responsePanel = document.getElementById('dashboardCoachResponse');
            const responseBody = document.getElementById('dashboardCoachResponseBody');
            const defaultSubmitHtml = submitButton ? submitButton.innerHTML : '';
            const DashboardCoachSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            let conversationId = null;
            let voiceRecognition = null;
            let voiceActive = false;
            const chatHistory = [];

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || form.querySelector('input[name="_token"]')?.value
                    || '';
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatCoachReply(value) {
                return '<p>' + escapeHtml(value || 'The coach did not return a message.')
                    .replace(/\n{2,}/g, '</p><p>')
                    .replace(/\n/g, '<br>') + '</p>';
            }

            function setStatus(message, type = 'info') {
                if (!status) return;

                status.textContent = message || '';
                status.dataset.type = type;
                status.classList.toggle('show', Boolean(message));
            }

            function setSubmitting(isSubmitting) {
                if (!submitButton) return;

                submitButton.disabled = isSubmitting;
                submitButton.setAttribute('aria-busy', isSubmitting ? 'true' : 'false');
                submitButton.innerHTML = isSubmitting
                    ? '<i class="fa-solid fa-spinner fa-spin"></i> Asking...'
                    : defaultSubmitHtml;
            }

            function renderReply(message) {
                if (!responsePanel || !responseBody) return;

                responseBody.innerHTML = formatCoachReply(message);
                responsePanel.hidden = false;
            }

            function appendVoiceText(text) {
                const clean = String(text || '').replace(/\s+/g, ' ').trim();
                if (!clean || !textarea) return;

                const current = textarea.value.trim();
                textarea.value = current ? current + ' ' + clean : clean;
                textarea.focus();
            }

            function setVoiceState(isActive) {
                voiceActive = isActive;
                if (!voiceButton) return;

                voiceButton.classList.toggle('is-recording', isActive);
                voiceButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                voiceButton.setAttribute('aria-label', isActive ? 'Stop voice prompt' : 'Start voice prompt');
                voiceButton.title = isActive ? 'Stop voice prompt' : 'Speak a question';

                const icon = voiceButton.querySelector('i');
                if (icon) icon.className = isActive ? 'fa-solid fa-stop' : 'fa-solid fa-microphone';
            }

            function ensureVoiceRecognition() {
                if (voiceRecognition || !DashboardCoachSpeechRecognition) return voiceRecognition;

                voiceRecognition = new DashboardCoachSpeechRecognition();
                voiceRecognition.continuous = false;
                voiceRecognition.interimResults = false;
                voiceRecognition.maxAlternatives = 1;
                voiceRecognition.lang = document.documentElement.lang || navigator.language || 'en-US';

                voiceRecognition.onresult = function(event) {
                    const transcript = event.results?.[0]?.[0]?.transcript || '';
                    appendVoiceText(transcript);
                    setStatus('Voice prompt added.', 'success');
                };

                voiceRecognition.onerror = function() {
                    setStatus('Voice prompt could not start. You can still type your question.', 'warning');
                    setVoiceState(false);
                };

                voiceRecognition.onend = function() {
                    setVoiceState(false);
                };

                return voiceRecognition;
            }

            if (voiceButton && !DashboardCoachSpeechRecognition) {
                voiceButton.disabled = true;
                voiceButton.classList.add('is-disabled');
                voiceButton.title = 'Voice prompt is not supported in this browser';
                voiceButton.setAttribute('aria-label', 'Voice prompt is not supported in this browser');
            }

            voiceButton?.addEventListener('click', function() {
                const recognition = ensureVoiceRecognition();
                if (!recognition) return;

                if (voiceActive) {
                    recognition.stop();
                    setVoiceState(false);
                    return;
                }

                try {
                    setVoiceState(true);
                    setStatus('Listening...', 'info');
                    recognition.start();
                } catch (error) {
                    setVoiceState(false);
                    setStatus('Voice prompt could not start. You can still type your question.', 'warning');
                }
            });

            clearButton?.addEventListener('click', function() {
                conversationId = null;
                chatHistory.splice(0, chatHistory.length);
                if (textarea) textarea.value = '';
                if (responsePanel) responsePanel.hidden = true;
                if (responseBody) responseBody.innerHTML = '';
                setStatus('Conversation cleared.', 'success');
                textarea?.focus();
            });

            form.addEventListener('submit', async function(event) {
                event.preventDefault();

                const message = textarea ? textarea.value.trim() : '';
                if (!message) {
                    setStatus('Type a question for the AI Coach first.', 'warning');
                    textarea?.focus();
                    return;
                }

                const formData = new FormData(form);
                formData.set('message', message);
                formData.set('history', JSON.stringify(chatHistory.slice(-10)));
                if (conversationId) formData.set('conversation_id', conversationId);

                setSubmitting(true);
                setStatus('Asking AI Coach...', 'info');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: formData,
                    });

                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data.message || data.response || 'AI Coach could not respond right now.');
                    }

                    const reply = data.response || 'The coach did not return a message.';
                    conversationId = data.conversation_id || conversationId;
                    chatHistory.push({ role: 'user', content: message });
                    chatHistory.push({ role: 'ai', content: reply });
                    renderReply(reply);
                    setStatus('Coach reply ready.', 'success');
                } catch (error) {
                    setStatus(error.message || 'AI Coach could not respond right now.', 'error');
                } finally {
                    setSubmitting(false);
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardCoachModal);
        } else {
            initDashboardCoachModal();
        }
    })();
</script>
@endpush
