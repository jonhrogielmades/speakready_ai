@if(\App\Support\ModuleVideoUrl::safeExternal($chapter->video_url))
    @php $embedUrl = \App\Support\ModuleVideoUrl::embed($chapter->video_url); @endphp
    <div class="module-lesson-video mt-3">
        @if($embedUrl)
            <iframe src="{{ $embedUrl }}" title="Video lesson: {{ $chapter->title }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
        @else
            <a href="{{ $chapter->video_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">Open video lesson <i class="fa-solid fa-arrow-up-right-from-square ms-1" aria-hidden="true"></i></a>
        @endif
    </div>
@endif
