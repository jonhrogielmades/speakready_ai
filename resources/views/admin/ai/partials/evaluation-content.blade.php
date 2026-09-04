@php
    $selectedRunId = $selectedRun?->id;
    $exportQuery = array_filter(['days' => $days, 'run' => $selectedRunId]);
    $realtimeUrl = route('admin.ai.evaluation.realtime', $exportQuery);
    $approvalRate = $productionEvidence['feedback_approval_rate'] ?? null;
    $bestProvider = $summary['best_provider'] ?? null;
    $generatedOutputGroups = collect($generatedOutputs ?? []);
    $generatedOutputProviderCount = $generatedOutputGroups->where('has_generated_evidence', true)->count();
    $generatedOutputQuestionCount = $generatedOutputGroups->sum(fn ($group) => count($group['questions'] ?? []));
    $generatedOutputFeedbackCount = $generatedOutputGroups->sum(fn ($group) => count($group['feedback'] ?? []));
    $panelistReady = (bool) ($summary['panelist_requirement_met'] ?? false);
    $minimumProviders = $summary['minimum_required_providers'] ?? 3;
    $activeConfiguredProviders = $summary['active_configured_providers'] ?? 0;
    $userRequestLabel = $userRequestContext['label'] ?? 'No user request selected yet';
    $userRequestNote = $userRequestContext['note'] ?? 'Only generated questions and feedback tied to the selected user request are shown.';
    $questionRouting = $interviewRouting['question_generation'] ?? [];
    $feedbackRouting = $interviewRouting['feedback_generation'] ?? [];
    $routingMeta = function (array $routing): string {
        $parts = array_values(array_filter([
            ($routing['rank_score'] ?? null) !== null ? 'Rank '.$routing['rank_score'].'%' : null,
            ($routing['accuracy_score'] ?? null) !== null ? 'Accuracy '.$routing['accuracy_score'].'%' : null,
            ($routing['reliability_score'] ?? null) !== null ? 'Reliability '.$routing['reliability_score'].'%' : null,
        ]));

        return $parts !== [] ? implode(' · ', $parts) : 'Run comparison evidence to route interviews by ranking.';
    };
@endphp

