@extends('layouts.admin')

@section('content')
<style>
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .input-dark {
        background: var(--bg3, #2b2b40);
        border: 1px solid var(--bd, rgba(255,255,255,0.1));
        color: var(--tx);
    }
    .input-dark:focus {
        background: var(--bg3);
        border-color: #3b82f6;
        color: var(--tx);
        box-shadow: 0 0 0 0.25rem rgba(59,130,246,0.25);
    }
    .chat-bubble {
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 20px;
        max-width: 85%;
    }
    .bubble-user {
        background: #3b82f6;
        color: #fff;
        margin-left: auto;
        border-bottom-right-radius: 4px;
    }
    .bubble-ai {
        background: var(--bg3);
        color: var(--tx);
        border: 1px solid var(--bd);
        margin-right: auto;
        border-bottom-left-radius: 4px;
    }
    #loadingIndicator {
        display: none;
    }
</style>

<div class="db-section active">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-vial me-2" style="color:#10b981;"></i>AI Testing Center</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Test and verify AI responses before deployment.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Configuration Side -->
        <div class="col-lg-4">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-4">Test Configuration</h6>
                <form id="testForm">
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size:0.85rem;">Select Provider</label>
                        <select id="provider_id" class="form-select input-dark" required>
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }} {{ $provider->is_primary ? '(Primary)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted" style="font-size:0.85rem;">Test Prompt</label>
                        <textarea id="promptText" class="form-control input-dark" rows="5" placeholder="e.g. Tell me about yourself." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold"><i class="fa-solid fa-paper-plane me-2"></i>Send Test Request</button>
                </form>
            </div>
        </div>

        <!-- Output Side -->
        <div class="col-lg-8">
            <div class="premium-card h-100 d-flex flex-column">
                <h6 class="fw-bold mb-4">Response Output</h6>
                
                <div id="chatBox" class="flex-grow-1" style="min-height: 400px; overflow-y: auto; padding-right:10px;">
                    <div class="text-center text-muted mt-5" id="emptyState">
                        <i class="fa-solid fa-robot fa-3x mb-3 opacity-50"></i>
                        <p>No tests run yet. Enter a prompt to verify AI quality.</p>
                    </div>
                </div>

                <div id="loadingIndicator" class="text-center mt-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 text-sm">Generating response...</p>
                </div>
                
                <div id="metricsBox" class="mt-3 pt-3 border-top" style="display:none; border-color:var(--bd) !important;">
                    <div class="d-flex justify-content-between text-muted" style="font-size:0.8rem;">
                        <span id="metricTime"><i class="fa-solid fa-clock me-1"></i> Time: 0ms</span>
                        <span><i class="fa-solid fa-check-circle text-success me-1"></i> Status: 200 OK</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('testForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    let prompt = document.getElementById('promptText').value;
    let providerId = document.getElementById('provider_id').value;
    
    if(!prompt) return;
    
    document.getElementById('emptyState').style.display = 'none';
    
    // Add user bubble
    let chatBox = document.getElementById('chatBox');
    let userBubble = `<div class="chat-bubble bubble-user">${prompt}</div>`;
    chatBox.innerHTML += userBubble;
    chatBox.scrollTop = chatBox.scrollHeight;
    
    // Show Loading
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('metricsBox').style.display = 'none';
    
    // Send request
    fetch('{{ route("admin.ai.testing.generate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ prompt: prompt, provider_id: providerId })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loadingIndicator').style.display = 'none';
        
        let aiBubble = `<div class="chat-bubble bubble-ai"><i class="fa-solid fa-robot me-2 text-primary"></i>${data.response}</div>`;
        chatBox.innerHTML += aiBubble;
        chatBox.scrollTop = chatBox.scrollHeight;
        
        document.getElementById('metricsBox').style.display = 'block';
        document.getElementById('metricTime').innerHTML = `<i class="fa-solid fa-clock me-1"></i> Time: ${data.time_ms}ms`;
        
        document.getElementById('promptText').value = '';
    })
    .catch(error => {
        document.getElementById('loadingIndicator').style.display = 'none';
        alert('An error occurred during testing.');
    });
});
</script>
@endsection
