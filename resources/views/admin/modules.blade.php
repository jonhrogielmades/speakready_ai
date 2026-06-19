@extends('layouts.admin')
@section('content')
<div class="db-section active" id="sec-admin-modules">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Learning Modules</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage your learning content.</p>
        </div>
        <button class="bgrd btn px-3 py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#addModuleModal">
            <i class="fa-solid fa-plus me-1"></i> Add Learning Module
        </button>
    </div>

    <!-- Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h6 style="color:var(--tx3);font-size:0.85rem">Total Modules</h6>
                <h2 style="font-weight:700;margin:0">{{ $totalModules }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h6 style="color:var(--tx3);font-size:0.85rem">Published Modules</h6>
                <h2 style="font-weight:700;margin:0;color:#10b981;">{{ $publishedModules }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h6 style="color:var(--tx3);font-size:0.85rem">Draft Modules</h6>
                <h2 style="font-weight:700;margin:0;color:#f59e0b;">{{ $draftModules }}</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h6 style="color:var(--tx3);font-size:0.85rem">Total Resources</h6>
                <h2 style="font-weight:700;margin:0">{{ $totalResources }}</h2>
            </div>
        </div>
    </div>

    <!-- Module List Table -->
    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;overflow-x:auto;">
        <div class="d-flex justify-content-between mb-3 align-items-center">
            <h6 style="margin:0;font-weight:600;">Module List</h6>
            <div class="d-flex gap-2">
                <input type="text" id="moduleSearch" class="oinp" placeholder="Search Modules..." style="max-width:200px;">
                <select id="moduleFilter" class="oinp" style="max-width:150px;">
                    <option value="">All Status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
        </div>

        <table class="table table-dark table-hover mb-0" id="modulesTable" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Module Title</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Category</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Difficulty</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Status</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Views</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($modules as $m)
                <tr data-status="{{ $m->status }}">
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        {{ $m->title }}
                        @if($m->is_featured) <span class="badge bg-warning ms-1 text-dark" style="font-size:0.6rem">⭐ Featured</span> @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $m->category ?? 'None' }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($m->difficulty == 'Beginner') <span class="badge bg-success">Beginner</span>
                        @elseif($m->difficulty == 'Intermediate') <span class="badge bg-warning text-dark">Intermediate</span>
                        @elseif($m->difficulty == 'Advanced') <span class="badge bg-danger">Advanced</span>
                        @else <span class="badge bg-secondary">Unknown</span> @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($m->status == 'published') 🟢 Published
                        @elseif($m->status == 'draft') 🟡 Draft
                        @else 🔴 Archived @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $m->views }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        <a href="{{ route('admin.modules.edit', $m->id) }}" class="btn btn-sm btn-outline-primary" style="font-size:.7rem">Manage Content</a>
                        <form action="{{ route('admin.modules.destroy', $m->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this module?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.7rem">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add Module Modal -->
<div class="modal fade" id="addModuleModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Add Learning Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">Module Title</label>
                    <input class="oinp mb-3" type="text" name="title" placeholder="e.g. Mastering the STAR Method" required>
                    
                    <label class="olbl">Category</label>
                    <input class="oinp mb-3" type="text" name="category" placeholder="e.g. Interview Basics" required>
                    
                    <label class="olbl">Difficulty Level</label>
                    <select class="oinp mb-3" name="difficulty" required>
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>

                    <label class="olbl">Description</label>
                    <textarea class="oinp mb-3" name="description" rows="3" placeholder="Short module summary"></textarea>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featureSwitch">
                        <label class="form-check-label olbl" for="featureSwitch">Mark as Featured ⭐</label>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Create Module</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('moduleSearch');
    const filterSelect = document.getElementById('moduleFilter');
    const table = document.getElementById('modulesTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    function filterTable() {
        const query = searchInput.value.toLowerCase();
        const status = filterSelect.value.toLowerCase();

        for (let i = 0; i < rows.length; i++) {
            const title = rows[i].cells[0].innerText.toLowerCase();
            const rowStatus = rows[i].getAttribute('data-status').toLowerCase();
            
            const matchSearch = title.includes(query);
            const matchStatus = status === "" || rowStatus === status;

            if (matchSearch && matchStatus) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }

    searchInput.addEventListener('keyup', filterTable);
    filterSelect.addEventListener('change', filterTable);
});
</script>
@endsection