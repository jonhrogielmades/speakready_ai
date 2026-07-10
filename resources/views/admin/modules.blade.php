@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('content')
<style>
    #sec-admin-modules .modules-stats-row > [class*="col-"] > div {
        width: 100%;
        min-height: 116px;
    }
    #sec-admin-modules .modules-panel {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 8px;
        overflow: hidden;
    }
    #sec-admin-modules .modules-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--bd);
    }
    #sec-admin-modules .modules-filters {
        display: grid;
        grid-template-columns: minmax(180px, 260px) minmax(150px, 190px) minmax(130px, 170px);
        gap: 10px;
        align-items: center;
    }
    #sec-admin-modules .modules-filters .oinp {
        width: 100%;
        max-width: none !important;
        min-width: 0;
    }
    #mainModulesTableWrapper {
        border: 0 !important;
        border-radius: 0 !important;
        padding: 0 !important;
        overflow-x: hidden !important;
        background: transparent !important;
    }
    #modulesTable {
        table-layout: fixed;
        width: 100%;
        min-width: 0;
        font-size: 0.78rem;
    }
    #modulesTable th:nth-child(1),
    #modulesTable td:nth-child(1) {
        width: 36%;
        white-space: normal !important;
    }
    #modulesTable th:nth-child(2),
    #modulesTable td:nth-child(2) {
        width: 18%;
    }
    #modulesTable th:nth-child(3),
    #modulesTable td:nth-child(3) {
        width: 12%;
    }
    #modulesTable th:nth-child(4),
    #modulesTable td:nth-child(4) {
        width: 13%;
    }
    #modulesTable th:nth-child(5),
    #modulesTable td:nth-child(5) {
        width: 6%;
    }
    #modulesTable th:nth-child(6),
    #modulesTable td:nth-child(6) {
        width: 16%;
    }
    #modulesTable th,
    #modulesTable td {
        padding: 10px 8px !important;
    }
    #sec-admin-modules .module-title-text {
        display: block;
        white-space: normal;
        overflow-wrap: anywhere;
        line-height: 1.25;
        font-size: 0.86rem;
        max-width: 100%;
    }
    #sec-admin-modules .module-category-text {
        display: block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #sec-admin-modules .module-actions {
        display: flex;
        gap: 6px;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: flex-start;
    }
    #sec-admin-modules .module-actions form {
        display: inline-flex;
        margin: 0;
    }
    #modulesTable .btn-sm {
        min-height: 30px;
        padding: 0.28rem 0.5rem;
        font-size: 0.68rem !important;
        line-height: 1.1;
        white-space: nowrap;
    }
    #modulesTable .badge {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.66rem;
        padding: 0.28rem 0.5rem;
        line-height: 1.1;
    }
    #modulesTable .badge.bg-warning.ms-1 {
        display: inline-flex;
        width: fit-content;
        margin-left: 0 !important;
        margin-top: 4px;
    }
    @media (max-width: 1199.98px) {
        #sec-admin-modules .modules-panel-header {
            align-items: flex-start;
            flex-direction: column;
        }
        #sec-admin-modules .modules-filters {
            width: 100%;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        #modulesTable {
            font-size: 0.72rem;
        }
        #modulesTable th,
        #modulesTable td {
            padding-left: 6px !important;
            padding-right: 6px !important;
        }
            #modulesTable .btn-sm {
            min-height: 28px;
            padding: 0.26rem 0.42rem;
            font-size: 0.62rem !important;
        }
        #modulesTable .badge {
            font-size: 0.66rem;
            padding-inline: 0.42rem;
        }
    }
    /* Mobile Card-based Table Layout for Main Modules Table */
    @media (max-width: 767px) {
        #mainModulesTableWrapper {
            overflow-x: hidden !important;
            padding: 12px !important;
            width: 100%;
        }
        #modulesTable {
            width: 100% !important;
            word-wrap: break-word;
        }
        #modulesTable thead {
            display: none;
        }
        #modulesTable tbody tr {
            display: flex;
            flex-direction: column;
            width: 100%;
            background: var(--bg3, rgba(255,255,255,0.02));
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--bd, rgba(255,255,255,0.1));
            padding: 12px;
        }
        #modulesTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-top: none !important;
            text-align: right;
            white-space: normal !important;
            word-break: break-word;
        }
        #modulesTable tbody td:last-child {
            border-bottom: none !important;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px !important;
            flex-wrap: wrap;
        }
        #modulesTable tbody td::before {
            font-size: 0.8rem;
            color: var(--tx3, #888);
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
            text-align: left;
        }
        #modulesTable tbody td:nth-child(2)::before { content: "Category"; }
        #modulesTable tbody td:nth-child(3)::before { content: "Difficulty"; }
        #modulesTable tbody td:nth-child(4)::before { content: "Status"; }
        #modulesTable tbody td:nth-child(5)::before { content: "Views"; }
        
        #modulesTable tbody td:nth-child(1) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
            padding-bottom: 12px !important;
            margin-bottom: 4px;
            text-align: left;
            flex-direction: column;
            align-items: flex-start;
        }
        #modulesTable tbody td:nth-child(1)::before { content: none; }
        #sec-admin-modules .modules-filters {
            grid-template-columns: 1fr;
        }
        #modulesTable {
            table-layout: auto;
        }
        #modulesTable th,
        #modulesTable td {
            width: auto !important;
        }
    }
