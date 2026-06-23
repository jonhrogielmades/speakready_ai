@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    /* Premium aesthetics for Learning Lab */
    .ll-header {
        background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(52,211,153,0.1) 100%);
        border: 1px solid var(--bd);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    .ll-stat-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        transition: 0.3s;
    }
    .ll-stat-card:hover {
        transform: translateY(-5px);
        border-color: rgba(59,130,246,0.3);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .ll-stat-val {
        font-size: 2rem;
        font-weight: 700;
        color: var(--tx);
        margin: 10px 0;
    }
    .ll-nav-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 30px;
        background: var(--sf);
        color: var(--tx2);
        border: 1px solid var(--bd);
        text-decoration: none;
        font-weight: 500;
        transition: 0.3s;
        margin-right: 10px;
        margin-bottom: 10px;
    }
    .ll-nav-pill:hover, .ll-nav-pill.active {
        background: var(--pur);
        color: #fff;
        border-color: var(--pur);
        box-shadow: 0 4px 15px rgba(59,130,246,0.3);
    }
    .ll-category-list {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 20px;
    }
    .ll-category-item {
        display: flex;
        align-items: center;
        padding: 10px;
        border-radius: 10px;
        color: var(--tx2);
        text-decoration: none;
        transition: 0.2s;
        margin-bottom: 5px;
    }
    .ll-category-item:hover, .ll-category-item.active {
        background: rgba(59,130,246,0.1);
        color: var(--pur);
    }
    .ll-module-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 18px;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: 0.3s;
    }
    .ll-module-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        border-color: rgba(59,130,246,0.4);
    }
    .ll-progress-bar {
        width: 100%;
        height: 8px;
        background: var(--bd);
        border-radius: 4px;
        overflow: hidden;
    }
    .ll-progress-fill {
        height: 100%;
        border-radius: 4px;
        background: linear-gradient(90deg, var(--pur) 0%, #34d399 100%);
    }
    /* AI Assistant FAB */
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
    <!-- Header & Navigation -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 style="font-weight:700;color:var(--tx);margin:0">Learning Lab <i class="fa-solid fa-flask" style="color:var(--pur);font-size:1.2rem"></i></h3>
            <p style="color:var(--tx3);margin-top:5px;">Master your interview skills with structured, AI-powered learning.</p>
        </div>
        <div class="db-top-search" style="width:300px;background:var(--sf);border:1px solid var(--bd);">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search lessons, quizzes, topics...">
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div class="mb-4 pb-2" style="overflow-x:auto;white-space:nowrap;">
        <a href="{{ route('user.learning') }}" class="ll-nav-pill active"><i class="fa-solid fa-border-all"></i> Dashboard</a>
        <a href="{{ route('user.learning.star') }}" class="ll-nav-pill"><i class="fa-solid fa-star"></i> STAR Method Training</a>
        <a href="{{ route('user.learning.library') }}" class="ll-nav-pill"><i class="fa-solid fa-book-bookmark"></i> Answer Library</a>
        <a href="{{ route('user.learning.quiz') }}" class="ll-nav-pill"><i class="fa-solid fa-brain"></i> Mini Quizzes</a>
    </div>

    <!-- Dashboard Stats -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="ll-stat-card">
                <i class="fa-solid fa-layer-group" style="font-size:1.5rem;color:var(--tx3)"></i>
                <div class="ll-stat-val">24</div>
                <div style="font-size:0.85rem;color:var(--tx3);font-weight:600;text-transform:uppercase">Total Lessons</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ll-stat-card">
                <i class="fa-solid fa-circle-check" style="font-size:1.5rem;color:#34d399"></i>
                <div class="ll-stat-val">8</div>
                <div style="font-size:0.85rem;color:var(--tx3);font-weight:600;text-transform:uppercase">Completed (33%)</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ll-stat-card">
                <i class="fa-solid fa-fire" style="font-size:1.5rem;color:#f59e0b"></i>
                <div class="ll-stat-val">5 <span style="font-size:1rem;color:var(--tx3)">Days</span></div>
                <div style="font-size:0.85rem;color:var(--tx3);font-weight:600;text-transform:uppercase">Current Streak</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ll-stat-card">
                <i class="fa-solid fa-medal" style="font-size:1.5rem;color:#eab308"></i>
                <div class="ll-stat-val">3</div>
                <div style="font-size:0.85rem;color:var(--tx3);font-weight:600;text-transform:uppercase">Badges Earned</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Categories Sidebar -->
        <div class="col-lg-3">
            <div class="ll-category-list">
                <h6 style="font-weight:700;color:var(--tx);margin-bottom:15px;text-transform:uppercase;font-size:0.85rem;letter-spacing:1px">Categories</h6>
                @if(isset($categories))
                    @foreach($categories as $index => $cat)
                    <a href="#" class="ll-category-item {{ $index === 0 ? 'active' : '' }}">
                        <i class="fa-solid fa-folder-open me-2" style="font-size:0.9rem"></i> {{ $cat }}
                    </a>
                    @endforeach
                @else
                    <a href="#" class="ll-category-item active"><i class="fa-solid fa-folder-open me-2"></i> Interview Basics</a>
                    <a href="#" class="ll-category-item"><i class="fa-solid fa-folder-open me-2"></i> STAR Method</a>
                @endif
                
                <h6 style="font-weight:700;color:var(--tx);margin-top:25px;margin-bottom:15px;text-transform:uppercase;font-size:0.85rem;letter-spacing:1px">Resource Type</h6>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" value="" id="typeVideo" checked>
                    <label class="form-check-label" for="typeVideo" style="color:var(--tx2);font-size:0.9rem">Video Lessons</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" value="" id="typePdf" checked>
                    <label class="form-check-label" for="typePdf" style="color:var(--tx2);font-size:0.9rem">PDF & Worksheets</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" value="" id="typeInteractive" checked>
                    <label class="form-check-label" for="typeInteractive" style="color:var(--tx2);font-size:0.9rem">Interactive Modules</label>
                </div>
            </div>
        </div>

        <!-- Modules List -->
        <div class="col-lg-9">
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 style="font-weight:700;color:var(--tx);margin:0">Recommended For You</h5>
                <select class="form-select" style="width:auto;background:var(--sf);border-color:var(--bd);color:var(--tx2);border-radius:10px">
                    <option>Sort by Relevance</option>
                    <option>Newest First</option>
                    <option>Difficulty: Low to High</option>
                </select>
            </div>

            <div class="row g-4">
                <!-- Mock Module 1: Interactive -->
                <div class="col-md-6 col-xl-4">
                    <div class="ll-module-card">
                        <div style="height:140px;background:url('https://images.unsplash.com/photo-1573164713988-8665fc963095?auto=format&fit=crop&q=80&w=400') center/cover;position:relative">
                            <div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(0,0,0,0.8), transparent)"></div>
                            <span class="badge" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,0.2)"><i class="fa-solid fa-gamepad me-1 text-info"></i> Interactive</span>
                            <span style="position:absolute;bottom:12px;left:12px;color:#fff;font-size:0.8rem;font-weight:600"><i class="fa-regular fa-clock me-1"></i> 15 mins</span>
                        </div>
                        <div style="padding:20px;flex-grow:1;display:flex;flex-direction:column">
                            <div style="font-size:0.75rem;color:var(--pur);font-weight:700;margin-bottom:5px;text-transform:uppercase">STAR Method</div>
                            <h6 style="color:var(--tx);font-weight:600;margin-bottom:10px">Mastering the STAR Framework</h6>
                            <p style="color:var(--tx3);font-size:0.85rem;margin-bottom:20px;flex-grow:1;line-height:1.5">Learn how to structure your behavioral interview answers effectively using Situation, Task, Action, and Result.</p>
                            
                            <div style="margin-bottom:15px">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span style="font-size:0.75rem;color:var(--tx3);font-weight:600">IN PROGRESS</span>
                                    <span style="font-size:0.75rem;color:var(--tx);font-weight:700">65%</span>
                                </div>
                                <div class="ll-progress-bar">
                                    <div class="ll-progress-fill" style="width:65%"></div>
                                </div>
                            </div>
                            
                            <a href="#" class="btn btn-outline-primary w-100" style="border-radius:10px;font-weight:600">Resume Module</a>
                        </div>
                    </div>
                </div>

                <!-- Mock Module 2: Video -->
                <div class="col-md-6 col-xl-4">
                    <div class="ll-module-card">
                        <div style="height:140px;background:url('https://images.unsplash.com/photo-1552581234-26160f608093?auto=format&fit=crop&q=80&w=400') center/cover;position:relative">
                            <div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(0,0,0,0.8), transparent)"></div>
                            <span class="badge" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,0.2)"><i class="fa-solid fa-video me-1" style="color:#f43f5e"></i> Video</span>
                            <span style="position:absolute;bottom:12px;left:12px;color:#fff;font-size:0.8rem;font-weight:600"><i class="fa-regular fa-clock me-1"></i> 8 mins</span>
                        </div>
                        <div style="padding:20px;flex-grow:1;display:flex;flex-direction:column">
                            <div style="font-size:0.75rem;color:var(--pur);font-weight:700;margin-bottom:5px;text-transform:uppercase">Interview Basics</div>
                            <h6 style="color:var(--tx);font-weight:600;margin-bottom:10px">How to Answer "Tell Me About Yourself"</h6>
                            <p style="color:var(--tx3);font-size:0.85rem;margin-bottom:20px;flex-grow:1;line-height:1.5">A comprehensive guide on tackling the most common and critical opening interview question.</p>
                            
                            <div style="margin-bottom:15px">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span style="font-size:0.75rem;color:var(--tx3);font-weight:600">STATUS</span>
                                    <span style="font-size:0.75rem;color:#34d399;font-weight:700">COMPLETED <i class="fa-solid fa-check"></i></span>
                                </div>
                                <div class="ll-progress-bar">
                                    <div class="ll-progress-fill" style="width:100%;background:#34d399"></div>
                                </div>
                            </div>
                            
                            <a href="#" class="btn btn-outline-success w-100" style="border-radius:10px;font-weight:600"><i class="fa-solid fa-rotate-left me-1"></i> Review Video</a>
                        </div>
                    </div>
                </div>

                <!-- Mock Module 3: PDF -->
                <div class="col-md-6 col-xl-4">
                    <div class="ll-module-card">
                        <div style="height:140px;background:url('https://images.unsplash.com/photo-1586281380349-632531db7ed4?auto=format&fit=crop&q=80&w=400') center/cover;position:relative">
                            <div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(0,0,0,0.8), transparent)"></div>
                            <span class="badge" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,0.2)"><i class="fa-solid fa-file-pdf me-1" style="color:#ef4444"></i> PDF Guide</span>
                            <span style="position:absolute;bottom:12px;left:12px;color:#fff;font-size:0.8rem;font-weight:600"><i class="fa-solid fa-download me-1"></i> 2.4 MB</span>
                        </div>
                        <div style="padding:20px;flex-grow:1;display:flex;flex-direction:column">
                            <div style="font-size:0.75rem;color:var(--pur);font-weight:700;margin-bottom:5px;text-transform:uppercase">Cheat Sheets</div>
                            <h6 style="color:var(--tx);font-weight:600;margin-bottom:10px">Top 50 Behavioral Questions</h6>
                            <p style="color:var(--tx3);font-size:0.85rem;margin-bottom:20px;flex-grow:1;line-height:1.5">Download our comprehensive cheat sheet containing the top 50 behavioral questions and analysis.</p>
                            
                            <div style="margin-bottom:15px">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span style="font-size:0.75rem;color:var(--tx3);font-weight:600">STATUS</span>
                                    <span style="font-size:0.75rem;color:var(--tx);font-weight:700">NOT STARTED</span>
                                </div>
                                <div class="ll-progress-bar">
                                    <div class="ll-progress-fill" style="width:0%"></div>
                                </div>
                            </div>
                            
                            <a href="#" class="btn bgrd w-100" style="border-radius:10px;font-weight:600"><i class="fa-solid fa-download me-1"></i> Download PDF</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- AI Learning Assistant Floating Button -->
<a href="{{ route('user.learning.assistant') }}" class="ll-ai-fab" title="Chat with AI Learning Assistant">
    <i class="fa-solid fa-robot"></i>
</a>

@endsection
