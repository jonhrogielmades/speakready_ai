@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('content')
<div class="db-section active" id="sec-admin-arena">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Arena Games</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage gamified interview levels, modifiers, and missions.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-info px-3 py-2" style="font-size:.85rem;font-weight:600" data-bs-toggle="modal" data-bs-target="#generateArenaModal">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto-Generate
            </button>
            <button class="bgrd btn px-3 py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#addArenaModal">
                <i class="fa-solid fa-plus me-1"></i> Add Game
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2); color: #10b981; border-radius:12px;">
            <i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter:invert(1) grayscale(1) brightness(2);"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background:rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); color: #ef4444; border-radius:12px;">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter:invert(1) grayscale(1) brightness(2);"></button>
        </div>
    @endif

    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;overflow:hidden;">
        <div class="p-3" style="border-bottom:1px solid var(--bd);background:var(--bg3)">
            <h6 style="color:var(--tx);margin:0;font-size:0.95rem">Game Levels & Analytics</h6>
        </div>
        
        <div class="table-responsive">
            <table class="table mb-0" style="color:var(--tx);--bs-table-bg:transparent;--bs-table-color:var(--tx);">
                <thead style="background:var(--bg3);">
                    <tr>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;width:80px;">Level</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;">Details</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;">Modifiers</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;">Rewards</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;">Analytics</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($levels as $level)
                        <tr style="border-bottom:1px solid var(--bd); {{ $level->is_hidden ? 'opacity: 0.6;' : '' }}">
                            <td style="padding:16px;vertical-align:middle;">
                                <span class="badge bg-primary rounded-pill mb-1">Lvl {{ $level->level_number }}</span>
                                @if($level->is_hidden) <br><span class="badge bg-dark" style="font-size:0.6rem">Hidden</span> @endif
                            </td>
                            <td style="padding:16px;vertical-align:middle;">
                                <div style="font-weight:700;color:var(--tx)">{{ $level->title }}</div>
                                <div style="font-size:0.75rem;color:var(--tx2); margin-top:2px;">
                                    <span class="badge bg-secondary" style="font-size:0.65rem; margin-right:4px;">{{ $level->category ? $level->category->title : 'No Category' }}</span> 
                                    {{ $level->target_position }} &bull; {{ ucfirst($level->difficulty) }}
                                </div>
                                @if($level->prerequisiteLevel)
                                    <div style="font-size:0.7rem;color:#f59e0b;margin-top:4px;"><i class="fa-solid fa-lock text-warning"></i> Prereq: Lvl {{ $level->prerequisiteLevel->level_number }}</div>
                                @endif
                            </td>
                            <td style="padding:16px;vertical-align:middle;">
                                @if($level->ai_persona) <div style="font-size:0.75rem;color:var(--tx2)"><i class="fa-solid fa-robot text-info w-15px"></i> {{ Str::limit($level->ai_persona, 20) }}</div> @endif
                                @if($level->time_limit_seconds) <div style="font-size:0.75rem;color:var(--tx2)"><i class="fa-solid fa-clock text-danger w-15px"></i> {{ $level->time_limit_seconds }}s limit</div> @endif
                                @if($level->banned_words) <div style="font-size:0.75rem;color:var(--tx2)" title="{{ $level->banned_words }}"><i class="fa-solid fa-ban text-danger w-15px"></i> Banned Words</div> @endif
                                @if($level->target_tone) <div style="font-size:0.75rem;color:var(--tx2)"><i class="fa-solid fa-face-smile text-success w-15px"></i> {{ $level->target_tone }}</div> @endif
                                @if(!$level->ai_persona && !$level->time_limit_seconds && !$level->banned_words && !$level->target_tone)
                                    <span style="font-size:0.75rem;color:var(--tx3)">Standard</span>
                                @endif
                            </td>
                            <td style="padding:16px;vertical-align:middle;">
                                <div style="font-size:0.8rem;color:var(--tx2);"><i class="fa-solid fa-star text-warning w-15px"></i> {{ $level->xp_reward }} XP</div>
                                <div style="font-size:0.8rem;color:var(--tx2);"><i class="fa-solid fa-heart text-danger w-15px"></i> {{ $level->energy_cost }} Cost</div>
                                @if($level->custom_badge_name) <div style="font-size:0.75rem;color:var(--pur);font-weight:600;"><i class="fa-solid fa-medal"></i> {{ $level->custom_badge_name }}</div> @endif
                                @if($level->skill_xp_amount > 0) <div style="font-size:0.75rem;color:#34d399;"><i class="fa-solid fa-bolt"></i> +{{ $level->skill_xp_amount }} {{ $level->skill_xp_type }}</div> @endif
                            </td>
                            <td style="padding:16px;vertical-align:middle;">
                                <div style="font-size:0.8rem;color:var(--tx2);font-weight:600;">Pass Rate: <span style="color:{{ $level->pass_rate > 70 ? '#34d399' : ($level->pass_rate < 30 ? '#ef4444' : 'var(--tx)') }}">{{ $level->pass_rate }}%</span></div>
                                <div style="font-size:0.8rem;color:var(--tx3);">Avg Score: {{ $level->avg_score }}%</div>
                            </td>
                            <td style="padding:16px;vertical-align:middle;text-align:right;">
                                <button class="btn btn-sm btn-outline-secondary border-0" data-bs-toggle="modal" data-bs-target="#editArenaModal{{ $level->id }}" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form action="{{ route('admin.arena.destroy', $level->id) }}" method="POST" style="display:inline-block;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Delete" onclick="return confirm('Are you sure you want to delete this game?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Arena Game Modal -->
                        <div class="modal fade" id="editArenaModal{{ $level->id }}" tabindex="-1" style="--bs-modal-bg:var(--sf)">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content" style="border:1px solid var(--bd)">
                                    <form action="{{ route('admin.arena.update', $level->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                                            <h5 class="modal-title" style="color:var(--tx)">Edit Arena Game: Lvl {{ $level->level_number }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                                        </div>
                                        <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                                            <h6 style="color:var(--adm); border-bottom:1px solid var(--bd); padding-bottom:8px; margin-bottom:16px;">Core Settings</h6>
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-2">
                                                    <label class="olbl">Level #</label>
                                                    <input class="oinp w-100" type="number" name="level_number" value="{{ $level->level_number }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="olbl">Category</label>
                                                    <select class="oinp w-100" name="category_id" required>
                                                        @foreach($categories as $cat)
                                                            <option value="{{ $cat->id }}" {{ $level->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="olbl">Title</label>
                                                    <input class="oinp w-100" type="text" name="title" value="{{ $level->title }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="olbl">Target Position</label>
                                                    <input class="oinp w-100" type="text" name="target_position" value="{{ $level->target_position }}" required>
                                                </div>
                                            </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-md-3">
                                                    <label class="olbl">Difficulty</label>
                                                    <select class="oinp w-100" name="difficulty" required>
                                                        <option value="beginner" {{ $level->difficulty == 'beginner' ? 'selected' : '' }}>Beginner</option>
                                                        <option value="intermediate" {{ $level->difficulty == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                                        <option value="advanced" {{ $level->difficulty == 'advanced' ? 'selected' : '' }}>Advanced</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="olbl">Required Score (%)</label>
                                                    <input class="oinp w-100" type="number" name="required_score" value="{{ $level->required_score }}" min="0" max="100" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="olbl">Prerequisite Level</label>
                                                    <select class="oinp w-100" name="prerequisite_level_id">
                                                        <option value="">None (Always Unlocked)</option>
                                                        @foreach($allLevels as $al)
                                                            @if($al->id !== $level->id)
                                                                <option value="{{ $al->id }}" {{ $level->prerequisite_level_id == $al->id ? 'selected' : '' }}>Level {{ $al->level_number }}: {{ $al->title }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex align-items-end">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" name="is_hidden" value="1" id="isHiddenEdit{{ $level->id }}" {{ $level->is_hidden ? 'checked' : '' }}>
                                                        <label class="form-check-label text-white" for="isHiddenEdit{{ $level->id }}">
                                                            Hidden until unlocked
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="olbl">Description</label>
                                                <textarea class="oinp w-100" name="description" rows="2">{{ $level->description }}</textarea>
                                            </div>
                                            <div class="mb-4">
                                                <label class="olbl">Mission Text (User Instructions)</label>
                                                <textarea class="oinp w-100" name="mission_text" rows="2">{{ $level->mission_text }}</textarea>
                                            </div>

                                            <h6 style="color:var(--adm); border-bottom:1px solid var(--bd); padding-bottom:8px; margin-bottom:16px;">AI Settings & Modifiers</h6>
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-4">
                                                    <label class="olbl">AI Persona (e.g. Strict Technical Lead)</label>
                                                    <input class="oinp w-100" type="text" name="ai_persona" value="{{ $level->ai_persona }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="olbl">Time Limit (Seconds)</label>
                                                    <input class="oinp w-100" type="number" name="time_limit_seconds" value="{{ $level->time_limit_seconds }}" placeholder="e.g. 120 (Leave empty for none)">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="olbl">Target Tone</label>
                                                    <input class="oinp w-100" type="text" name="target_tone" value="{{ $level->target_tone }}" placeholder="e.g. Confident, Empathetic">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="olbl">Banned Words (Comma separated)</label>
                                                <input class="oinp w-100" type="text" name="banned_words" value="{{ $level->banned_words }}" placeholder="e.g. um, like, basically">
                                            </div>
                                            <div class="mb-4">
                                                <label class="olbl">Custom AI Prompt (Hidden instructions)</label>
                                                <textarea class="oinp w-100" name="ai_custom_prompt" rows="2" placeholder="e.g. Interrupt the user at least once.">{{ $level->ai_custom_prompt }}</textarea>
                                            </div>

                                            <h6 style="color:var(--adm); border-bottom:1px solid var(--bd); padding-bottom:8px; margin-bottom:16px;">Rewards & Economy</h6>
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-2">
                                                    <label class="olbl">Energy Cost</label>
                                                    <input class="oinp w-100" type="number" name="energy_cost" value="{{ $level->energy_cost }}" min="0" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="olbl">General XP Reward</label>
                                                    <input class="oinp w-100" type="number" name="xp_reward" value="{{ $level->xp_reward }}" min="0" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="olbl">Custom Badge Name</label>
                                                    <input class="oinp w-100" type="text" name="custom_badge_name" value="{{ $level->custom_badge_name }}" placeholder="e.g. Master Negotiator">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="olbl">Bonus Skill Type</label>
                                                    <select class="oinp w-100" name="skill_xp_type">
                                                        <option value="">None</option>
                                                        <option value="Leadership" {{ $level->skill_xp_type == 'Leadership' ? 'selected' : '' }}>Leadership</option>
                                                        <option value="Communication" {{ $level->skill_xp_type == 'Communication' ? 'selected' : '' }}>Communication</option>
                                                        <option value="Technical" {{ $level->skill_xp_type == 'Technical' ? 'selected' : '' }}>Technical</option>
                                                        <option value="Problem Solving" {{ $level->skill_xp_type == 'Problem Solving' ? 'selected' : '' }}>Problem Solving</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="olbl">Bonus Skill XP</label>
                                                    <input class="oinp w-100" type="number" name="skill_xp_amount" value="{{ $level->skill_xp_amount }}" min="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer" style="border-top:1px solid var(--bd)">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="bgrd btn px-4">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5" style="color:var(--tx3);">
                                <i class="fa-solid fa-gamepad fa-3x mb-3" style="color:var(--bd);"></i>
                                <h5>No Arena Games Found</h5>
                                <p>Click "Add Game" or "Auto-Generate" to create your first level.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Auto-Generate Game Modal -->
<div class="modal fade" id="generateArenaModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.arena.generate') }}" method="POST" id="generateGameForm">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)"><i class="fa-solid fa-wand-magic-sparkles text-info me-2"></i> Auto-Generate AI Game</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--tx3); font-size:0.85rem;">Let our AI instantly craft a unique, fully-configured gamified level complete with instructions, a persona, modifiers, and rewards.</p>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="olbl">Level #</label>
                            <input class="oinp w-100" type="number" name="level_number" required placeholder="e.g. {{ count($levels) + 1 }}">
                        </div>
                        <div class="col-md-8">
                            <label class="olbl">Category</label>
                            <select class="oinp w-100" name="category_id" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="olbl">Topic / Challenge Focus</label>
                        <input class="oinp w-100" type="text" name="topic" required placeholder="e.g. Dealing with a hostile client, High stress technical test, Salary negotiation">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info px-4 text-white" onclick="this.innerHTML='<i class=\'fa-solid fa-spinner fa-spin\'></i> Generating...';">Generate Game</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Arena Game Modal -->
<div class="modal fade" id="addArenaModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.arena.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Add Arena Game</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <h6 style="color:var(--adm); border-bottom:1px solid var(--bd); padding-bottom:8px; margin-bottom:16px;">Core Settings</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="olbl">Level #</label>
                            <input class="oinp w-100" type="number" name="level_number" required>
                        </div>
                        <div class="col-md-3">
                            <label class="olbl">Category</label>
                            <select class="oinp w-100" name="category_id" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="olbl">Title</label>
                            <input class="oinp w-100" type="text" name="title" required placeholder="e.g. Sales Pitch Challenge">
                        </div>
                        <div class="col-md-3">
                            <label class="olbl">Target Position</label>
                            <input class="oinp w-100" type="text" name="target_position" required placeholder="e.g. Sales Manager">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="olbl">Difficulty</label>
                            <select class="oinp w-100" name="difficulty" required>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="olbl">Required Score (%)</label>
                            <input class="oinp w-100" type="number" name="required_score" value="80" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="olbl">Prerequisite Level</label>
                            <select class="oinp w-100" name="prerequisite_level_id">
                                <option value="">None (Always Unlocked)</option>
                                @foreach($allLevels as $al)
                                    <option value="{{ $al->id }}">Level {{ $al->level_number }}: {{ $al->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_hidden" value="1" id="isHiddenAdd">
                                <label class="form-check-label text-white" for="isHiddenAdd">
                                    Hidden until unlocked
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="olbl">Description</label>
                        <textarea class="oinp w-100" name="description" rows="2" placeholder="Brief description of the game/level"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="olbl">Mission Text (Instructions)</label>
                        <textarea class="oinp w-100" name="mission_text" rows="2" placeholder="Specific instructions for the user"></textarea>
                    </div>

                    <h6 style="color:var(--adm); border-bottom:1px solid var(--bd); padding-bottom:8px; margin-bottom:16px;">AI Settings & Modifiers</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="olbl">AI Persona</label>
                            <input class="oinp w-100" type="text" name="ai_persona" placeholder="e.g. Strict HR Director">
                        </div>
                        <div class="col-md-4">
                            <label class="olbl">Time Limit (Seconds)</label>
                            <input class="oinp w-100" type="number" name="time_limit_seconds" placeholder="Optional">
                        </div>
                        <div class="col-md-4">
                            <label class="olbl">Target Tone</label>
                            <input class="oinp w-100" type="text" name="target_tone" placeholder="e.g. Confident">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="olbl">Banned Words (Comma separated)</label>
                        <input class="oinp w-100" type="text" name="banned_words" placeholder="e.g. um, like, basically">
                    </div>
                    <div class="mb-4">
                        <label class="olbl">Custom AI Prompt (Hidden)</label>
                        <textarea class="oinp w-100" name="ai_custom_prompt" rows="2" placeholder="Secret AI instructions..."></textarea>
                    </div>

                    <h6 style="color:var(--adm); border-bottom:1px solid var(--bd); padding-bottom:8px; margin-bottom:16px;">Rewards & Economy</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="olbl">Energy Cost</label>
                            <input class="oinp w-100" type="number" name="energy_cost" value="1" min="0" required>
                        </div>
                        <div class="col-md-2">
                            <label class="olbl">General XP Reward</label>
                            <input class="oinp w-100" type="number" name="xp_reward" value="500" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="olbl">Custom Badge Name</label>
                            <input class="oinp w-100" type="text" name="custom_badge_name" placeholder="Optional">
                        </div>
                        <div class="col-md-3">
                            <label class="olbl">Bonus Skill Type</label>
                            <select class="oinp w-100" name="skill_xp_type">
                                <option value="">None</option>
                                <option value="Leadership">Leadership</option>
                                <option value="Communication">Communication</option>
                                <option value="Technical">Technical</option>
                                <option value="Problem Solving">Problem Solving</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="olbl">Bonus Skill XP</label>
                            <input class="oinp w-100" type="number" name="skill_xp_amount" value="0" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Create Game</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