</style>
<div class="db-section active" id="sec-admin-modules">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(16, 185, 129, 0.1); color:#10b981; border:1px solid rgba(16, 185, 129, 0.3); border-radius:12px;">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background:rgba(244, 63, 94, 0.1); color:#f43f5e; border:1px solid rgba(244, 63, 94, 0.3); border-radius:12px;">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
        </div>
    @endif
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Learning Modules</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage your learning content.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn px-3 py-2" style="font-size:.85rem; background:rgba(59,130,246,0.1); color:var(--pur); border:1px solid rgba(59,130,246,0.3);" data-bs-toggle="modal" data-bs-target="#aiGenerateModuleModal">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> AI Generate
            </button>
            <button class="bgrd btn px-3 py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                <i class="fa-solid fa-plus me-1"></i> Add Module
            </button>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row g-3 mb-4 modules-stats-row">
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
    <div class="modules-panel">
        <div class="modules-panel-header">
            <h6 style="margin:0;font-weight:600;">Module List</h6>
            <div class="modules-filters">
                <input type="text" id="moduleSearch" class="oinp" placeholder="Search Modules...">
                <select id="categoryFilter" class="oinp">
                    <option value="">All Categories</option>
                    @if(isset($categories))
                        @foreach($categories as $cat)
                            <option value="{{ strtolower($cat) }}">{{ $cat }}</option>
                        @endforeach
                    @endif
                </select>
                <select id="moduleFilter" class="oinp">
                    <option value="">All Status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
        </div>

        <div id="mainModulesTableWrapper" class="table-responsive">
        <table class="table table-dark table-hover mb-0" id="modulesTable" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Module Title</th>
                    <th class="d-none d-md-table-cell" style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Category</th>
                    <th class="d-none d-md-table-cell" style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Difficulty</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Status</th>
                    <th class="d-none d-lg-table-cell" style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Views</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($modules as $m)
                <tr data-status="{{ $m->status }}" data-category="{{ strtolower($m->category ?? '') }}">
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        <span class="module-title-text">{{ $m->title }}</span>
                        @if($m->is_featured) <span class="badge bg-warning ms-1 text-dark" style="font-size:0.6rem"><i class="fa-solid fa-star me-1"></i>Featured</span> @endif
                    </td>
                    <td class="d-none d-md-table-cell" style="border-bottom:1px solid var(--bd);padding:12px 8px"><span class="module-category-text" title="{{ $m->category ?? 'None' }}">{{ $m->category ?? 'None' }}</span></td>
                    <td class="d-none d-md-table-cell" style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($m->difficulty == 'Beginner') <span class="badge bg-success">Beginner</span>
                        @elseif($m->difficulty == 'Intermediate') <span class="badge bg-warning text-dark">Intermediate</span>
                        @elseif($m->difficulty == 'Advanced') <span class="badge bg-danger">Advanced</span>
                        @else <span class="badge bg-secondary">Unknown</span> @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($m->status == 'published') <span class="badge bg-success"><i class="fa-solid fa-circle me-1"></i>Published</span>
                        @elseif($m->status == 'draft') <span class="badge bg-warning text-dark"><i class="fa-solid fa-circle me-1"></i>Draft</span>
                        @else <span class="badge bg-secondary"><i class="fa-solid fa-circle me-1"></i>Archived</span> @endif
                    </td>
                    <td class="d-none d-lg-table-cell" style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $m->views }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px;">
                        <div class="module-actions">
                        <a href="{{ route('admin.modules.edit', $m->id) }}" class="btn btn-sm btn-outline-primary" style="font-size:.7rem">Manage</a>
                        <form action="{{ route('admin.modules.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Delete this module?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.7rem">Delete</button>
                        </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
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
                    <select class="oinp mb-3" name="category" id="categorySelect" required onchange="if(this.value === 'new_category') { document.getElementById('newCategoryInput').style.display='block'; document.getElementById('newCategoryInput').name='category'; document.getElementById('newCategoryInput').required=true; this.name=''; } else { document.getElementById('newCategoryInput').style.display='none'; document.getElementById('newCategoryInput').name=''; document.getElementById('newCategoryInput').required=false; this.name='category'; }">
                        <option value="" disabled selected>Select a Category...</option>
                        @if(isset($categories) && count($categories) > 0)
                            @foreach($categories as $cat)
                                @if(!empty($cat))
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endif
                            @endforeach
                        @endif
                        <option value="new_category">+ Add New Category</option>
                    </select>
                    <input type="text" id="newCategoryInput" class="oinp mb-3" placeholder="Enter new category name" style="display: none;">
                    
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
                        <label class="form-check-label olbl" for="featureSwitch">Mark as Featured</label>
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

