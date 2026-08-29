@extends('desktop.layouts.admin')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/admin/categories.css?v=1') }}" data-page-style="admin-categories">
@endpush

@section('content')
<div class="db-section active" id="sec-admin-categories">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Manage Philippines Interview Categories</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage categories for Philippine job and school admission interviews.</p>
        </div>
        <button class="bgrd btn px-3 py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="fa-solid fa-plus me-1"></i> Add PH Category</button>
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
                    <h5 class="modal-title" style="color:var(--tx)">Edit Philippines Interview Category</h5>
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
                        <label class="form-check-label olbl" for="feat{{ $c->id }}">Featured PH Category</label>
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
                    <h5 class="modal-title" style="color:var(--tx)">Add Philippines Interview Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">PH Category Title</label>
                    <input class="oinp mb-3" type="text" name="title" required>
                    
                    <label class="olbl">Philippines Interview Description</label>
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
                        <label class="form-check-label olbl" for="featAdd">Featured PH Category</label>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Save PH Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
