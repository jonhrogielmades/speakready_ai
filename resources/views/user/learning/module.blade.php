@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    .module-viewer-container {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 30px;
    }
    .video-placeholder {
        width: 100%;
        height: 500px;
        background: #000;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .play-btn {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(59,130,246,0.8);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        cursor: pointer;
        transition: 0.3s;
        border: 2px solid rgba(255,255,255,0.5);
    }
    .play-btn:hover {
        transform: scale(1.1);
        background: var(--pur);
    }
    
    .ll-ai-fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--pur) 0%, #34d399 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 10px 25px rgba(59,130,246,0.4);
        cursor: pointer;
        transition: 0.3s;
        z-index: 100;
        text-decoration: none;
    }
    .ll-ai-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 15px 35px rgba(59,130,246,0.5);
    }
</style>

<div class="db-section active">
    
    <a href="{{ route('user.learning') }}" class="btn btn-sm btn-outline-secondary mb-4" style="border-radius:10px">
        <i class="fa-solid fa-arrow-left me-2"></i> Back to Learning Lab
    </a>

    <div class="module-viewer-container">
        <!-- Mock Video Player -->
        <div class="video-placeholder" style="background:linear-gradient(to right, rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('https://images.unsplash.com/photo-1552581234-26160f608093?auto=format&fit=crop&q=80&w=1200') center/cover">
            <div class="play-btn">
                <i class="fa-solid fa-play" style="margin-left:5px"></i>
            </div>
            
            <!-- Mock Controls -->
            <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.6);padding:15px;display:flex;align-items:center;gap:15px;backdrop-filter:blur(5px)">
                <i class="fa-solid fa-pause text-white" style="cursor:pointer"></i>
                <span class="text-white" style="font-size:0.8rem">02:15 / 08:30</span>
                <div style="flex-grow:1;height:4px;background:rgba(255,255,255,0.3);border-radius:2px;position:relative;cursor:pointer">
                    <div style="position:absolute;left:0;top:0;bottom:0;width:25%;background:var(--pur);border-radius:2px"></div>
                    <div style="position:absolute;left:25%;top:-4px;width:12px;height:12px;border-radius:50%;background:#fff;box-shadow:0 0 5px rgba(0,0,0,0.5)"></div>
                </div>
                <i class="fa-solid fa-volume-high text-white" style="cursor:pointer"></i>
                <i class="fa-solid fa-expand text-white" style="cursor:pointer"></i>
            </div>
        </div>
        
        <div style="padding:30px">
            <span class="badge bg-primary mb-2">Video Lesson</span>
            <h3 style="color:var(--tx);font-weight:700">{{ $module->title ?? 'How to Answer "Tell Me About Yourself"' }}</h3>
            <p style="color:var(--tx3);font-size:1.05rem;line-height:1.6;margin-top:15px;margin-bottom:30px">
                {{ $module->description ?? 'A comprehensive guide on tackling the most common and critical opening interview question. Learn the Present-Past-Future formula to structure your response perfectly.' }}
            </p>
            
            <hr style="border-color:var(--bd);margin-bottom:30px">
            
            <h5 style="color:var(--tx);font-weight:600;margin-bottom:15px">Lesson Materials</h5>
            <div class="d-flex gap-3 flex-wrap">
                <a href="#" class="btn btn-outline-secondary" style="border-radius:10px">
                    <i class="fa-solid fa-file-pdf text-danger me-2"></i> Download Slide Deck
                </a>
                <a href="#" class="btn btn-outline-secondary" style="border-radius:10px">
                    <i class="fa-solid fa-file-word text-primary me-2"></i> Transcript
                </a>
            </div>
        </div>
    </div>
</div>

<a href="{{ route('user.learning.assistant') }}" class="ll-ai-fab" title="Chat with AI Learning Assistant">
    <i class="fa-solid fa-robot"></i>
</a>

@endsection
