@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('content')
<div class="db-section active" id="sec-admin-arena">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Arena Games</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage gamified interview levels and missions.</p>
        </div>
        <button class="bgrd btn px-3 py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#addArenaModal">
            <i class="fa-solid fa-plus me-1"></i> Add Arena Game
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2); color: #10b981; border-radius:12px;">
            <i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter:invert(1) grayscale(1) brightness(2);"></button>
        </div>
    @endif

    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;overflow:hidden;">
        <div class="p-3" style="border-bottom:1px solid var(--bd);background:var(--bg3)">
            <h6 style="color:var(--tx);margin:0;font-size:0.95rem">Game Levels</h6>
        </div>
        
        <div class="table-responsive">
            <table class="table mb-0" style="color:var(--tx);--bs-table-bg:transparent;--bs-table-color:var(--tx);">
                <thead style="background:var(--bg3);">
                    <tr>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;width:80px;">Level</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;">Title</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;">Target Position</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;">Difficulty</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;">Rewards</th>
                        <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:0.8rem;font-weight:600;padding:12px 16px;text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($levels as $level)
                        <tr style="border-bottom:1px solid var(--bd);">
                            <td style="padding:16px;vertical-align:middle;">
                                <span class="badge bg-primary rounded-pill">{{ $level->level_number }}</span>
                            </td>
                            <td style="padding:16px;vertical-align:middle;font-weight:600;">
                                {{ $level->title }}
                                <div style="font-size:0.75rem;color:var(--tx3);font-weight:normal;margin-top:4px;">{{ Str::limit($level->description, 50) }}</div>
                            </td>
                            <td style="padding:16px;vertical-align:middle;color:var(--tx2);">{{ $level->target_position }}</td>
                            <td style="padding:16px;vertical-align:middle;">
                                <span class="badge" style="background:var(--bg3);color:var(--tx2);">{{ ucfirst($level->difficulty) }}</span>
                            </td>
                            <td style="padding:16px;vertical-align:middle;">
                                <div style="font-size:0.8rem;color:var(--tx2);"><i class="fa-solid fa-star text-warning w-15px"></i> {{ $level->xp_reward }} XP</div>
                                <div style="font-size:0.8rem;color:var(--tx2);"><i class="fa-solid fa-heart text-danger w-15px"></i> {{ $level->energy_cost }} Cost</div>
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
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content" style="border:1px solid var(--bd)">
                                    <form action="{{ route('admin.arena.update', $level->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                                            <h5 class="modal-title" style="color:var(--tx)">Edit Arena Game</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-2">
                                                    <label class="olbl">Level #</label>
                                                    <input class="oinp w-100" type="number" name="level_number" value="{{ $level->level_number }}" required>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="olbl">Title</label>
                                                    <input class="oinp w-100" type="text" name="title" value="{{ $level->title }}" required>
                                                </div>
                                                <div class="col-md-5">
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
                                                    <label class="olbl">XP Reward</label>
                                                    <input class="oinp w-100" type="number" name="xp_reward" value="{{ $level->xp_reward }}" min="0" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="olbl">Energy Cost</label>
                                                    <input class="oinp w-100" type="number" name="energy_cost" value="{{ $level->energy_cost }}" min="0" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="olbl">Description</label>
                                                <textarea class="oinp w-100" name="description" rows="3">{{ $level->description }}</textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="olbl">Mission Text (Instructions)</label>
                                                <textarea class="oinp w-100" name="mission_text" rows="3">{{ $level->mission_text }}</textarea>
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
                                <p>Click the "Add Arena Game" button to create one.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Arena Game Modal -->
<div class="modal fade" id="addArenaModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.arena.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Add Arena Game</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="olbl">Level #</label>
                            <input class="oinp w-100" type="number" name="level_number" required>
                        </div>
                        <div class="col-md-5">
                            <label class="olbl">Title</label>
                            <input class="oinp w-100" type="text" name="title" required placeholder="e.g. Sales Pitch Challenge">
                        </div>
                        <div class="col-md-5">
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
                            <label class="olbl">XP Reward</label>
                            <input class="oinp w-100" type="number" name="xp_reward" value="500" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="olbl">Energy Cost</label>
                            <input class="oinp w-100" type="number" name="energy_cost" value="1" min="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="olbl">Description</label>
                        <textarea class="oinp w-100" name="description" rows="3" placeholder="Brief description of the game/level"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="olbl">Mission Text (Instructions)</label>
                        <textarea class="oinp w-100" name="mission_text" rows="3" placeholder="Specific instructions for the user"></textarea>
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
