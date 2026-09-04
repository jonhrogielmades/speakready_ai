@php
    $bestProvider = $summary['best_provider'] ?? null;
    $generatedOutputGroups = collect($generatedOutputs ?? []);
    $generatedOutputProviderCount = $generatedOutputGroups->where('has_generated_evidence', true)->count();
    $minimumProviders = $summary['minimum_required_providers'] ?? 3;
    $activeConfiguredProviders = $summary['active_configured_providers'] ?? 0;
    $userRequestLabel = $userRequestContext['label'] ?? 'No user request selected yet';
    $questionRouting = $interviewRouting['question_generation'] ?? [];
    $feedbackRouting = $interviewRouting['feedback_generation'] ?? [];
    $routingMeta = function (array $routing): string {
        $parts = array_values(array_filter([
            ($routing['rank_score'] ?? null) !== null ? 'Rank '.$routing['rank_score'].'%' : null,
            ($routing['accuracy_score'] ?? null) !== null ? 'Accuracy '.$routing['accuracy_score'].'%' : null,
            ($routing['reliability_score'] ?? null) !== null ? 'Reliability '.$routing['reliability_score'].'%' : null,
        ]));

        return $parts !== [] ? implode(' · ', $parts) : 'No routing evidence yet';
    };
@endphp

<div class="db-section active ai-evaluation-page ai-evaluation-report" id="sec-admin-ai-evaluation">
    <div class="ai-eval-header btn-no-print">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-pdf me-2"></i>AI Provider Evidence Report</h4>
            <p class="mb-0">Generated {{ now()->format('M d, Y g:i A') }}.</p>
        </div>
        <div class="ai-eval-actions">
            <a href="{{ route('admin.ai.evaluation', array_filter(['days' => $days, 'run' => $selectedRun?->id])) }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i>Back
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fa-solid fa-print me-2"></i>Save PDF
            </button>
        </div>
    </div>

    <div class="ai-report-paper">
        <div class="ai-report-title">
            <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
            <div>
                <h1>SpeakReady AI Provider Evaluation Evidence</h1>
                <p>Comparison version: {{ $selectedRun?->benchmark_version ?? 'No comparison run' }} · Evidence window: {{ $days }} days</p>
            </div>
        </div>

        <div class="ai-report-summary">
            <div><span>Best provider</span><strong>{{ $bestProvider['provider_name'] ?? 'No evidence' }}</strong></div>
            <div><span>Overall score</span><strong>{{ $bestProvider['overall_score'] ?? 0 }}%</strong></div>
            <div><span>Configured providers</span><strong>{{ $summary['configured_providers'] }}</strong></div>
            <div><span>Panelist 3+ API check</span><strong>{{ $activeConfiguredProviders }}/{{ $minimumProviders }} active APIs</strong></div>
            <div><span>Interview question provider</span><strong>{{ $questionRouting['provider_name'] ?? 'No ranking' }}</strong><small>{{ $routingMeta($questionRouting) }}</small></div>
            <div><span>Interview feedback provider</span><strong>{{ $feedbackRouting['provider_name'] ?? 'No ranking' }}</strong><small>{{ $routingMeta($feedbackRouting) }}</small></div>
        </div>

        <p class="ai-report-note">{{ $summary['panelist_requirement_label'] ?? 'Panelist requirement: evaluate 3 or more AI APIs.' }}</p>

        <h2>Provider Matrix</h2>
        <table class="ai-report-table">
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Overall</th>
                    <th>Question</th>
                    <th>Feedback</th>
                    <th>Reliability</th>
                    <th>Evidence</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rankedProviders as $provider)
                    <tr>
                        <td>{{ $provider['provider_name'] }}</td>
                        <td>{{ ucfirst($provider['status']) }}</td>
                        <td>{{ $provider['overall_score'] }}%</td>
                        <td>{{ $provider['question_generation_score'] !== null ? $provider['question_generation_score'].'%' : 'None' }}</td>
                        <td>{{ $provider['feedback_generation_score'] !== null ? $provider['feedback_generation_score'].'%' : 'None' }}</td>
                        <td>{{ $provider['operational_reliability_score'] !== null ? $provider['operational_reliability_score'].'%' : 'No logs' }}</td>
                        <td>{{ $provider['evidence_level'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Human Audit Evidence</h2>
        <table class="ai-report-table">
            <tbody>
                <tr><th>AI feedback records</th><td>{{ number_format($productionEvidence['feedback_total']) }}</td></tr>
                <tr><th>Admin-reviewed feedback</th><td>{{ number_format($productionEvidence['feedback_reviewed']) }}</td></tr>
                <tr><th>Approved or archived feedback</th><td>{{ number_format($productionEvidence['feedback_approved']) }}</td></tr>
                <tr><th>Flagged feedback</th><td>{{ number_format($productionEvidence['feedback_flagged']) }}</td></tr>
                <tr><th>Approval rate</th><td>{{ $productionEvidence['feedback_approval_rate'] !== null ? $productionEvidence['feedback_approval_rate'].'%' : 'No reviewed feedback' }}</td></tr>
                <tr><th>Generated question records</th><td>{{ number_format($productionEvidence['generated_question_records']) }}</td></tr>
                <tr><th>Provider log records</th><td>{{ number_format($productionEvidence['ai_log_count']) }}</td></tr>
            </tbody>
        </table>

        @if($selectedRun && $selectedRun->results->count() > 0)
            <h2>Provider Comparison Evidence</h2>
            <table class="ai-report-table">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Case</th>
                        <th>Status</th>
                        <th>Quality</th>
                        <th>Schema</th>
                        <th>Accuracy</th>
                        <th>Safety</th>
                        <th>Latency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selectedRun->results as $result)
                        <tr>
                            <td>{{ $result->provider_name }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $result->task_type)) }}</td>
                            <td>{{ ucfirst($result->status) }}</td>
                            <td>{{ $result->quality_score }}%</td>
                            <td>{{ $result->schema_score }}%</td>
                            <td>{{ $result->accuracy_score }}%</td>
                            <td>{{ $result->safety_score }}%</td>
                            <td>{{ number_format((int) $result->response_time_ms) }}ms</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(!empty($generatedOutputs))
            <h2>Ranked User-Requested Generated Outputs by AI Provider</h2>
            <p class="ai-report-note">Based on user request: {{ $userRequestLabel }}. {{ count($generatedOutputs) }} providers shown · {{ $generatedOutputProviderCount }} with generated user-request output.</p>
            @foreach($generatedOutputs as $outputGroup)
                <div class="ai-report-output-provider">
                    <h3>
                        {{ $outputGroup['provider_name'] }}
                        <span>{{ $outputGroup['provider_key'] }} · {{ $outputGroup['generated_rank_label'] ?? 'No user-requested output yet' }} · Best {{ $outputGroup['generated_rank_score_label'] ?? 'No scored output' }}</span>
                    </h3>

                    <div class="ai-report-output-block">
                        <strong>Generated Questions</strong>
                        @if(!empty($outputGroup['questions']))
                            <ol>
                                @foreach($outputGroup['questions'] as $question)
                                    @php
                                        $questionText = is_array($question) ? ($question['text'] ?? '') : $question;
                                        $questionMeta = is_array($question)
                                            ? array_values(array_filter([
                                                $question['source'] ?? null,
                                                $question['created_at'] ?? null,
                                                !empty($question['session_id']) ? 'Session #'.$question['session_id'] : null,
                                            ]))
                                            : [];
                                    @endphp
                                    <li>
                                        {{ $questionText }}
                                        @if($questionMeta !== [])
                                            <small>({{ implode(' · ', $questionMeta) }})</small>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p>No user-requested question output captured in this evidence window.</p>
                        @endif
                    </div>

                    <div class="ai-report-output-block">
                        <strong>Generated Feedback</strong>
                        @if(!empty($outputGroup['feedback']))
                            @foreach($outputGroup['feedback'] as $feedback)
                                <div class="ai-report-feedback-item">
                                    <p><strong>{{ $feedback['question_focus'] ?: 'Feedback item #'.$loop->iteration }}</strong></p>
                                    @php
                                        $feedbackMeta = array_values(array_filter([
                                            $feedback['source'] ?? null,
                                            $feedback['created_at'] ?? null,
                                            !empty($feedback['session_id']) ? 'Session #'.$feedback['session_id'] : null,
                                        ]));
                                    @endphp
                                    @if($feedbackMeta !== [])
                                        <p>Source: {{ implode(' · ', $feedbackMeta) }}</p>
                                    @endif
                                    @if(($feedback['score'] ?? null) !== null)
                                        <p>Score: {{ $feedback['score'] }}%</p>
                                    @endif
                                    @if(!empty($feedback['ai_feedback']))
                                        <p>Feedback: {{ $feedback['ai_feedback'] }}</p>
                                    @endif
                                    @if(!empty($feedback['evidence_quotes']))
                                        <p>Evidence quotes: {{ implode(' | ', $feedback['evidence_quotes']) }}</p>
                                    @endif
                                    @if(!empty($feedback['better_sample_answer']))
                                        <p>Better sample answer: {{ $feedback['better_sample_answer'] }}</p>
                                    @endif
                                    @if(!empty($feedback['follow_up_question']))
                                        <p>Follow-up question: {{ $feedback['follow_up_question'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <p>No user-requested feedback output captured in this evidence window.</p>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif

        <p class="ai-report-note">
            Automated benchmark scores estimate provider fit for this system using fixed, de-identified question and feedback cases. Human audit counts come from administrator-reviewed feedback records.
        </p>
    </div>
</div>
