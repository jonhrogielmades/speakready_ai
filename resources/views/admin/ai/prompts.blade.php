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
    .prompt-textarea {
        font-family: monospace;
        font-size: 0.9rem;
        line-height: 1.5;
        min-height: 200px;
        resize: vertical;
    }
</style>

<div class="db-section active">
    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-comment-dots me-2" style="color:#10b981;"></i>System Prompts</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Manage the base prompts used across different AI modules.</p>
        </div>
    </div>

    @php
        $modules = [
            'mock_interview' => 'Mock Interview Prompt',
            'feedback_generation' => 'Feedback Generation Prompt',
            'ai_coach' => 'AI Coach Prompt',
            'readiness_assessment' => 'Readiness Assessment Prompt'
        ];
    @endphp

    <div class="accordion" id="promptsAccordion" style="--bs-accordion-bg: var(--sf); --bs-accordion-color: var(--tx); --bs-accordion-border-color: var(--bd);">
        @foreach($modules as $key => $name)
            @php 
                $existingPrompt = $prompts->where('module', $key)->first();
                $defaultText = $existingPrompt ? $existingPrompt->prompt_text : "You are a helpful AI assistant for $name.";
            @endphp
            <div class="accordion-item" style="border: 1px solid var(--bd); margin-bottom: 15px; border-radius: 12px; overflow: hidden;">
                <h2 class="accordion-header" id="heading-{{ $key }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $key }}" style="background:var(--bg3);color:var(--tx);font-weight:600;box-shadow:none;">
                        {{ $name }}
                    </button>
                </h2>
                <div id="collapse-{{ $key }}" class="accordion-collapse collapse" data-bs-parent="#promptsAccordion">
                    <div class="accordion-body">
                        <form action="{{ route('admin.ai.prompts.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="module" value="{{ $key }}">
                            <input type="hidden" name="name" value="{{ $name }}">
                            
                            <div class="mb-3">
                                <label class="form-label text-muted" style="font-size:0.85rem;">System Prompt Text</label>
                                <textarea name="prompt_text" class="form-control input-dark prompt-textarea" required>{{ $defaultText }}</textarea>
                                <div class="form-text text-muted" style="font-size:0.75rem;margin-top:8px;">You can use variables like <code>{user_name}</code> or <code>{question}</code> depending on the module context.</div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i>Save Prompt</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
