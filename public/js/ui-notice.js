(function (window, document) {
    'use strict';

    var noticeId = 'srUiNotice';
    var hideTimer = 0;

    function getNotice() {
        var notice = document.getElementById(noticeId);
        if (notice) return notice;

        notice = document.createElement('div');
        notice.id = noticeId;
        notice.setAttribute('role', 'status');
        notice.setAttribute('aria-live', 'polite');
        notice.setAttribute('aria-atomic', 'true');
        notice.hidden = true;
        notice.style.cssText = [
            'position:fixed',
            'inset:auto max(16px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left))',
            'z-index:14000',
            'display:flex',
            'align-items:flex-start',
            'gap:12px',
            'width:min(420px, calc(100vw - 32px))',
            'margin:0 auto',
            'padding:14px 16px',
            'border:1px solid rgba(148,163,184,.28)',
            'border-radius:14px',
            'background:rgba(15,23,42,.96)',
            'color:#fff',
            'box-shadow:0 18px 44px rgba(15,23,42,.28)',
            'font:600 14px/1.45 Poppins,Arial,sans-serif',
            'opacity:0',
            'transform:translateY(12px)',
            'pointer-events:none',
            'transition:opacity .18s ease,transform .18s ease'
        ].join(';');

        var message = document.createElement('span');
        message.dataset.srUiNoticeMessage = 'true';
        message.style.flex = '1 1 auto';

        var close = document.createElement('button');
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.textContent = 'x';
        close.style.cssText = 'flex:0 0 auto;margin:-3px -4px 0 0;padding:0;border:0;background:transparent;color:inherit;font:700 22px/1 Arial,sans-serif;cursor:pointer;opacity:.8';
        close.addEventListener('click', hideNotice);

        notice.appendChild(message);
        notice.appendChild(close);
        (document.body || document.documentElement).appendChild(notice);
        return notice;
    }

    function hideNotice() {
        window.clearTimeout(hideTimer);
        var notice = document.getElementById(noticeId);
        if (!notice) return;

        notice.style.opacity = '0';
        notice.style.transform = 'translateY(12px)';
        notice.style.pointerEvents = 'none';
        window.setTimeout(function () {
            if (notice.style.opacity === '0') notice.hidden = true;
        }, 180);
    }

    function showNotice(message, options) {
        if (!message) return;

        var notice = getNotice();
        var messageElement = notice.querySelector('[data-sr-ui-notice-message]');
        if (!messageElement) return;

        var tone = options && options.tone === 'success' ? 'success' : 'error';
        notice.style.borderColor = tone === 'success' ? 'rgba(74,222,128,.42)' : 'rgba(248,113,113,.42)';
        notice.style.background = tone === 'success' ? 'rgba(20,83,45,.96)' : 'rgba(127,29,29,.96)';
        messageElement.textContent = String(message);
        notice.hidden = false;
        window.requestAnimationFrame(function () {
            notice.style.opacity = '1';
            notice.style.transform = 'translateY(0)';
            notice.style.pointerEvents = 'auto';
        });

        window.clearTimeout(hideTimer);
        hideTimer = window.setTimeout(hideNotice, Math.max(3200, Number(options && options.duration) || 5200));
    }

    window.SpeakReadyUiNotice = showNotice;
    window.SpeakReadyUiNotice.hide = hideNotice;
}(window, document));
