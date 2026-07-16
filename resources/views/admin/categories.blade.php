@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('content')
<style>
    #addCategoryModal .form-check-input,
    [id^="editCategoryModal"] .form-check-input {
        position: absolute !important;
        width: 18px !important;
        height: 18px !important;
        min-width: 18px !important;
        min-height: 18px !important;
        margin: 0 !important;
        opacity: 0 !important;
        cursor: pointer;
    }
    #addCategoryModal .form-check,
    [id^="editCategoryModal"] .form-check {
        position: relative;
    }
    #addCategoryModal .form-check-label,
    [id^="editCategoryModal"] .form-check-label {
        position: relative;
        min-height: 18px;
        padding-left: 26px;
        cursor: pointer;
    }
    #addCategoryModal .form-check-label::before,
    [id^="editCategoryModal"] .form-check-label::before {
        content: "";
        position: absolute;
        left: 0;
        top: 50%;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        transform: translateY(-50%);
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.7);
    }
    #addCategoryModal .form-check-label::after,
    [id^="editCategoryModal"] .form-check-label::after {
        content: "";
        position: absolute;
        left: 5px;
        top: 50%;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        transform: translateY(-50%) scale(0);
        background: #ffffff;
        transition: transform 0.15s ease;
    }
    #addCategoryModal .form-check-input:checked + .form-check-label::before,
    [id^="editCategoryModal"] .form-check-input:checked + .form-check-label::before {
        background: #2563eb;
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.22) !important;
    }
    #addCategoryModal .form-check-input:checked + .form-check-label::after,
    [id^="editCategoryModal"] .form-check-input:checked + .form-check-label::after {
        transform: translateY(-50%) scale(1);
    }
    #addCategoryModal .form-check-input:focus,
    [id^="editCategoryModal"] .form-check-input:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.18) !important;
    }
    /* Mobile Card-based Table Layout for Main Categories Table */
    @media (max-width: 767px) {
        #addCategoryModal .modal-dialog,
        [id^="editCategoryModal"] .modal-dialog {
            width: min(calc(100vw - 24px), 420px) !important;
            max-width: 420px !important;
            margin-inline: auto !important;
        }
        #addCategoryModal .modal-body,
        [id^="editCategoryModal"] .modal-body {
            padding: 16px !important;
        }
        #addCategoryModal .oinp,
        [id^="editCategoryModal"] .oinp {
            width: 100% !important;
            max-width: 100% !important;
            min-height: 42px !important;
            margin-bottom: 12px !important;
        }
        #addCategoryModal .form-check,
        [id^="editCategoryModal"] .form-check {
            display: flex !important;
            align-items: center !important;
            gap: 10px;
            min-width: 0;
            padding-left: 0 !important;
            margin-top: 2px;
        }
        #addCategoryModal .form-check-input,
        [id^="editCategoryModal"] .form-check-input {
            width: 18px !important;
            min-width: 18px !important;
            max-width: 18px !important;
            height: 18px !important;
            min-height: 18px !important;
            margin: 0 !important;
            float: none !important;
            flex: 0 0 18px !important;
            border-radius: 50% !important;
        }
        #addCategoryModal .form-check-label,
        [id^="editCategoryModal"] .form-check-label {
            display: block !important;
            min-width: 0;
            margin: 0 !important;
            line-height: 1.3;
            padding-left: 26px;
            overflow-wrap: normal;
            word-break: normal;
        }
        #mainCategoriesTableWrapper {
            overflow-x: visible !important;
            -webkit-overflow-scrolling: auto !important;
            padding: 12px !important;
        }
        #mainCategoriesTable thead {
            display: none;
        }
        #mainCategoriesTable tbody tr {
            display: flex;
            flex-direction: column;
            background: var(--bg3, rgba(255,255,255,0.02));
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--bd, rgba(255,255,255,0.1));
            padding: 12px;
        }
        #mainCategoriesTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-top: none !important;
            text-align: right;
        }
        #mainCategoriesTable tbody td:last-child {
            border-bottom: none !important;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px !important;
        }
        #mainCategoriesTable tbody td::before {
            font-size: 0.8rem;
            color: var(--tx3, #888);
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
            text-align: left;
        }
        #mainCategoriesTable tbody td:nth-child(1)::before { content: "ID"; }
        #mainCategoriesTable tbody td:nth-child(3)::before { content: "Description"; }
        #mainCategoriesTable tbody td:nth-child(4)::before { content: "Type"; }
        #mainCategoriesTable tbody td:nth-child(5)::before { content: "Questions"; }
        #mainCategoriesTable tbody td:nth-child(6)::before { content: "Status"; }
        
        #mainCategoriesTable tbody td:nth-child(2) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
            padding-bottom: 12px !important;
            margin-bottom: 4px;
            text-align: left;
        }
        #mainCategoriesTable tbody td:nth-child(2)::before { content: none; }
    }
