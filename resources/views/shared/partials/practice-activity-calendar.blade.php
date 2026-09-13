<div class="row g-4 mb-4">
    <div class="col-12" id="activity-calendar">
        <div class="activity-panel" style="--panel-accent:#6d5dfc;">
            <div class="activity-heading">
                <div class="activity-heading-icon"><i class="fa-regular fa-calendar"></i></div>
                <div>
                    <h5 class="activity-title">Activity Calendar</h5>
                    <p class="activity-subtitle">Completed interview activity from the last 28 days.</p>
                </div>
            </div>
            @if($activityCalendar->range_active_days > 0)
                <div class="activity-summary-grid">
                    <div class="activity-summary-item">
                        <strong>{{ $activityCalendar->range_active_days }}</strong>
                        <span>Active days</span>
                    </div>
                    <div class="activity-summary-item">
                        <strong>{{ $activityCalendar->recent_active_days }}</strong>
                        <span>This week</span>
                    </div>
                    <div class="activity-summary-item">
                        <strong>{{ $activityCalendar->current_streak }}</strong>
                        <span>Activity streak</span>
                    </div>
                    <div class="activity-summary-item">
                        <strong>{{ $activityCalendar->last_activity_label }}</strong>
                        <span>Latest practice</span>
                    </div>
                </div>
                <div class="activity-grid" role="list" aria-label="Last 28 days practice activity">
                    @foreach($activityCalendar->days as $day)
                        <div class="activity-day {{ $day->total > 0? 'active': '' }} {{ $day->is_today? 'today': '' }}"
                            role="listitem"
                            title="{{ $day->tooltip }}"
                            aria-label="{{ $day->tooltip }}"
                            style="--activity-intensity: {{ $day->intensity }}%;">
                            <span class="activity-day-week">{{ $day->weekday }}</span>
                            <span class="activity-day-number">{{ $day->day_number }}</span>
                            @if($day->total > 0)
                                <span class="activity-day-dot">{{ $day->total }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="activity-legend">
                    <span><i></i>Practice recorded</span>
                    <a href="{{ route('interview.setup') }}" class="btn btn-outline-primary activity-cta compact"><i class="fa-solid fa-play"></i> Practice Again</a>
                </div>
            @else
                <div class="activity-empty">
                    <svg class="activity-illustration" viewBox="0 0 520 260" aria-hidden="true" role="img">
                        <defs>
                            <linearGradient id="activityCalTop" x1="120" y1="50" x2="400" y2="202" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#8B5CF6"/>
                                <stop offset="1" stop-color="#C4B5FD"/>
                            </linearGradient>
                        </defs>
                        <ellipse cx="260" cy="218" rx="210" ry="18" fill="#ede9fe"/>
                        <circle cx="260" cy="130" r="118" fill="#ede9fe" opacity=".75"/>
                        <path d="M116 180c34-18 52-49 43-91 34 39 37 72 6 101" fill="#c4b5fd" opacity=".7"/>
                        <path d="M404 190c-22-42-10-75 34-103 8 48-4 82-34 103z" fill="#c4b5fd" opacity=".7"/>
                        <rect x="162" y="72" width="196" height="148" rx="18" fill="#fff" stroke="#ddd6fe" stroke-width="3"/>
                        <path d="M162 96c0-13 11-24 24-24h148c13 0 24 11 24 24v26H162V96z" fill="url(#activityCalTop)"/>
                        <path d="M198 58v34M260 58v34M322 58v34" stroke="#37306b" stroke-width="13" stroke-linecap="round"/>
                        @for($row = 0; $row < 3; $row++)
                            @for($col = 0; $col < 6; $col++)
                                <rect x="{{ 194 + ($col * 28) }}" y="{{ 144 + ($row * 31) }}" width="22" height="22" rx="5" fill="#ede9fe" opacity=".75"/>
                            @endfor
                        @endfor
                        <rect x="278" y="172" width="30" height="30" rx="7" fill="#6d5dfc"/>
                        <path d="M286 187l6 6 11-14" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="118" cy="91" r="6" fill="#c4b5fd"/>
                        <circle cx="76" cy="158" r="8" fill="#c4b5fd"/>
                        <circle cx="432" cy="132" r="7" fill="#c4b5fd"/>
                        <path d="M380 86l8 16 16 8-16 8-8 16-8-16-16-8 16-8 8-16z" fill="#a78bfa"/>
                    </svg>
                    <h6 class="activity-empty-title">Complete your first practice interview</h6>
                    <p class="activity-empty-text">to start tracking your daily practice activity.</p>
                    <a href="{{ route('interview.setup') }}" class="btn btn-outline-primary activity-cta"><i class="fa-solid fa-play"></i> Start Practice</a>
                </div>
            @endif
        </div>
    </div>
</div>
