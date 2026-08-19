<style>
    .admin-motion-title-wrap {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: 10px !important;
        flex-wrap: nowrap !important;
        text-align: left !important;
    }

    .admin-motion-title-card {
        --admin-title-card-bg: linear-gradient(135deg, color-mix(in srgb, var(--bg3, #ffffff) 88%, transparent), color-mix(in srgb, var(--admin-title-main, #2563eb) 12%, var(--bg2, #eff6ff)));
        --admin-title-panel: color-mix(in srgb, var(--admin-title-main, #2563eb) 10%, var(--bg3, #ffffff));
        --admin-title-panel-strong: color-mix(in srgb, var(--admin-title-main, #2563eb) 18%, var(--bg3, #ffffff));
        --admin-title-panel-soft: color-mix(in srgb, var(--admin-title-main, #2563eb) 7%, var(--bg, #ffffff));
        --admin-title-border: color-mix(in srgb, var(--admin-title-main, #2563eb) 20%, var(--bd, #dbe4f0));
        width: 100%;
        max-width: none;
        min-height: 118px;
        padding: 18px 18px 16px;
        border: 1px solid var(--admin-title-border);
        border-radius: 14px;
        background:
            radial-gradient(circle at 92% 18%, var(--admin-title-glow, rgba(34, 211, 238, 0.18)), transparent 30%),
            var(--admin-title-card-bg);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.09);
        color: var(--tx, #0f172a);
        display: grid;
        grid-template-columns: minmax(0, 1fr) 122px;
        align-items: center;
        justify-content: center;
        gap: 14px;
        overflow: hidden;
        position: relative;
    }

    .admin-motion-title-copy {
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        position: relative;
        z-index: 1;
    }

    .admin-motion-title-copy p {
        color: var(--tx2, #475569) !important;
        max-width: 34rem;
        margin: 6px 0 0 !important;
        line-height: 1.4;
        text-align: left !important;
    }

    .admin-motion-title-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: var(--admin-title-main, #2563eb);
        background: color-mix(in srgb, var(--admin-title-main, #2563eb) 13%, transparent);
        border: 1px solid color-mix(in srgb, var(--admin-title-main, #2563eb) 20%, transparent);
        box-shadow: 0 8px 20px color-mix(in srgb, var(--admin-title-main, #2563eb) 14%, transparent);
        animation: adminTitleIconFloat 4.2s ease-in-out infinite;
    }

    .admin-motion-title-art {
        width: 122px;
        height: 90px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        pointer-events: none;
    }

    .admin-motion-title-art svg {
        width: 122px;
        height: 90px;
        display: block;
        filter: drop-shadow(0 12px 18px color-mix(in srgb, var(--admin-title-main, #2563eb) 18%, transparent));
    }

    .admin-motion-title-art .admin-motion-fill {
        fill: var(--admin-title-panel);
    }

    .admin-motion-title-art .admin-motion-fill-strong {
        fill: var(--admin-title-panel-strong);
    }

    .admin-motion-title-art .admin-motion-fill-soft {
        fill: var(--admin-title-panel-soft);
    }

    .admin-motion-title-art .admin-motion-screen {
        animation: adminTitleScreenFloat 4.8s ease-in-out infinite;
        transform-origin: center;
    }

    .admin-motion-title-art .admin-motion-spark {
        animation: adminTitleSpark 2.4s ease-in-out infinite;
        transform-origin: center;
    }

    .admin-motion-title-art .admin-motion-spark:nth-child(2) {
        animation-delay: .42s;
    }

    .admin-motion-title-art .admin-motion-wave {
        stroke-dasharray: 38;
        animation: adminTitleWave 2.8s ease-in-out infinite;
    }

    .admin-motion-title-icon svg {
        width: 21px;
        height: 21px;
        display: block;
    }

    .admin-motion-title-icon .admin-motion-dot {
        transform-origin: center;
        animation: adminTitleDotPulse 1.8s ease-in-out infinite;
    }

    .admin-motion-title-icon .admin-motion-bar {
        transform-origin: bottom;
        animation: adminTitleBarRise 2.2s ease-in-out infinite;
    }

    .admin-motion-title-icon .admin-motion-bar:nth-child(3) {
        animation-delay: 0.18s;
    }

    .admin-motion-title-icon .admin-motion-bar:nth-child(4) {
        animation-delay: 0.34s;
    }

    .admin-motion-title-wrap > i {
        display: none !important;
    }

    html:not(.lm) .admin-motion-title-card {
        --admin-title-card-bg: linear-gradient(135deg, color-mix(in srgb, var(--bg3, #171927) 82%, transparent), color-mix(in srgb, var(--admin-title-main, #60a5fa) 18%, var(--bg2, #10131f)));
        --admin-title-panel: color-mix(in srgb, var(--admin-title-main, #60a5fa) 22%, var(--bg3, #171927));
        --admin-title-panel-strong: color-mix(in srgb, var(--admin-title-main, #60a5fa) 34%, var(--bg3, #171927));
        --admin-title-panel-soft: color-mix(in srgb, var(--admin-title-main, #60a5fa) 12%, var(--bg, #0b1020));
        --admin-title-border: color-mix(in srgb, var(--admin-title-main, #60a5fa) 34%, var(--bd, rgba(148, 163, 184, .22)));
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.28);
    }

    html:not(.lm) .admin-motion-title-icon {
        background: color-mix(in srgb, var(--admin-title-main, #60a5fa) 18%, rgba(15, 23, 42, .72));
        border-color: color-mix(in srgb, var(--admin-title-main, #60a5fa) 38%, rgba(148, 163, 184, .22));
    }

    @keyframes adminTitleIconFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }

    @keyframes adminTitleDotPulse {
        0%, 100% { opacity: 0.68; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.12); }
    }

    @keyframes adminTitleBarRise {
        0%, 100% { transform: scaleY(0.72); opacity: 0.7; }
        50% { transform: scaleY(1); opacity: 1; }
    }

    @keyframes adminTitleScreenFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-5px) rotate(-1deg); }
    }

    @keyframes adminTitleSpark {
        0%, 100% { opacity: .45; transform: scale(.88); }
        50% { opacity: 1; transform: scale(1.08); }
    }

    @keyframes adminTitleWave {
        0% { stroke-dashoffset: 38; opacity: .25; }
        45%, 70% { stroke-dashoffset: 0; opacity: .95; }
        100% { stroke-dashoffset: -38; opacity: .25; }
    }

    @media (max-width: 767px) {
        #mob-content .admin-motion-title-wrap {
            max-width: 100%;
            font-size: clamp(1.05rem, 4.4vw, 1.45rem) !important;
            line-height: 1.15 !important;
        }

        #mob-content .admin-motion-title-card {
            min-height: 104px;
            padding: 14px 12px;
            border-radius: 14px;
            grid-template-columns: minmax(0, 1fr) 92px;
            gap: 8px;
        }

        #mob-content .admin-motion-title-copy {
            max-width: 100%;
        }

        #mob-content .admin-motion-title-icon {
            width: 30px;
            height: 30px;
            border-radius: 10px;
        }

        #mob-content .admin-motion-title-icon svg {
            width: 18px;
            height: 18px;
        }

        #mob-content .admin-motion-title-art {
            width: 92px;
            height: 74px;
        }

        #mob-content .admin-motion-title-art svg {
            width: 92px;
            height: 74px;
        }

        #mob-content .admin-motion-title-copy p {
            font-size: .78rem !important;
            line-height: 1.25 !important;
            max-width: min(100%, 13.5rem);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .admin-motion-title-icon,
        .admin-motion-title-icon .admin-motion-dot,
        .admin-motion-title-icon .admin-motion-bar,
        .admin-motion-title-art .admin-motion-screen,
        .admin-motion-title-art .admin-motion-spark,
        .admin-motion-title-art .admin-motion-wave {
            animation: none !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('#mob-content .db-content') || document.querySelector('.db-content');
    if (!root) return;

    const variants = [
        { key: 'settings', words: ['setting', 'configuration'], main: '#7c3aed', soft: 'rgba(237, 233, 254, .86)', glow: 'rgba(168, 85, 247, .2)', icon: 'gear', art: 'controls' },
        { key: 'question', words: ['question', 'bank', 'quiz'], main: '#0891b2', soft: 'rgba(207, 250, 254, .82)', glow: 'rgba(6, 182, 212, .2)', icon: 'book', art: 'cards' },
        { key: 'module', words: ['module', 'learning lab'], main: '#16a34a', soft: 'rgba(220, 252, 231, .8)', glow: 'rgba(34, 197, 94, .2)', icon: 'layers', art: 'stack' },
        { key: 'game', words: ['game'], main: '#ea580c', soft: 'rgba(255, 237, 213, .82)', glow: 'rgba(249, 115, 22, .22)', icon: 'spark', art: 'game' },
        { key: 'feedback', words: ['feedback', 'complaint', 'audit'], main: '#db2777', soft: 'rgba(252, 231, 243, .82)', glow: 'rgba(236, 72, 153, .2)', icon: 'chat', art: 'messages' },
        { key: 'session', words: ['session', 'monitoring', 'archive'], main: '#2563eb', soft: 'rgba(219, 234, 254, .82)', glow: 'rgba(59, 130, 246, .2)', icon: 'pulse', art: 'chart' },
        { key: 'notification', words: ['notification'], main: '#4f46e5', soft: 'rgba(224, 231, 255, .84)', glow: 'rgba(99, 102, 241, .2)', icon: 'bell', art: 'bell' },
        { key: 'provider', words: ['provider', 'ai'], main: '#0d9488', soft: 'rgba(204, 251, 241, .82)', glow: 'rgba(20, 184, 166, .2)', icon: 'cpu', art: 'network' },
        { key: 'category', words: ['category', 'categories'], main: '#0284c7', soft: 'rgba(224, 242, 254, .84)', glow: 'rgba(14, 165, 233, .2)', icon: 'folder', art: 'folders' },
        { key: 'user', words: ['user', 'account', 'contact', 'message'], main: '#059669', soft: 'rgba(209, 250, 229, .82)', glow: 'rgba(16, 185, 129, .2)', icon: 'users', art: 'people' },
        { key: 'dashboard', words: ['dashboard', 'overview'], main: '#2563eb', soft: 'rgba(226, 239, 255, .82)', glow: 'rgba(34, 211, 238, .18)', icon: 'bars', art: 'dashboard' },
    ];

    const chooseVariant = (text) => {
        const clean = text.toLowerCase();
        return variants.find((variant) => variant.words.some((word) => clean.includes(word))) || variants[variants.length - 1];
    };

    const iconSvg = (type) => {
        const icons = {
            gear: '<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" fill="none" stroke="currentColor" stroke-width="2"/><path class="admin-motion-bar" d="M4 12h2m12 0h2M12 4v2m0 12v2m5.7-13.7-1.4 1.4M7.7 16.3l-1.4 1.4m0-11.4 1.4 1.4m8.6 8.6 1.4 1.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
            book: '<path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H20v16H7.5A2.5 2.5 0 0 0 5 21V5.5Z" fill="none" stroke="currentColor" stroke-width="2"/><path class="admin-motion-bar" d="M9 8h7M9 12h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
            layers: '<path d="m12 3 9 5-9 5-9-5 9-5Z" fill="none" stroke="currentColor" stroke-width="2"/><path class="admin-motion-bar" d="m5 12 7 4 7-4M5 16l7 4 7-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
            spark: '<path class="admin-motion-dot" d="m12 2 1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8L12 2Z" fill="currentColor"/><circle cx="18" cy="18" r="2" fill="currentColor" opacity=".7"/>',
            chat: '<path d="M5 5h14v10H9l-4 4V5Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path class="admin-motion-bar" d="M8 9h8M8 12h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
            pulse: '<path d="M4 13h4l2-6 4 12 2-6h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle class="admin-motion-dot" cx="18" cy="7" r="2" fill="currentColor"/>',
            bell: '<path d="M6 17h12l-1.5-2v-4.5a4.5 4.5 0 0 0-9 0V15L6 17Z" fill="none" stroke="currentColor" stroke-width="2"/><path class="admin-motion-dot" d="M10 19a2 2 0 0 0 4 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
            cpu: '<rect x="7" y="7" width="10" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path class="admin-motion-bar" d="M4 9h3m-3 6h3m10-6h3m-3 6h3M9 4v3m6-3v3M9 17v3m6-3v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
            folder: '<path d="M3 7h7l2 2h9v10H3V7Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path class="admin-motion-bar" d="M7 13h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
            users: '<circle cx="9" cy="9" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 20a6 6 0 0 1 12 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle class="admin-motion-dot" cx="17" cy="10" r="2" fill="currentColor"/>',
            bars: '<circle class="admin-motion-dot" cx="7" cy="7" r="3" fill="currentColor" opacity=".78"></circle><rect class="admin-motion-bar" x="5" y="12" width="3" height="7" rx="1.5" fill="currentColor" opacity=".72"></rect><rect class="admin-motion-bar" x="10.5" y="9" width="3" height="10" rx="1.5" fill="currentColor" opacity=".86"></rect><rect class="admin-motion-bar" x="16" y="5" width="3" height="14" rx="1.5" fill="currentColor"></rect><path d="M5 20h15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".55"></path>',
        };
        return icons[type] || icons.bars;
    };

    const artSvg = (variant) => {
        const art = {
            controls: '<rect class="admin-motion-fill admin-motion-screen" x="26" y="22" width="86" height="58" rx="16" stroke="currentColor" stroke-width="2" opacity=".72"/><path class="admin-motion-wave" d="M42 40h48M42 60h48" stroke="currentColor" stroke-width="5" stroke-linecap="round"/><circle class="admin-motion-screen" cx="96" cy="40" r="8" fill="currentColor"/><circle class="admin-motion-screen" cx="54" cy="60" r="8" fill="currentColor" opacity=".72"/>',
            cards: '<rect class="admin-motion-fill admin-motion-screen" x="30" y="22" width="64" height="48" rx="12" stroke="currentColor" stroke-width="2"/><rect class="admin-motion-fill-soft" x="46" y="36" width="64" height="48" rx="12" stroke="currentColor" stroke-width="2" opacity=".92"/><path class="admin-motion-wave" d="M58 51h32M58 64h22" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>',
            stack: '<path class="admin-motion-fill admin-motion-screen" d="m70 18 48 24-48 24-48-24 48-24Z" stroke="currentColor" stroke-width="2"/><path d="m30 55 40 20 40-20M30 68l40 20 40-20" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" opacity=".75"/>',
            game: '<rect class="admin-motion-fill admin-motion-screen" x="26" y="32" width="88" height="42" rx="20" stroke="currentColor" stroke-width="2"/><path d="M50 45v16M42 53h16" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><circle class="admin-motion-spark" cx="88" cy="49" r="5" fill="currentColor"/><circle class="admin-motion-spark" cx="100" cy="59" r="5" fill="currentColor" opacity=".7"/>',
            messages: '<path class="admin-motion-fill admin-motion-screen" d="M28 26h82v42H52L34 82V68h-6V26Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path class="admin-motion-wave" d="M46 42h44M46 56h30" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>',
            chart: '<rect class="admin-motion-fill admin-motion-screen" x="24" y="24" width="92" height="56" rx="14" stroke="currentColor" stroke-width="2" opacity=".78"/><path class="admin-motion-wave" d="M34 62C46 42 56 72 68 54s24-8 36-24" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/><circle class="admin-motion-spark" cx="104" cy="30" r="6" fill="currentColor"/>',
            bell: '<path class="admin-motion-fill admin-motion-screen" d="M70 24c18 0 28 12 28 30v12l10 12H32l10-12V54c0-18 10-30 28-30Z" stroke="currentColor" stroke-width="2"/><path class="admin-motion-wave" d="M44 28c-8 8-12 18-12 30M96 28c8 8 12 18 12 30" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" opacity=".65"/>',
            network: '<circle class="admin-motion-fill admin-motion-screen" cx="70" cy="52" r="16" stroke="currentColor" stroke-width="2"/><circle class="admin-motion-fill-soft" cx="34" cy="34" r="9" stroke="currentColor" stroke-width="2"/><circle class="admin-motion-fill-soft" cx="106" cy="34" r="9" stroke="currentColor" stroke-width="2"/><circle class="admin-motion-fill-soft" cx="106" cy="76" r="9" stroke="currentColor" stroke-width="2"/><path class="admin-motion-wave" d="M43 38 56 46m28 0 14-8M84 60l14 12" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>',
            folders: '<path class="admin-motion-fill admin-motion-screen" d="M26 34h34l8 8h46v36H26V34Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M36 26h30l8 8h34" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" opacity=".6"/>',
            people: '<circle class="admin-motion-fill admin-motion-screen" cx="58" cy="42" r="14" stroke="currentColor" stroke-width="2"/><circle class="admin-motion-fill-soft" cx="90" cy="46" r="11" stroke="currentColor" stroke-width="2"/><path class="admin-motion-wave" d="M34 80c8-18 40-18 48 0M76 78c8-12 26-12 32 0" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>',
            dashboard: '<rect class="admin-motion-fill admin-motion-screen" x="19" y="21" width="100" height="64" rx="15" stroke="currentColor" stroke-width="2" opacity=".76"/><rect class="admin-motion-fill-soft" x="29" y="31" width="80" height="44" rx="12"/><path class="admin-motion-wave" d="M36 62 C48 46, 60 76, 74 58 S98 55, 106 42" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><circle cx="44" cy="44" r="5" fill="currentColor" opacity=".6"/><circle cx="98" cy="62" r="5" fill="#22c55e"/>',
        };

        return `
            <span class="admin-motion-title-art" aria-hidden="true">
                <svg viewBox="0 0 140 104" role="img" focusable="false">
                    <g color="${variant.main}">
                        ${art[variant.art] || art.dashboard}
                        <circle class="admin-motion-spark" cx="23" cy="18" r="4" fill="#facc15"></circle>
                        <circle class="admin-motion-spark" cx="123" cy="32" r="5" fill="${variant.main}" opacity=".75"></circle>
                    </g>
                </svg>
            </span>
        `;
    };

    const headings = root.querySelectorAll('.db-section > :is(.d-flex, .mb-4, .container-fluid) :is(h1, h2, h3, h4), .container-fluid > :is(.d-flex, .mb-4) :is(h1, h2, h3, h4)');
    headings.forEach((heading, index) => {
        if (index > 0 || heading.dataset.adminMotionTitle === '1') return;

        const variant = chooseVariant(heading.textContent || document.title || '');
        heading.dataset.adminMotionTitle = '1';
        heading.classList.add('admin-motion-title-wrap');
        if (heading.parentElement) {
            const card = heading.parentElement;
            const description = heading.nextElementSibling && heading.nextElementSibling.tagName === 'P'
                ? heading.nextElementSibling
                : null;
            const copy = document.createElement('div');

            copy.className = 'admin-motion-title-copy';
            card.classList.add('admin-motion-title-card');
            card.style.setProperty('--admin-title-main', variant.main);
            card.style.setProperty('--admin-title-soft', variant.soft);
            card.style.setProperty('--admin-title-glow', variant.glow);
            card.insertBefore(copy, heading);
            copy.appendChild(heading);
            if (description) {
                copy.appendChild(description);
            }

            card.insertAdjacentHTML('beforeend', artSvg(variant));
        }
        heading.insertAdjacentHTML('afterbegin', `
            <span class="admin-motion-title-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" role="img" focusable="false">
                    ${iconSvg(variant.icon)}
                </svg>
            </span>
        `);
    });
});
</script>