<!-- AI Generate Module Modal -->
<div class="modal fade" id="aiGenerateModuleModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.generate') }}" method="POST" id="aiGenerateForm">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>AI Generate Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--tx3); font-size:0.9rem; margin-bottom:20px;">
                        Enter a topic or prompt, and the AI will automatically generate a complete Learning Module with chapters, category, and description.
                    </p>
                    <label class="olbl">Topic Prompt</label>
                    <textarea class="oinp mb-3" name="prompt" rows="3" placeholder="e.g. A comprehensive guide on leadership in remote technical teams." required></textarea>
                    
                    <div id="aiLoadingIndicator" style="display:none; text-align:center; padding:15px; border-radius:10px; background:rgba(59,130,246,0.1);">
                        <i class="fa-solid fa-circle-notch fa-spin text-primary" style="font-size:1.5rem; margin-bottom:10px;"></i>
                        <h6 style="color:var(--tx); margin:0;">Generating Content...</h6>
                        <p style="color:var(--tx3); font-size:0.8rem; margin:0;">This may take up to 30 seconds.</p>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)" id="aiModalFooter">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4" id="aiGenerateBtn">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const aiForm = document.getElementById('aiGenerateForm');
        if(aiForm) {
            aiForm.addEventListener('submit', function() {
                document.getElementById('aiLoadingIndicator').style.display = 'block';
                document.getElementById('aiModalFooter').style.display = 'none';
            });
        }
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('moduleSearch');
    const filterSelect = document.getElementById('moduleFilter');
    const categorySelect = document.getElementById('categoryFilter');
    const table = document.getElementById('modulesTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    function filterTable() {
        const query = searchInput.value.toLowerCase();
        const status = filterSelect.value.toLowerCase();
        const category = categorySelect.value.toLowerCase();

        for (let i = 0; i < rows.length; i++) {
            const title = rows[i].cells[0].innerText.toLowerCase();
            const rowStatus = rows[i].getAttribute('data-status').toLowerCase();
            const rowCategory = rows[i].getAttribute('data-category').toLowerCase();
            
            const matchSearch = title.includes(query);
            const matchStatus = status === "" || rowStatus === status;
            const matchCategory = category === "" || rowCategory === category;

            if (matchSearch && matchStatus && matchCategory) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }

    searchInput.addEventListener('keyup', filterTable);
    filterSelect.addEventListener('change', filterTable);
    categorySelect.addEventListener('change', filterTable);
});
</script>
@endsection
