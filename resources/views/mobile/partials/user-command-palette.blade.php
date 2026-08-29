@php
    $isGuestQuickNavigation = (bool) ($guestQuickNavigation ?? false);
@endphp

@once
    <div
        id="userCommandPalette"
        class="ucp-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="userCommandPaletteTitle"
        aria-describedby="userCommandPaletteDescription"
        aria-hidden="true"
        hidden
    >
        <section class="ucp-dialog" role="document" tabindex="-1">
            <header class="ucp-header">
                <div class="ucp-heading">
                    <span class="ucp-heading-icon" aria-hidden="true">
                        <i class="fa-solid fa-bolt"></i>
                    </span>
                    <div>
                        <h2 id="userCommandPaletteTitle">Quick navigation</h2>
                        <p id="userCommandPaletteDescription">Choose where you want to go.</p>
                    </div>
                </div>
                <button type="button" class="ucp-close" data-ucp-close aria-label="Close quick navigation">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </header>

            <nav class="ucp-results" id="userCommandList" aria-label="Navigation destinations">
                @if ($isGuestQuickNavigation)
                <a id="gqn-destination-home" class="ucp-result" href="#hero" data-ucp-item>
                    <span class="ucp-result-icon ucp-blue"><i class="fa-solid fa-house" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Home</strong><small>Return to the SpeakReady introduction</small></span>
                    <span class="ucp-result-group">Explore</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="gqn-destination-features" class="ucp-result" href="#features" data-ucp-item>
                    <span class="ucp-result-icon ucp-purple"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Features</strong><small>Explore AI-powered interview tools</small></span>
                    <span class="ucp-result-group">Explore</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="gqn-destination-how" class="ucp-result" href="#how" data-ucp-item>
                    <span class="ucp-result-icon ucp-cyan"><i class="fa-solid fa-route" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>How It Works</strong><small>See the path from practice to progress</small></span>
                    <span class="ucp-result-group">Explore</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="gqn-destination-benefits" class="ucp-result" href="#benefits" data-ucp-item>
                    <span class="ucp-result-icon ucp-emerald"><i class="fa-solid fa-award" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Interview Categories</strong><small>Explore practice paths by interview type</small></span>
                    <span class="ucp-result-group">Explore</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="gqn-destination-developers" class="ucp-result" href="#developers" data-ucp-item>
                    <span class="ucp-result-icon ucp-indigo"><i class="fa-solid fa-code" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Developers</strong><small>Meet the team behind SpeakReady</small></span>
                    <span class="ucp-result-group">Company</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="gqn-destination-faq" class="ucp-result" href="#faq" data-ucp-item>
                    <span class="ucp-result-icon ucp-amber"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>FAQ</strong><small>Find answers to common questions</small></span>
                    <span class="ucp-result-group">Support</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="gqn-destination-contact" class="ucp-result" href="#contact" data-ucp-item>
                    <span class="ucp-result-icon ucp-rose"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Contact Us</strong><small>Send a question or feedback</small></span>
                    <span class="ucp-result-group">Support</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                @else
                <a id="ucp-destination-dashboard" class="ucp-result" href="{{ route('dashboard') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-blue"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Dashboard</strong><small>Overview of your interview workspace</small></span>
                    <span class="ucp-result-group">Workspace</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-interview" class="ucp-result" href="{{ route('interview.setup') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-purple"><i class="fa-solid fa-microphone-lines" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Mock Interview</strong><small>Start guided Philippine interview practice</small></span>
                    <span class="ucp-result-group">Practice</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-modules" class="ucp-result" href="{{ route('user.modules.index') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-emerald"><i class="fa-solid fa-book-open-reader" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Interview Modules</strong><small>Open action modules for what to prepare, rehearse, revise, and check</small></span>
                    <span class="ucp-result-group">Training</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-voice" class="ucp-result" href="{{ route('user.drills.voice') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-rose"><i class="fa-solid fa-ear-listen" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Voice Rehearsal</strong><small>Improve clarity, pace, and delivery for interview answers</small></span>
                    <span class="ucp-result-group">Training</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-missions" class="ucp-result" href="{{ route('user.missions') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-cyan"><i class="fa-solid fa-route" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Missions</strong><small>Practice real-life speaking tasks with goal-based scoring</small></span>
                    <span class="ucp-result-group">Training</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-learning" class="ucp-result" href="{{ route('user.learning') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-amber"><i class="fa-solid fa-gamepad" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Challenges</strong><small>Sharpen interview skills through scenario challenges</small></span>
                    <span class="ucp-result-group">Training</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-coach" class="ucp-result" href="{{ route('user.coach') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-purple"><i class="fa-solid fa-robot" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>AI Coach</strong><small>Get Philippine interview guidance</small></span>
                    <span class="ucp-result-group">Training</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-progress" class="ucp-result" href="{{ route('user.progress') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-emerald"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Progress</strong><small>See Philippines interview trends and growth</small></span>
                    <span class="ucp-result-group">Insights</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-feedback" class="ucp-result" href="{{ route('user.feedback') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-blue"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Feedback</strong><small>Review answers and coaching feedback</small></span>
                    <span class="ucp-result-group">Insights</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-reports" class="ucp-result" href="{{ route('user.reports') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-cyan"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Reports</strong><small>Open your interview records and results</small></span>
                    <span class="ucp-result-group">Insights</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-leaderboard" class="ucp-result" href="{{ route('user.leaderboard') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-amber"><i class="fa-solid fa-trophy" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Mastery</strong><small>Compare with your own assessment baseline</small></span>
                    <span class="ucp-result-group">Insights</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-notifications" class="ucp-result" href="{{ route('user.notifications') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-rose"><i class="fa-solid fa-bell" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Notifications</strong><small>Catch up on alerts and updates</small></span>
                    <span class="ucp-result-group">Account</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>

                <a id="ucp-destination-account" class="ucp-result" href="{{ route('user.account') }}" data-ucp-item>
                    <span class="ucp-result-icon ucp-indigo"><i class="fa-solid fa-user-gear" aria-hidden="true"></i></span>
                    <span class="ucp-result-copy"><strong>Account Management</strong><small>Update your profile and preferences</small></span>
                    <span class="ucp-result-group">Account</span>
                    <i class="fa-solid fa-arrow-right ucp-result-arrow" aria-hidden="true"></i>
                </a>
                @endif
            </nav>

            @if ($isGuestQuickNavigation)
            <footer class="ucp-footer ucp-guest-footer">
                <div class="ucp-guest-actions">
                    <button type="button" class="ucp-guest-action ucp-guest-login" data-ucp-action data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('login')">
                        <i class="fa-regular fa-user fa-sm" aria-hidden="true"></i>
                        Login
                    </button>
                    <button type="button" class="ucp-guest-action ucp-guest-register" data-ucp-action data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('signup')">
                        Start Practicing
                        <i class="fa-solid fa-arrow-right fa-sm" aria-hidden="true"></i>
                    </button>
                </div>
            </footer>
            @else
            <footer class="ucp-footer">
                <span class="ucp-status">15 destinations</span>
                <span class="ucp-help"><kbd>&uarr;</kbd><kbd>&darr;</kbd> move <kbd>Enter</kbd> open</span>
            </footer>
            @endif
        </section>
    </div>

    <style>
        .ucp-backdrop[hidden] { display: none !important; }
        .ucp-backdrop {
            position: fixed;
            inset: 0;
            z-index: 12000;
            display: grid;
            place-items: start center;
            padding: clamp(72px, 10vh, 120px) 18px 24px;
            background: rgba(3, 8, 20, .72);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            animation: ucp-fade-in .16s ease-out both;
        }
        .ucp-dialog {
            width: min(680px, 100%);
            max-height: min(720px, calc(100dvh - clamp(96px, 14vh, 152px)));
            display: flex;
            flex-direction: column;
            overflow: hidden;
            color: var(--tx, #f8fafc);
            background:
                linear-gradient(145deg, rgba(96, 165, 250, .08), transparent 34%),
                var(--bg2, #111827);
            border: 1px solid color-mix(in srgb, var(--bd, #334155) 82%, #60a5fa 18%);
            border-radius: 22px;
            box-shadow: 0 28px 90px rgba(0, 0, 0, .46), 0 0 0 1px rgba(255, 255, 255, .025) inset;
            animation: ucp-dialog-in .2s cubic-bezier(.2, .8, .2, 1) both;
        }
        .ucp-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px 15px;
        }
        .ucp-heading { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .ucp-heading-icon {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            display: grid;
            place-items: center;
            color: #dbeafe;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(59, 130, 246, .28);
        }
        .ucp-heading h2 { margin: 0; color: var(--tx, #f8fafc); font-size: 1rem; font-weight: 700; letter-spacing: -.01em; }
        .ucp-heading p { margin: 3px 0 0; color: var(--tx3, #94a3b8); font-size: .77rem; line-height: 1.4; }
        .ucp-close {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: grid;
            place-items: center;
            padding: 0;
            color: var(--tx2, #cbd5e1);
            background: var(--bg3, #1e293b);
            border: 1px solid var(--bd, #334155);
            border-radius: 11px;
            cursor: pointer;
            transition: border-color .16s ease, color .16s ease, transform .16s ease;
        }
        .ucp-close:hover { color: var(--tx, #fff); border-color: #64748b; transform: translateY(-1px); }
        .ucp-close:focus-visible,
        .ucp-result:focus-visible,
        .ucp-guest-action:focus-visible { outline: 3px solid rgba(96, 165, 250, .48); outline-offset: 2px; }
        .ucp-results {
            min-height: 120px;
            overflow: auto;
            overscroll-behavior: contain;
            padding: 2px 12px 10px;
            scrollbar-width: thin;
            scrollbar-color: var(--bd, #334155) transparent;
        }
        .ucp-result {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto 20px;
            align-items: center;
            gap: 12px;
            width: 100%;
            min-height: 64px;
            padding: 9px 10px;
            color: var(--tx2, #cbd5e1);
            font-family: inherit;
            text-align: left;
            text-decoration: none;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 13px;
            cursor: pointer;
            appearance: none;
            transition: background-color .12s ease, border-color .12s ease, transform .12s ease;
        }
        .ucp-result:hover,
        .ucp-result.is-active {
            color: var(--tx, #f8fafc);
            background: color-mix(in srgb, var(--bg3, #1e293b) 87%, #3b82f6 13%);
            border-color: color-mix(in srgb, var(--bd, #334155) 72%, #60a5fa 28%);
        }
        .ucp-result.is-active {
            background:
                linear-gradient(135deg, rgba(37, 99, 235, .24), rgba(14, 165, 233, .13)),
                color-mix(in srgb, var(--bg3, #1e293b) 84%, #2563eb 16%);
            border-color: rgba(96, 165, 250, .58);
            box-shadow: 0 12px 28px rgba(37, 99, 235, .18), 0 0 0 1px rgba(147, 197, 253, .08) inset;
        }
        .ucp-result.is-active .ucp-result-icon {
            color: #ffffff;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            box-shadow: 0 8px 18px rgba(37, 99, 235, .28);
        }
        .ucp-result.is-active .ucp-result-copy small {
            color: color-mix(in srgb, var(--tx2, #cbd5e1) 84%, #93c5fd 16%);
        }
        .ucp-result:active { transform: scale(.992); }
        .ucp-result-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            font-size: .92rem;
        }
        .ucp-blue { color: #93c5fd; background: rgba(59, 130, 246, .14); }
        .ucp-purple { color: #c4b5fd; background: rgba(139, 92, 246, .14); }
        .ucp-cyan { color: #67e8f9; background: rgba(6, 182, 212, .13); }
        .ucp-indigo { color: #a5b4fc; background: rgba(99, 102, 241, .14); }
        .ucp-emerald { color: #6ee7b7; background: rgba(16, 185, 129, .13); }
        .ucp-rose { color: #fda4af; background: rgba(244, 63, 94, .13); }
        .ucp-amber { color: #fcd34d; background: rgba(245, 158, 11, .13); }
        .ucp-result-copy { min-width: 0; }
        .ucp-result-copy strong { display: block; overflow: hidden; color: inherit; font-size: .84rem; font-weight: 650; line-height: 1.35; text-overflow: ellipsis; white-space: nowrap; }
        .ucp-result-copy small { display: block; overflow: hidden; margin-top: 3px; color: var(--tx3, #94a3b8); font-size: .71rem; line-height: 1.35; text-overflow: ellipsis; white-space: nowrap; }
        .ucp-result-group { color: var(--tx3, #94a3b8); font-size: .65rem; font-weight: 600; letter-spacing: .025em; text-transform: uppercase; }
        .ucp-result-arrow { color: var(--tx3, #64748b); font-size: .68rem; opacity: 0; transform: translateX(-4px); transition: opacity .12s ease, transform .12s ease; }
        .ucp-result:hover .ucp-result-arrow,
        .ucp-result.is-active .ucp-result-arrow { opacity: 1; transform: translateX(0); }
        .ucp-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 46px;
            padding: 10px 22px;
            color: var(--tx3, #94a3b8);
            background: color-mix(in srgb, var(--bg3, #1e293b) 54%, transparent);
            border-top: 1px solid var(--bd, #334155);
            font-size: .68rem;
        }
        .ucp-status { font-weight: 600; }
        .ucp-help { display: flex; align-items: center; gap: 5px; }
        .ucp-footer.ucp-guest-footer {
            min-height: auto;
            padding: 12px 16px 16px;
        }
        .ucp-guest-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            width: 100%;
        }
        .ucp-guest-action {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 12px;
            color: var(--tx, #f8fafc);
            font-family: inherit;
            font-size: .76rem;
            font-weight: 700;
            line-height: 1;
            border: 1px solid var(--bd, #334155);
            border-radius: 11px;
            cursor: pointer;
            transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }
        .ucp-guest-action:hover { transform: translateY(-1px); }
        .ucp-guest-login { background: var(--bg2, #111827); }
        .ucp-guest-register {
            color: #fff;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            border-color: transparent;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .22);
        }
        .ucp-help kbd {
            min-width: 22px;
            padding: 2px 5px;
            color: var(--tx2, #cbd5e1);
            font-family: inherit;
            font-size: .64rem;
            font-weight: 600;
            line-height: 1.35;
            text-align: center;
            background: var(--bg2, #111827);
            border: 1px solid var(--bd, #334155);
            border-radius: 5px;
            box-shadow: none;
        }
        @keyframes ucp-fade-in { from { opacity: 0; } }
        @keyframes ucp-dialog-in { from { opacity: 0; transform: translateY(-10px) scale(.985); } }

        .lm .ucp-backdrop { background: rgba(15, 23, 42, .52); }
        .lm .ucp-dialog { box-shadow: 0 28px 80px rgba(15, 23, 42, .22), 0 0 0 1px rgba(255, 255, 255, .6) inset; }

        @media (max-width: 640px) {
            .ucp-backdrop { place-items: end center; padding: 18px 0 0; }
            .ucp-dialog {
                width: 100%;
                max-height: min(88dvh, 760px);
                padding-bottom: env(safe-area-inset-bottom, 0px);
                border-right: 0;
                border-bottom: 0;
                border-left: 0;
                border-radius: 22px 22px 0 0;
                animation-name: ucp-dialog-mobile-in;
            }
            .ucp-dialog::before { content: ""; width: 40px; height: 4px; flex: 0 0 4px; margin: 8px auto 0; background: var(--bd, #475569); border-radius: 99px; }
            .ucp-header { padding: 13px 16px 12px; }
            .ucp-heading-icon { width: 36px; height: 36px; flex-basis: 36px; }
            .ucp-heading p { display: none; }
            .ucp-results { padding-right: 8px; padding-left: 8px; }
            .ucp-result { grid-template-columns: 40px minmax(0, 1fr) 16px; gap: 10px; min-height: 62px; padding: 8px; }
            .ucp-result-icon { width: 38px; height: 38px; }
            .ucp-result-group { display: none; }
            .ucp-result-copy strong { font-size: .82rem; }
            .ucp-result-copy small { font-size: .69rem; }
            .ucp-result-arrow { opacity: .55; transform: none; }
            .ucp-footer { min-height: 42px; padding: 9px 16px; }
            .ucp-help { display: none; }
        }
        @keyframes ucp-dialog-mobile-in { from { opacity: 0; transform: translateY(24px); } }
        @media (prefers-reduced-motion: reduce) {
            .ucp-backdrop,
            .ucp-dialog { animation: none; }
            .ucp-result,
            .ucp-close,
            .ucp-result-arrow,
            .ucp-guest-action { transition: none; }
        }
    </style>

    <script src="{{ asset('js/user-ui.js') }}?v=16" defer></script>
@endonce