<div class="db-section active ai-evaluation-page" id="sec-admin-ai-evaluation">
    @if(session('message'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="ai-eval-header">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-simple me-2"></i>AI Provider Evaluation</h4>
            <p class="mb-0">Evidence for question generation, feedback generation, reliability, and review readiness.</p>
        </div>
        <div class="ai-eval-actions">
            <form action="{{ route('admin.ai.evaluation.run') }}" method="POST">
                @csrf
                <button type="submit"
                    class="btn btn-primary"
                    @disabled(!$panelistReady)
                    title="{{ $panelistReady ? 'Compare all active configured AI providers.' : 'Configure at least '.$minimumProviders.' active AI providers before running the panelist comparison.' }}">
                    <i class="fa-solid fa-layer-group me-2"></i>Compare All Providers
                </button>
            </form>
            <a href="{{ route('admin.ai.evaluation.export', $exportQuery) }}" class="btn btn-success">
                <i class="fa-solid fa-file-excel me-2"></i>Excel CSV
            </a>
            <form action="{{ route('admin.ai.evaluation.clear') }}" method="POST" onsubmit="return confirm('Clear all AI provider evaluation evidence from this admin view? User sessions, answers, and question text will stay saved.');">
                @csrf
                <button type="submit" class="btn ai-eval-clear-btn">
                    <i class="fa-solid fa-trash-can me-2"></i>Clear All
                </button>
            </form>
        </div>
    </div>

    <form class="ai-eval-filter" method="GET" action="{{ route('admin.ai.evaluation') }}">
        <label>
            <span>Evidence Window</span>
            <select name="days" class="form-select input-dark" onchange="this.form.submit()">
                @foreach([7, 14, 30, 60, 90, 180, 365] as $option)
                    <option value="{{ $option }}" @selected((int) $days === $option)>Last {{ $option }} days</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>Comparison Run</span>
            <select name="run" class="form-select input-dark" onchange="this.form.submit()">
                <option value="">Latest comparison run</option>
                @foreach($recentRuns as $run)
                    <option value="{{ $run->id }}" @selected($selectedRunId === $run->id)>
                        Run #{{ $run->id }} - {{ optional($run->completed_at ?? $run->created_at)->format('M d, Y g:i A') }}
                    </option>
                @endforeach
            </select>
        </label>
        <noscript>
            <button type="submit" class="btn btn-secondary">Apply</button>
        </noscript>
    </form>

    <div class="ai-eval-realtime-bar">
        <div>
            <span class="ai-live-dot" aria-hidden="true"></span>
            <strong>Realtime Evaluation</strong>
        </div>
        <small data-ai-eval-live-status>Live user-request evidence ready · updates every 8 seconds</small>
    </div>

    <div class="ai-panelist-requirement {{ $panelistReady ? 'met' : 'warning' }}">
        <div>
            <strong>Panelist requirement: evaluate 3 or more AI APIs</strong>
            <small>{{ $summary['panelist_requirement_label'] ?? 'Configure at least 3 active AI APIs before running comparison evidence.' }}</small>
        </div>
        <span>{{ $activeConfiguredProviders }}/{{ $minimumProviders }} active APIs</span>
    </div>

    <div id="ai-provider-evaluation-live"
        data-ai-eval-live
        data-live-url="{{ $realtimeUrl }}"
        data-live-interval="8000">
    <div class="ai-eval-summary-grid">
        <div class="ai-eval-card">
            <span class="ai-eval-card-icon text-primary"><i class="fa-solid fa-trophy"></i></span>
            <strong>{{ $bestProvider['provider_name'] ?? 'No Evidence' }}</strong>
            <small>Best Overall Provider</small>
            <b>{{ $bestProvider['overall_score'] ?? 0 }}%</b>
        </div>
        <div class="ai-eval-card">
            <span class="ai-eval-card-icon text-info"><i class="fa-solid fa-plug-circle-check"></i></span>
            <strong>{{ $summary['configured_providers'] }}</strong>
            <small>Configured Providers</small>
            <b>{{ $activeConfiguredProviders }}/{{ $minimumProviders }} active APIs required</b>
        </div>
        <div class="ai-eval-card">
            <span class="ai-eval-card-icon text-success"><i class="fa-solid fa-bullseye"></i></span>
            <strong>{{ $summary['benchmark_average'] !== null ? $summary['benchmark_average'].'%' : 'None' }}</strong>
            <small>Comparison Average</small>
            <b>{{ $summary['latest_run_label'] }}</b>
        </div>
        <div class="ai-eval-card">
            <span class="ai-eval-card-icon text-warning"><i class="fa-solid fa-clipboard-check"></i></span>
            <strong>{{ $approvalRate !== null ? $approvalRate.'%' : 'No review' }}</strong>
            <small>Human Audit Approval</small>
            <b>{{ number_format($productionEvidence['feedback_reviewed']) }} reviewed</b>
        </div>
        <div class="ai-eval-card">
            <span class="ai-eval-card-icon text-primary"><i class="fa-solid fa-circle-question"></i></span>
            <strong>{{ $questionRouting['provider_name'] ?? 'No Ranking' }}</strong>
            <small>Interview Questions Use</small>
            <b>{{ $routingMeta($questionRouting) }}</b>
        </div>
        <div class="ai-eval-card">
            <span class="ai-eval-card-icon text-success"><i class="fa-solid fa-comments"></i></span>
            <strong>{{ $feedbackRouting['provider_name'] ?? 'No Ranking' }}</strong>
            <small>Interview Feedback Uses</small>
            <b>{{ $routingMeta($feedbackRouting) }}</b>
        </div>
    </div>

    <div class="premium-card ai-eval-panel mb-4">
        <div class="ai-eval-panel-title">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-ranking-star me-2 text-primary"></i>Provider Evidence Matrix</h5>
            <span class="stat-badge primary">{{ count($providers) }} providers</span>
        </div>
        <div class="table-responsive">
            <table class="table custom-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Overall</th>
                        <th>Questions</th>
                        <th>Feedback</th>
                        <th>Reliability</th>
                        <th>Latency</th>
                        <th>Evidence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rankedProviders as $provider)
                        <tr>
                            <td>
                                <div class="ai-eval-provider-name">{{ $provider['provider_name'] }}</div>
                                <small>{{ $provider['role'] }} · {{ $provider['provider_key'] }}</small>
                            </td>
                            <td>
                                <span class="stat-badge {{ $provider['status'] === 'active' ? 'success' : ($provider['status'] === 'unconfigured' ? 'secondary' : 'danger') }}">
                                    {{ ucfirst($provider['status']) }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $provider['overall_score'] }}%</strong>
                                <small class="d-block">{{ $provider['quality_label'] }}</small>
                            </td>
                            <td>{{ $provider['question_generation_score'] !== null ? $provider['question_generation_score'].'%' : 'None' }}</td>
                            <td>{{ $provider['feedback_generation_score'] !== null ? $provider['feedback_generation_score'].'%' : 'None' }}</td>
                            <td>
                                {{ $provider['operational_reliability_score'] !== null ? $provider['operational_reliability_score'].'%' : 'No logs' }}
                                <small class="d-block">{{ $provider['success_rate'] !== null ? $provider['success_rate'].'% success' : '' }}</small>
                            </td>
                            <td>{{ $provider['avg_latency_ms'] !== null ? number_format($provider['avg_latency_ms']).'ms' : 'No logs' }}</td>
                            <td>
                                <span class="ai-eval-evidence-level">{{ $provider['evidence_level'] }}</span>
                                <small class="d-block">{{ $provider['evidence_note'] }}</small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="premium-card ai-eval-panel mb-4">
        <div class="ai-eval-panel-title">
            <div>
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-file-lines me-2 text-info"></i>Ranked User-Requested Generated Questions and Feedback by AI Provider</h5>
                <small class="ai-output-context">Based on user request: {{ $userRequestLabel }}. {{ $userRequestNote }}</small>
            </div>
            <span class="stat-badge {{ $generatedOutputProviderCount > 0 ? 'success' : 'secondary' }}">
                {{ count($generatedOutputs) }} providers · {{ $generatedOutputProviderCount }} with output · {{ $generatedOutputQuestionCount }} questions · {{ $generatedOutputFeedbackCount }} feedback
            </span>
        </div>

        @if(!empty($generatedOutputs))
            <div class="ai-generated-output-grid">
                @foreach($generatedOutputs as $outputGroup)
                    @php
                        $providerDomKey = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($outputGroup['provider_key'] ?? $loop->index));
                        $bestQuestionPanelId = 'ai-best-questions-'.$providerDomKey;
                        $bestFeedbackPanelId = 'ai-best-feedback-'.$providerDomKey;
                        $bestQuestions = $outputGroup['best_questions'] ?? [];
                        $bestFeedback = $outputGroup['best_feedback'] ?? [];
                    @endphp
                    <article class="ai-provider-output-card">
                        <div class="ai-provider-output-head">
                            <div>
                                <strong>{{ $outputGroup['provider_name'] }}</strong>
                                <small>{{ $outputGroup['provider_key'] }}</small>
                                <div class="ai-provider-output-meta">
                                    <span class="ai-provider-output-rank">{{ $outputGroup['generated_rank_label'] ?? 'No user-requested output yet' }}</span>
                                    <span>{{ ($outputGroup['has_generated_evidence'] ?? false) ? 'Best ' : '' }}{{ $outputGroup['generated_rank_score_label'] ?? 'No scored output' }}</span>
                                </div>
                            </div>
                            <span>{{ count($outputGroup['questions']) }} questions · {{ count($outputGroup['feedback']) }} feedback</span>
                        </div>

                        <div class="ai-best-action-row">
                            <button type="button"
                                class="ai-best-action-btn question"
                                data-ai-best-toggle
                                data-target="#{{ $bestQuestionPanelId }}"
                                aria-controls="{{ $bestQuestionPanelId }}"
                                aria-expanded="false">
                                <i class="fa-solid fa-circle-question"></i>
                                View Best Questions
                                <b>{{ count($bestQuestions) }}</b>
                            </button>
                            <button type="button"
                                class="ai-best-action-btn feedback"
                                data-ai-best-toggle
                                data-target="#{{ $bestFeedbackPanelId }}"
                                aria-controls="{{ $bestFeedbackPanelId }}"
                                aria-expanded="false">
                                <i class="fa-solid fa-comments"></i>
                                View Best Feedback
                                <b>{{ count($bestFeedback) }}</b>
                            </button>
                        </div>

                        <div id="{{ $bestQuestionPanelId }}" class="ai-best-panel" hidden>
                            <h6>Best Generated Questions</h6>
                            @if(!empty($bestQuestions))
                                <ol class="ai-best-list">
                                    @foreach($bestQuestions as $question)
                                        @php
                                            $questionText = is_array($question) ? ($question['text'] ?? '') : $question;
                                            $questionMeta = is_array($question)
                                                ? array_values(array_filter([
                                                    ($question['best_score'] ?? null) !== null ? 'Score '.$question['best_score'].'%' : null,
                                                    $question['source'] ?? null,
                                                    $question['created_at'] ?? null,
                                                ]))
                                                : [];
                                        @endphp
                                        <li>
                                            <span>{{ $questionText }}</span>
                                            @if($questionMeta !== [])
                                                <small>{{ implode(' · ', $questionMeta) }}</small>
                                            @endif
                                            @if(!empty($question['best_reason']))
                                                <small>{{ $question['best_reason'] }}</small>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <p class="ai-output-empty">No ranked generated questions are available for this provider yet.</p>
                            @endif
                        </div>

                        <div id="{{ $bestFeedbackPanelId }}" class="ai-best-panel" hidden>
                            <h6>Best Generated Feedback</h6>
                            @if(!empty($bestFeedback))
                                <div class="ai-best-list">
                                    @foreach($bestFeedback as $feedback)
                                        <article class="ai-best-feedback-item">
                                            <strong>{{ $feedback['question_focus'] ?: 'Feedback item #'.$loop->iteration }}</strong>
                                            <div class="ai-feedback-scores">
                                                @if(($feedback['best_score'] ?? null) !== null)
                                                    <span>Score {{ $feedback['best_score'] }}%</span>
                                                @endif
                                                @if(!empty($feedback['source']))
                                                    <span>{{ $feedback['source'] }}</span>
                                                @endif
                                                @if(!empty($feedback['created_at']))
                                                    <span>{{ $feedback['created_at'] }}</span>
                                                @endif
                                            </div>
                                            @if(!empty($feedback['ai_feedback']))
                                                <p class="ai-feedback-text">{{ $feedback['ai_feedback'] }}</p>
                                            @endif
                                            @if(!empty($feedback['best_reason']))
                                                <small>{{ $feedback['best_reason'] }}</small>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <p class="ai-output-empty">No ranked generated feedback is available for this provider yet.</p>
                            @endif
                        </div>

                        <section class="ai-output-section">
                            <h6>Generated Questions</h6>
                            @if(!empty($outputGroup['questions']))
                                <ol class="ai-question-list">
                                    @foreach($outputGroup['questions'] as $question)
                                        @php
                                            $questionText = is_array($question) ? ($question['text'] ?? '') : $question;
                                            $questionMeta = is_array($question)
                                                ? array_values(array_filter([
                                                    $question['source'] ?? null,
                                                    $question['created_at'] ?? null,
                                                    !empty($question['session_id']) ? 'Session #'.$question['session_id'] : null,
                                                    ($question['best_score'] ?? null) !== null ? 'Score '.$question['best_score'].'%' : null,
                                                ]))
                                                : [];
                                        @endphp
                                        <li>
                                            <span>{{ $questionText }}</span>
                                            @if($questionMeta !== [])
                                                <small>{{ implode(' · ', $questionMeta) }}</small>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <p class="ai-output-empty">No user-requested question output captured in this evidence window.</p>
                            @endif
                        </section>

                        <section class="ai-output-section">
                            <h6>Generated Feedback</h6>
                            @if(!empty($outputGroup['feedback']))
                                <div class="ai-feedback-list">
                                    @foreach($outputGroup['feedback'] as $feedback)
                                        <article class="ai-feedback-item">
                                            <div class="ai-feedback-question">
                                                {{ $feedback['question_focus'] ?: 'Feedback item #'.$loop->iteration }}
                                            </div>

                                            <div class="ai-feedback-scores">
                                                @if(!empty($feedback['source']))
                                                    <span>{{ $feedback['source'] }}</span>
                                                @endif
                                                @if(!empty($feedback['created_at']))
                                                    <span>{{ $feedback['created_at'] }}</span>
                                                @endif
                                                @if(!empty($feedback['session_id']))
                                                    <span>Session #{{ $feedback['session_id'] }}</span>
                                                @endif
                                                @if(($feedback['score'] ?? null) !== null)
                                                    <span>Overall {{ $feedback['score'] }}%</span>
                                                @endif
                                                @if(($feedback['comparison_score'] ?? null) !== null)
                                                    <span>Comparison {{ $feedback['comparison_score'] }}%</span>
                                                @endif
                                                @foreach([
                                                    'clarity_score' => 'Clarity',
                                                    'relevance_score' => 'Relevance',
                                                    'grammar_score' => 'Grammar',
                                                    'professionalism_score' => 'Professionalism',
                                                    'star_method_score' => 'STAR',
                                                ] as $scoreKey => $scoreLabel)
                                                    @if(($feedback[$scoreKey] ?? null) !== null)
                                                        <span>{{ $scoreLabel }} {{ $feedback[$scoreKey] }}%</span>
                                                    @endif
                                                @endforeach
                                                @if(!empty($feedback['answer_alignment']))
                                                    <span>{{ str_replace('_', ' ', $feedback['answer_alignment']) }}</span>
                                                @endif
                                            </div>

                                            @if(!empty($feedback['ai_feedback']))
                                                <p class="ai-feedback-text"><strong>Feedback:</strong> {{ $feedback['ai_feedback'] }}</p>
                                            @endif

                                            @if(!empty($feedback['evidence_quotes']))
                                                <div class="ai-evidence-quotes">
                                                    <strong>Evidence quotes</strong>
                                                    <ul>
                                                        @foreach($feedback['evidence_quotes'] as $quote)
                                                            <li>{{ $quote }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            @if(!empty($feedback['better_sample_answer']))
                                                <p class="ai-feedback-text"><strong>Better sample answer:</strong> {{ $feedback['better_sample_answer'] }}</p>
                                            @endif

                                            @if(!empty($feedback['follow_up_question']))
                                                <p class="ai-feedback-text"><strong>Follow-up question:</strong> {{ $feedback['follow_up_question'] }}</p>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <p class="ai-output-empty">No user-requested feedback output captured in this evidence window.</p>
                            @endif
                        </section>
                    </article>
                @endforeach
            </div>
        @else
            <div class="ai-eval-empty">
                <i class="fa-solid fa-file-circle-question"></i>
                <strong>No user-requested generated output yet.</strong>
                <span>New generated questions and feedback from real user sessions will appear here automatically.</span>
            </div>
        @endif
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="premium-card ai-eval-panel h-100">
                <div class="ai-eval-panel-title">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-user-check me-2 text-success"></i>Human Audit Evidence</h5>
                </div>
                <div class="ai-eval-metric-list">
                    <div><span>AI feedback records</span><strong>{{ number_format($productionEvidence['feedback_total']) }}</strong></div>
                    <div><span>Reviewed by admin</span><strong>{{ number_format($productionEvidence['feedback_reviewed']) }}</strong></div>
                    <div><span>Approved or archived</span><strong>{{ number_format($productionEvidence['feedback_approved']) }}</strong></div>
                    <div><span>Flagged feedback</span><strong>{{ number_format($productionEvidence['feedback_flagged']) }}</strong></div>
                    <div><span>Average feedback score</span><strong>{{ $productionEvidence['avg_feedback_score'] !== null ? $productionEvidence['avg_feedback_score'].'%' : 'No data' }}</strong></div>
                    <div><span>Average scoring confidence</span><strong>{{ $productionEvidence['avg_scoring_confidence'] !== null ? $productionEvidence['avg_scoring_confidence'].'%' : 'No data' }}</strong></div>
                    <div><span>Generated question records</span><strong>{{ number_format($productionEvidence['generated_question_records']) }}</strong></div>
                    <div><span>Provider log records</span><strong>{{ number_format($productionEvidence['ai_log_count']) }}</strong></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="premium-card ai-eval-panel h-100">
                <div class="ai-eval-panel-title">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-info"></i>Comparison Cases</h5>
                    <span class="stat-badge primary">{{ count($benchmarkCases) }} cases</span>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Case</th>
                                <th>Task</th>
                                <th>Evidence Focus</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($benchmarkCases as $case)
                                <tr>
                                    <td class="fw-bold">{{ $case['title'] }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $case['task_type'])) }}</td>
                                    <td>{{ $case['evidence_focus'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="premium-card ai-eval-panel">
        <div class="ai-eval-panel-title">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-magnifying-glass-chart me-2 text-warning"></i>Provider Comparison Evidence</h5>
            <span class="stat-badge {{ $selectedRun ? 'success' : 'secondary' }}">{{ $selectedRun ? 'Run #'.$selectedRun->id : 'No run' }}</span>
        </div>

        @if($selectedRun && $selectedRun->results->count() > 0)
            <div class="table-responsive">
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Task</th>
                            <th>Status</th>
                            <th>Quality</th>
                            <th>Schema</th>
                            <th>Accuracy</th>
                            <th>Safety</th>
                            <th>Latency</th>
                            <th>Output Evidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($selectedRun->results as $result)
                            <tr>
                                <td class="fw-bold">{{ $result->provider_name }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $result->task_type)) }}</td>
                                <td>
                                    <span class="stat-badge {{ $result->status === 'success' ? 'success' : 'danger' }}">
                                        {{ ucfirst($result->status) }}
                                    </span>
                                </td>
                                <td>{{ $result->quality_score }}%</td>
                                <td>{{ $result->schema_score }}%</td>
                                <td>{{ $result->accuracy_score }}%</td>
                                <td>{{ $result->safety_score }}%</td>
                                <td>{{ number_format((int) $result->response_time_ms) }}ms</td>
                                <td>
                                    @php
                                        $benchmarkEvidence = collect($result->evidence ?? [])
                                            ->except(['generated_questions', 'generated_feedback', 'sample_output'])
                                            ->all();
                                        $benchmarkEvidenceText = $result->error_message
                                            ?: (empty($benchmarkEvidence)
                                                ? 'No score evidence captured.'
                                                : json_encode($benchmarkEvidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                                    @endphp
                                    <details>
                                        <summary>View evidence</summary>
                                        <pre>{{ $benchmarkEvidenceText }}</pre>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ai-eval-empty">
                <i class="fa-solid fa-flask"></i>
                <strong>No provider comparison evidence yet.</strong>
                <span>Compare providers after configuring at least one provider key and capturing a user answer.</span>
            </div>
        @endif
    </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        if (document.documentElement.dataset.aiBestEvidenceBound !== '1') {
            document.documentElement.dataset.aiBestEvidenceBound = '1';
            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-ai-best-toggle]');
                if (!button) {
                    return;
                }

                const target = button.dataset.target;
                if (!target) {
                    return;
                }

                const panel = document.querySelector(target);
                if (!panel) {
                    return;
                }

                const card = button.closest('.ai-provider-output-card');
                const shouldOpen = panel.hidden;

                if (card) {
                    card.querySelectorAll('.ai-best-panel').forEach((item) => {
                        item.hidden = true;
                    });
                    card.querySelectorAll('[data-ai-best-toggle]').forEach((item) => {
                        item.setAttribute('aria-expanded', 'false');
                    });
                }

                panel.hidden = !shouldOpen;
                button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            });
        }

        const liveRoot = document.querySelector('[data-ai-eval-live]');
        if (!liveRoot || liveRoot.dataset.bound === '1') {
            return;
        }

        liveRoot.dataset.bound = '1';

        const status = document.querySelector('[data-ai-eval-live-status]');
        const intervalMs = Math.max(5000, Number.parseInt(liveRoot.dataset.liveInterval || '8000', 10));
        let inFlight = false;

        const setStatus = (message, state = 'ready') => {
            if (!status) {
                return;
            }

            status.textContent = message;
            status.dataset.state = state;
        };

        const refreshEvaluation = async () => {
            if (document.hidden || inFlight) {
                return;
            }

            inFlight = true;
            setStatus('Checking live provider evidence...', 'loading');

            try {
                const response = await fetch(liveRoot.dataset.liveUrl, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Realtime refresh failed with ${response.status}`);
                }

                const payload = await response.json();
                const doc = new DOMParser().parseFromString(payload.html || '', 'text/html');
                const freshRoot = doc.querySelector('[data-ai-eval-live]');

                if (!freshRoot) {
                    throw new Error('Realtime refresh did not include an evidence panel.');
                }

                liveRoot.innerHTML = freshRoot.innerHTML;
                setStatus(
                    `Live updated ${payload.updated_at} · ${payload.active_configured_provider_count || 0}/${payload.minimum_required_provider_count || 3} active APIs · ${payload.generated_provider_count || 0}/${payload.total_provider_count || 0} providers ranked · ${payload.generated_question_count || 0} questions · ${payload.generated_feedback_count || 0} feedback`,
                    'ready'
                );
            } catch (error) {
                setStatus('Realtime refresh retrying...', 'error');
            } finally {
                inFlight = false;
            }
        };

        window.setInterval(refreshEvaluation, intervalMs);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                refreshEvaluation();
            }
        });
    })();
</script>
@endpush
