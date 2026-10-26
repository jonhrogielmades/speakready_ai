@php
    $addChapterErrors = $errors->getBag('addChapter');
    $editChapterErrorId = null;

    foreach ($module->chapters as $chapterWithPossibleErrors) {
        if ($errors->getBag('editChapter'.$chapterWithPossibleErrors->id)->any()) {
            $editChapterErrorId = $chapterWithPossibleErrors->id;
            break;
        }
    }
@endphp

<div
    id="adminModuleChapterEditorConfig"
    data-module-id="{{ $module->id }}"
    data-active-module-tab="{{ session('active_module_tab', '') }}"
    data-open-add-chapter="{{ $addChapterErrors->any() ? '1' : '0' }}"
    data-edit-chapter-id="{{ $editChapterErrorId ?? '' }}"
    hidden
></div>

<!-- Add Chapter Modal -->
<div class="modal fade admin-chapter-modal" id="addChapterModal" tabindex="-1" aria-labelledby="addChapterModalLabel" aria-hidden="true" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.chapters.store', $module) }}" method="POST" id="chapterForm" class="admin-chapter-form">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" id="addChapterModalLabel" style="color:var(--tx)">Add Interview Chapter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="olbl" for="addChapterTitle">Interview Chapter Title</label>
                        <input
                            class="oinp w-100 {{ $addChapterErrors->has('title') ? 'is-invalid' : '' }}"
                            id="addChapterTitle"
                            type="text"
                            name="title"
                            value="{{ $addChapterErrors->any() ? old('title') : '' }}"
                            maxlength="255"
                            autocomplete="off"
                            required
                        >
                        @if($addChapterErrors->has('title'))
                            <div class="invalid-feedback">{{ $addChapterErrors->first('title') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="olbl" for="addChapterVideoUrl">Video URL (Optional YouTube Embed Link)</label>
                        <input
                            class="oinp w-100 {{ $addChapterErrors->has('video_url') ? 'is-invalid' : '' }}"
                            id="addChapterVideoUrl"
                            type="url"
                            name="video_url"
                            value="{{ $addChapterErrors->any() ? old('video_url') : '' }}"
                            placeholder="https://www.youtube.com/embed/..."
                            maxlength="2048"
                            inputmode="url"
                            aria-describedby="addChapterVideoUrlHelp"
                        >
                        <div class="form-text" id="addChapterVideoUrlHelp">Use a complete http:// or https:// address.</div>
                        @if($addChapterErrors->has('video_url'))
                            <div class="invalid-feedback d-block">{{ $addChapterErrors->first('video_url') }}</div>
                        @endif
                    </div>

                    <div>
                        <label class="olbl" for="chapterContent">Interview Lesson Content</label>
                        <div
                            id="editor-container"
                            class="admin-chapter-rich-editor"
                            data-chapter-editor
                            data-content-input="chapterContent"
                            hidden
                        ></div>
                        <textarea
                            class="oinp w-100 admin-chapter-content-source {{ $addChapterErrors->has('content') ? 'is-invalid' : '' }}"
                            id="chapterContent"
                            name="content"
                            rows="10"
                            maxlength="100000"
                        >{{ $addChapterErrors->any() ? old('content') : '' }}</textarea>
                        <div class="form-text admin-chapter-editor-fallback-note">Basic editor active. You can still edit and save the lesson content.</div>
                        @if($addChapterErrors->has('content'))
                            <div class="invalid-feedback d-block">{{ $addChapterErrors->first('content') }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4" data-saving-label="Saving Chapter...">Save Interview Chapter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Chapter Modals -->
@foreach($module->chapters as $chapter)
    @php
        $editChapterErrors = $errors->getBag('editChapter'.$chapter->id);
        $useOldChapterInput = $editChapterErrors->any();
        $editChapterContent = $useOldChapterInput
            ? old('content', '')
            : \App\Support\ModuleChapterHtml::sanitize($chapter->content);
    @endphp
    <div class="modal fade admin-chapter-modal" id="editChapterModal{{ $chapter->id }}" tabindex="-1" aria-labelledby="editChapterModalLabel{{ $chapter->id }}" aria-hidden="true" style="--bs-modal-bg:var(--sf)">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border:1px solid var(--bd)">
                <form action="{{ route('admin.modules.chapters.update', [$module, $chapter]) }}" method="POST" id="editChapterForm-{{ $chapter->id }}" class="admin-chapter-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                        <h5 class="modal-title" id="editChapterModalLabel{{ $chapter->id }}" style="color:var(--tx)">Edit Interview Chapter {{ $chapter->order }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="olbl" for="editChapterTitle{{ $chapter->id }}">Interview Chapter Title</label>
                            <input
                                class="oinp w-100 {{ $editChapterErrors->has('title') ? 'is-invalid' : '' }}"
                                id="editChapterTitle{{ $chapter->id }}"
                                type="text"
                                name="title"
                                value="{{ $useOldChapterInput ? old('title') : $chapter->title }}"
                                maxlength="255"
                                autocomplete="off"
                                required
                            >
                            @if($editChapterErrors->has('title'))
                                <div class="invalid-feedback">{{ $editChapterErrors->first('title') }}</div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="olbl" for="editChapterVideoUrl{{ $chapter->id }}">Video URL (Optional YouTube Embed Link)</label>
                            <input
                                class="oinp w-100 {{ $editChapterErrors->has('video_url') ? 'is-invalid' : '' }}"
                                id="editChapterVideoUrl{{ $chapter->id }}"
                                type="url"
                                name="video_url"
                                value="{{ $useOldChapterInput ? old('video_url') : $chapter->video_url }}"
                                placeholder="https://www.youtube.com/embed/..."
                                maxlength="2048"
                                inputmode="url"
                                aria-describedby="editChapterVideoUrlHelp{{ $chapter->id }}"
                            >
                            <div class="form-text" id="editChapterVideoUrlHelp{{ $chapter->id }}">Use a complete http:// or https:// address.</div>
                            @if($editChapterErrors->has('video_url'))
                                <div class="invalid-feedback d-block">{{ $editChapterErrors->first('video_url') }}</div>
                            @endif
                        </div>

                        <div>
                            <label class="olbl" for="editChapterContent{{ $chapter->id }}">Interview Lesson Content</label>
                            <div
                                id="edit-editor-container-{{ $chapter->id }}"
                                class="admin-chapter-rich-editor"
                                data-chapter-editor
                                data-content-input="editChapterContent{{ $chapter->id }}"
                                hidden
                            ></div>
                            <textarea
                                class="oinp w-100 admin-chapter-content-source {{ $editChapterErrors->has('content') ? 'is-invalid' : '' }}"
                                id="editChapterContent{{ $chapter->id }}"
                                name="content"
                                rows="10"
                                maxlength="100000"
                            >{{ $editChapterContent }}</textarea>
                            <div class="form-text admin-chapter-editor-fallback-note">Basic editor active. You can still edit and save the lesson content.</div>
                            @if($editChapterErrors->has('content'))
                                <div class="invalid-feedback d-block">{{ $editChapterErrors->first('content') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--bd)">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="bgrd btn px-4" data-saving-label="Updating Chapter...">Update Interview Chapter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script src="{{ asset('js/admin-module-editor.js?v=1') }}"></script>
@endpush
