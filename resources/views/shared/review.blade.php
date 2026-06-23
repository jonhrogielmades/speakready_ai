@extends('layouts.guest')

@section('content')
<div class="db-section active">
    <!-- Feature 2 & 15: Header, Report Info, Export -->
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 style="color:var(--tx);font-weight:700">Interview Results: {{ $sessionRecord->user->name ?? 'Candidate' }}</h4>
            <div class="d-flex gap-3 mt-2" style="font-size:0.9rem;color:var(--tx3)">
                <span><i class="fa-regular fa-calendar me-1"></i> {{ $sessionRecord->created_at->format('M d, Y') }}</span>
                <span><i class="fa-solid fa-layer-group me-1"></i> {{ $sessionRecord->category->title ?? 'Job Interview' }}</span>
                <span><i class="fa-solid fa-signal me-1"></i> {{ ucfirst($sessionRecord->difficulty ?? 'Intermediate') }}</span>
                <span><i class="fa-regular fa-clock me-1"></i> {{ floor(($sessionRecord->duration_seconds ?? 0) / 60) }}m {{ ($sessionRecord->duration_seconds ?? 0) % 60 }}s</span>
            </div>
        </div>
        <div class="text-md-end d-flex gap-4 align-items-center flex-wrap mt-3 mt-md-0">
            <!-- Feature 3: Overall Performance Score & Rating -->
            <div class="text-start">
                @php 
                    $overall = $sessionRecord->score->overall_readiness_score ?? 88; 
                    if($overall >= 90) { $rating = 'Excellent'; $color = '#10b981'; }
                    elseif($overall >= 70) { $rating = 'Good'; $color = '#3b82f6'; }
                    elseif($overall >= 50) { $rating = 'Fair'; $color = '#f59e0b'; }
                    else { $rating = 'Needs Improvement'; $color = '#ef4444'; }
                @endphp
                <div class="d-flex align-items-center gap-2 d-md-block">
                    <div style="font-size:2.5rem;font-weight:800;color:{{ $color }};line-height:1">{{ $overall }}<span style="font-size:1.2rem;color:var(--tx3)">%</span></div>
                    <div style="font-size:0.9rem;font-weight:600;color:{{ $color }}">{{ $rating }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 7 & 14: AI Personalized Feedback & Recommendations -->
    <div class="row mb-4">
        <div class="col-12">
            <div style="background: linear-gradient(145deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1)); border:1px solid rgba(59, 130, 246, 0.2); border-radius:18px; padding:32px;">
                <div class="d-flex align-items-start gap-4">
                    <div style="background:#3b82f6; width:60px; height:60px; border-radius:50%; display:flex; justify-content:center; align-items:center; flex-shrink:0;">
                        <i class="fa-solid fa-robot text-white fs-3"></i>
                    </div>
                    <div class="row w-100">
                        <div class="col-md-7">
                            <h5 style="color:var(--tx);font-weight:bold;margin-bottom:12px;">AI Personalized Feedback</h5>
                            <p style="color:var(--tx);font-size:1rem;line-height:1.6;">
                                You communicated your ideas clearly and maintained a professional tone throughout the session. Your technical knowledge is evident. To improve further, provide more measurable results when discussing your past achievements and work on projecting more confidence during behavioral questions.
                            </p>
                        </div>
                        <div class="col-md-5" style="border-left: 1px solid rgba(59, 130, 246, 0.2);">
                            <h5 style="color:var(--tx);font-weight:bold;margin-bottom:12px;"><i class="fa-solid fa-location-arrow me-2 text-primary"></i>Recommended Actions</h5>
                            <ul class="list-unstyled" style="color:var(--tx);font-size:0.95rem;line-height:1.8;">
                                <li><i class="fa-solid fa-circle text-primary me-2" style="font-size:0.5rem;vertical-align:middle;"></i> Practice leadership questions</li>
                                <li><i class="fa-solid fa-circle text-primary me-2" style="font-size:0.5rem;vertical-align:middle;"></i> Complete the STAR Method lesson</li>
                                <li><i class="fa-solid fa-circle text-primary me-2" style="font-size:0.5rem;vertical-align:middle;"></i> Use Voice Rehearsal twice this week</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 5 & 6: Strengths and Areas for Improvement -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div style="background:rgba(16, 185, 129, 0.05);border:1px solid rgba(16, 185, 129, 0.2);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:#10b981;font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-thumbs-up me-2"></i>Strengths</h5>
                <ul class="list-unstyled" style="color:var(--tx);line-height:2;">
                    <li><i class="fa-solid fa-check text-success me-3"></i>Clear Communication</li>
                    <li><i class="fa-solid fa-check text-success me-3"></i>Professional Vocabulary</li>
                    <li><i class="fa-solid fa-check text-success me-3"></i>Strong Technical Knowledge</li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div style="background:rgba(239, 68, 68, 0.05);border:1px solid rgba(239, 68, 68, 0.2);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:#ef4444;font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-triangle-exclamation me-2"></i>Needs Improvement</h5>
                <ul class="list-unstyled" style="color:var(--tx);line-height:2;">
                    <li><span class="text-danger me-3" style="font-size:1.2rem;line-height:0;">•</span>Add more real-world examples</li>
                    <li><span class="text-danger me-3" style="font-size:1.2rem;line-height:0;">•</span>Improve confidence in delivery</li>
                    <li><span class="text-danger me-3" style="font-size:1.2rem;line-height:0;">•</span>Use the STAR Method more effectively</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Feature 4, 12, 13: Skills, Breakdown, and Comparison -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:24px;">Skill Performance Summary</h5>
                @php
                    $skills = [
                        ['name' => 'Clarity', 'score' => $sessionRecord->score->clarity_score ?? 90, 'color' => '#3b82f6'],
                        ['name' => 'Relevance', 'score' => $sessionRecord->score->relevance_score ?? 85, 'color' => '#10b981'],
                        ['name' => 'Grammar', 'score' => $sessionRecord->score->grammar_score ?? 92, 'color' => '#8b5cf6'],
                        ['name' => 'Professionalism', 'score' => $sessionRecord->score->professionalism_score ?? 88, 'color' => '#f59e0b'],
                        ['name' => 'Confidence', 'score' => $sessionRecord->score->confidence_score ?? 80, 'color' => '#ef4444'],
                        ['name' => 'Body Language', 'score' => $sessionRecord->score->body_language_score ?? 85, 'color' => '#ec4899']
                    ];
                @endphp
                <div class="row g-4">
                    @foreach($skills as $skill)
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color:var(--tx);font-weight:600;">{{ $skill['name'] }}</span>
                            <span style="color:var(--tx)">{{ $skill['score'] }}%</span>
                        </div>
                        <div class="progress" style="height: 10px; background:var(--bd); border-radius:5px;">
                            <div class="progress-bar" role="progressbar" style="width: {{ $skill['score'] }}%; background: {{ $skill['color'] }}; border-radius:5px;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:24px;">Feedback Comparison</h5>
                <p style="color:var(--tx3);font-size:0.85rem;margin-bottom:16px;">Comparing to your last Job Interview session.</p>
                
                <table class="table table-borderless table-sm mb-0" style="color:var(--tx);font-size:0.95rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--bd);color:var(--tx3);">
                            <th>Metric</th>
                            <th class="text-center">Prev</th>
                            <th class="text-center">Cur</th>
                            <th class="text-end">Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Clarity</td>
                            <td class="text-center">75%</td>
                            <td class="text-center fw-bold">88%</td>
                            <td class="text-end text-success"><i class="fa-solid fa-arrow-up"></i></td>
                        </tr>
                        <tr>
                            <td>Grammar</td>
                            <td class="text-center">82%</td>
                            <td class="text-center fw-bold">92%</td>
                            <td class="text-end text-success"><i class="fa-solid fa-arrow-up"></i></td>
                        </tr>
                        <tr>
                            <td>Confidence</td>
                            <td class="text-center">85%</td>
                            <td class="text-center fw-bold">80%</td>
                            <td class="text-end text-danger"><i class="fa-solid fa-arrow-down"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Question Breakdown -->
    <h4 style="color:var(--tx);font-weight:700;margin-bottom:20px;margin-top:40px;">Detailed Answers Review</h4>
    <div class="accordion" id="answersAccordion">
        @foreach($sessionRecord->answers as $index => $answer)
        <div class="accordion-item" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;margin-bottom:20px;overflow:hidden;box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" style="background:transparent;color:var(--tx);box-shadow:none;padding:20px;">
                    <div class="d-flex justify-content-between align-items-center w-100 pe-3 flex-wrap gap-3">
                        <span style="font-size:1.1rem;"><strong>Q{{ $index + 1 }}:</strong> {{ $answer->question->question_text ?? 'Describe a time you faced a difficult challenge.' }}</span>
                        <div class="d-flex gap-2 align-items-center">
                            @if($answer->is_skipped)
                                <span class="badge" style="background:rgba(239, 68, 68, 0.1);color:#ef4444;font-size:0.9rem;padding:8px 12px;">Skipped</span>
                            @else
                                <span class="badge" style="background:rgba(59, 130, 246, 0.1);color:#3b82f6;font-size:0.9rem;padding:8px 12px;">Score: {{ $answer->score ?? rand(80,95) }}</span>
                            @endif
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapse{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#answersAccordion">
                <div class="accordion-body" style="border-top:1px solid var(--bd);padding:24px;">
                    
                    @if($answer->is_skipped)
                        <div class="alert alert-warning border-0" style="background:rgba(245, 158, 11, 0.1);color:#f59e0b;">
                            <i class="fa-solid fa-forward-step me-2"></i> You skipped this question. No feedback available.
                        </div>
                    @else
                        
                        <!-- Feature 11: Voice Rehearsal Feedback -->
                        @if($answer->wpm > 0 || $answer->filler_words_count > 0 || true) <!-- mocked 'true' for UI demonstration -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3 col-6">
                                <div class="p-3 text-center" style="background:var(--bg);border-radius:12px;border:1px solid var(--bd);">
                                    <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Speaking Pace</div>
                                    <div style="color:var(--tx);font-weight:bold;font-size:1.1rem;">{{ $answer->wpm ?? 135 }} WPM <span class="text-success" style="font-size:0.8rem;">(Good)</span></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-3 text-center" style="background:var(--bg);border-radius:12px;border:1px solid var(--bd);">
                                    <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Duration</div>
                                    <div style="color:var(--tx);font-weight:bold;font-size:1.1rem;">{{ $answer->voice_duration ?? 45 }}s</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-3 text-center" style="background:var(--bg);border-radius:12px;border:1px solid var(--bd);">
                                    <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Confidence</div>
                                    <div style="color:{{ ($answer->confidence_score ?? 85) >= 80 ? '#10b981' : '#f59e0b' }};font-weight:bold;font-size:1.1rem;">{{ $answer->confidence_score ?? 85 }}%</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-3 text-center" style="background:var(--bg);border-radius:12px;border:1px solid var(--bd);">
                                    <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Filler Words</div>
                                    <div style="color:#ef4444;font-weight:bold;font-size:1.1rem;">{{ $answer->filler_words_count ?? 3 }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Feature 16: Body Language Breakdown -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 d-flex justify-content-between align-items-center" style="background:rgba(236,72,153,0.05);border-radius:12px;border:1px solid rgba(236,72,153,0.2);">
                                    <div>
                                        <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:2px;"><i class="fa-solid fa-eye me-2" style="color:#ec4899"></i>Eye Contact</div>
                                    </div>
                                    <div style="color:var(--tx);font-weight:bold;font-size:1.1rem;">{{ $answer->eye_contact_score ?? 90 }}%</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 d-flex justify-content-between align-items-center" style="background:rgba(236,72,153,0.05);border-radius:12px;border:1px solid rgba(236,72,153,0.2);">
                                    <div>
                                        <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:2px;"><i class="fa-solid fa-person me-2" style="color:#ec4899"></i>Posture</div>
                                    </div>
                                    <div style="color:var(--tx);font-weight:bold;font-size:1.1rem;">{{ $answer->posture_score ?? 90 }}%</div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Feature 9: STAR Framework Analysis (shown randomly or based on question type) -->
                        <div class="mb-4 p-4" style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;">
                            <h6 style="color:var(--tx);font-weight:bold;margin-bottom:16px;">STAR Framework Analysis</h6>
                            <div class="d-flex flex-wrap gap-4 align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill bg-success" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-check"></i></span>
                                    <span style="color:var(--tx);font-weight:600;">Situation</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill bg-success" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-check"></i></span>
                                    <span style="color:var(--tx);font-weight:600;">Task</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill bg-success" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-check"></i></span>
                                    <span style="color:var(--tx);font-weight:600;">Action</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill bg-danger" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-xmark"></i></span>
                                    <span style="color:var(--tx);font-weight:600;">Result</span>
                                </div>
                            </div>
                            <p style="color:var(--tx3);font-size:0.9rem;margin-top:12px;margin-bottom:0;">
                                <strong class="text-danger">Suggestion:</strong> Include the final measurable outcome of your actions to complete the STAR method effectively.
                            </p>
                        </div>

                        <!-- Feature 8: Suggested Answer Improvement -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label style="font-size:0.85rem;color:var(--tx3);font-weight:700;text-transform:uppercase;margin-bottom:8px;"><i class="fa-solid fa-user me-2"></i>Original Answer</label>
                                <div style="color:var(--tx);background:rgba(255,255,255,0.03);padding:16px;border-radius:12px;border:1px solid var(--bd);height:100%;font-size:0.95rem;line-height:1.6;">
                                    {{ $answer->answer_text }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:0.85rem;color:#10b981;font-weight:700;text-transform:uppercase;margin-bottom:8px;"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Improved Answer</label>
                                <div style="color:var(--tx);background:rgba(16, 185, 129, 0.05);padding:16px;border-radius:12px;border:1px solid rgba(16, 185, 129, 0.2);height:100%;font-size:0.95rem;line-height:1.6;">
                                    {{ $answer->better_sample_answer ?? 'I organized a team meeting to redistribute the workload, which resulted in us meeting the deadline two days early.' }}
                                </div>
                            </div>
                        </div>

                        <!-- Feature 10: Follow-Up Questions -->
                        <div class="mt-4 p-4" style="background:rgba(59, 130, 246, 0.05);border:1px solid rgba(59, 130, 246, 0.2);border-radius:12px;">
                            <label style="font-size:0.9rem;color:#3b82f6;font-weight:700;text-transform:uppercase;margin-bottom:12px;"><i class="fa-solid fa-clipboard-question me-2"></i>Follow-Up Questions to Consider</label>
                            <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:12px;">Think about these questions to encourage deeper practice:</p>
                            <ul class="mb-0" style="color:var(--tx);line-height:1.8;">
                                @if($answer->follow_up_question)
                                    <li>{{ $answer->follow_up_question }}</li>
                                @else
                                    <li>What specific challenges did you face during this task?</li>
                                    <li>What was the exact numerical outcome of your intervention?</li>
                                    <li>If you had to do it over again, what would you do differently?</li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