</style>
<div class="db-section active" id="sec-admin-categories">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Manage Categories</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage interview categories.</p>
        </div>
        <button class="bgrd btn px-3 py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="fa-solid fa-plus me-1"></i> Add Category</button>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div id="mainCategoriesTableWrapper" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;overflow-x:auto;">
        <table id="mainCategoriesTable" class="table table-dark table-hover mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">ID</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Title</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Description</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Type</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Questions</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Status</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $c)
                <tr>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $c->id }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($c->is_featured) <i class="fa-solid fa-star text-warning me-1"></i> @endif
                        @if($c->icon) <i class="{{ $c->icon }} me-1"></i> @endif
                        <a href="{{ route('admin.categories.details', $c->id) }}" style="color:var(--tx);text-decoration:none;font-weight:600;">{{ $c->title }}</a>
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ Str::limit($c->description, 50) }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($c->type == 'game')
                            <span class="badge bg-info text-dark">Game</span>
                        @elseif($c->type == 'learning')
                            <span class="badge bg-success">Learning</span>
                        @else
                            <span class="badge bg-primary">Core</span>
                        @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $c->questions()->count() }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($c->status == 'active')
                            <span class="badge bg-success"><i class="fa-solid fa-circle me-1"></i>Active</span>
                        @else
                            <span class="badge bg-secondary"><i class="fa-solid fa-circle me-1"></i>Inactive</span>
                        @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        <form action="{{ route('admin.categories.status', $c->id) }}" method="POST" style="display:inline-block">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-warning" style="font-size:.7rem" title="Toggle Status"><i class="fa-solid fa-power-off"></i></button>
                        </form>
                        <button class="btn btn-sm btn-outline-primary" style="font-size:.7rem" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $c->id }}" title="Edit"><i class="fa-solid fa-pen me-1"></i>Edit</button>
                        <button class="btn btn-sm btn-outline-danger" style="font-size:.7rem" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal{{ $c->id }}" title="Delete"><i class="fa-solid fa-trash me-1"></i>Delete</button>
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>
    </div>
</div>

@foreach($categories as $c)
<!-- Edit Modal -->
<div class="modal fade" id="editCategoryModal{{ $c->id }}" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.categories.update', $c->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">Title</label>
                    <input class="oinp mb-3" type="text" name="title" value="{{ $c->title }}" required>

                    <label class="olbl">Description</label>
                    <textarea class="oinp mb-3" name="description" rows="3">{{ $c->description }}</textarea>

                    <label class="olbl">Type</label>
                    <select class="oinp mb-3" name="type" required>
                        <option value="core" {{ $c->type == 'core' ? 'selected' : '' }}>Core</option>
                        <option value="game" {{ $c->type == 'game' ? 'selected' : '' }}>Game</option>
                        <option value="learning" {{ $c->type == 'learning' ? 'selected' : '' }}>Learning</option>
                    </select>

                    <label class="olbl">Icon Class (FontAwesome)</label>
                    <input class="oinp mb-3" type="text" name="icon" value="{{ $c->icon }}" placeholder="e.g. fa-solid fa-briefcase">

                    <label class="olbl">Status</label>
                    <select class="oinp mb-3" name="status">
                        <option value="active" {{ $c->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $c->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="feat{{ $c->id }}" {{ $c->is_featured ? 'checked' : '' }}>
                        <label class="form-check-label olbl" for="feat{{ $c->id }}">Featured Category</label>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteCategoryModal{{ $c->id }}" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.categories.destroy', $c->id) }}" method="POST">
                @csrf @method('DELETE')
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--tx)">Are you sure you want to delete "{{ $c->title }}"?</p>
                    @if($c->questions()->count() > 0)
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i> Warning: This category has {{ $c->questions()->count() }} questions. You cannot delete it unless you transfer or delete the questions first.
                        </div>
                    @endif
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4" {{ $c->questions()->count() > 0 ? 'disabled' : '' }}>Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- Add Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">Title</label>
                    <input class="oinp mb-3" type="text" name="title" required>
                    
                    <label class="olbl">Description</label>
                    <textarea class="oinp mb-3" name="description" rows="3"></textarea>
                    
                    <label class="olbl">Type</label>
                    <select class="oinp mb-3" name="type" required>
                        <option value="core">Core</option>
                        <option value="game">Game</option>
                        <option value="learning">Learning</option>
                    </select>
                    
                    <label class="olbl">Icon Class (FontAwesome)</label>
                    <input class="oinp mb-3" type="text" name="icon" placeholder="e.g. fa-solid fa-briefcase">
                    
                    <label class="olbl">Status</label>
                    <select class="oinp mb-3" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featAdd">
                        <label class="form-check-label olbl" for="featAdd">Featured Category</label>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
