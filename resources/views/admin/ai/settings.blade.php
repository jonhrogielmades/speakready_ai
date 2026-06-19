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
    .module-item {
        padding: 15px;
        border: 1px solid var(--bd);
        border-radius: 12px;
        background: var(--bg3);
        margin-bottom: 15px;
        transition: transform 0.2s;
    }
    .module-item:hover {
        border-color: #3b82f6;
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
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-sliders me-2" style="color:#f59e0b;"></i>AI Settings</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Configure AI Models per module and general AI behavior.</p>
        </div>
    </div>

    <form action="{{ route('admin.ai.settings.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <!-- Left Column: Model Selection -->
            <div class="col-lg-7">
                <div class="premium-card h-100">
                    <h6 class="fw-bold mb-4 text-primary"><i class="fa-solid fa-layer-group me-2"></i>AI Model Selection</h6>
                    <p class="text-muted" style="font-size:0.85rem;">Assign specific AI models to power different parts of the system.</p>

                    @php
                        $modules = [
                            'model_mock_interview' => 'Mock Interview Analysis',
                            'model_ai_coach' => 'AI Career Coach',
                            'model_voice_rehearsal' => 'Voice Rehearsal Analysis',
                            'model_feedback' => 'Automated Feedback Generation',
                            'model_learning' => 'Learning Recommendations'
                        ];
                    @endphp

                    @foreach($modules as $key => $name)
                    <div class="module-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold" style="font-size:0.95rem;">{{ $name }}</div>
                        </div>
                        <div style="width: 250px;">
                            <select name="{{ $key }}" class="form-select input-dark">
                                <option value="">Default (Primary Provider)</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->name }}" {{ ($settings[$key] ?? '') == $provider->name ? 'selected' : '' }}>
                                        {{ $provider->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Column: General AI Config -->
            <div class="col-lg-5">
                <div class="premium-card h-100">
                    <h6 class="fw-bold mb-4 text-warning"><i class="fa-solid fa-gear me-2"></i>Global AI Configuration</h6>

                    <div class="mb-4">
                        <label class="form-label fw-bold" style="font-size:0.9rem;">Creativity Level (Temperature)</label>
                        <select name="ai_creativity" class="form-select input-dark">
                            <option value="low" {{ ($settings['ai_creativity'] ?? '') == 'low' ? 'selected' : '' }}>Low (Strict & Factual)</option>
                            <option value="medium" {{ ($settings['ai_creativity'] ?? 'medium') == 'medium' ? 'selected' : '' }}>Medium (Balanced)</option>
                            <option value="high" {{ ($settings['ai_creativity'] ?? '') == 'high' ? 'selected' : '' }}>High (Creative & Expressive)</option>
                        </select>
                        <div class="form-text text-muted mt-2" style="font-size:0.75rem;">Determines how varied the AI's responses will be.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold" style="font-size:0.9rem;">Response Length</label>
                        <select name="ai_response_length" class="form-select input-dark">
                            <option value="short" {{ ($settings['ai_response_length'] ?? '') == 'short' ? 'selected' : '' }}>Short (Concise)</option>
                            <option value="medium" {{ ($settings['ai_response_length'] ?? 'medium') == 'medium' ? 'selected' : '' }}>Medium (Standard)</option>
                            <option value="detailed" {{ ($settings['ai_response_length'] ?? '') == 'detailed' ? 'selected' : '' }}>Detailed (Comprehensive)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold" style="font-size:0.9rem;">Language</label>
                        <select name="ai_language" class="form-select input-dark">
                            <option value="english" {{ ($settings['ai_language'] ?? 'english') == 'english' ? 'selected' : '' }}>English</option>
                            <option value="filipino" {{ ($settings['ai_language'] ?? '') == 'filipino' ? 'selected' : '' }}>Filipino</option>
                            <option value="taglish" {{ ($settings['ai_language'] ?? '') == 'taglish' ? 'selected' : '' }}>Taglish</option>
                        </select>
                    </div>

                    <div class="mt-5 text-end">
                        <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
