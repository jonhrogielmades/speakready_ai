(function () {
    'use strict';

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }
        callback();
    }

    let isDark = (window.SpeakReadyTheme ? window.SpeakReadyTheme.get() : localStorage.getItem('theme')) !== 'light';

    function applyTheme() {
        const appliedTheme = window.SpeakReadyTheme
            ? window.SpeakReadyTheme.apply(isDark ? 'dark' : 'light', false)
            : (isDark ? 'dark' : 'light');
        const sunIcon = document.getElementById('suni');
        const moonIcon = document.getElementById('mooni');

        isDark = appliedTheme !== 'light';
        if (sunIcon && moonIcon) {
            sunIcon.style.display = isDark ? 'none' : 'inline';
            moonIcon.style.display = isDark ? 'inline' : 'none';
        }
    }

    function toggleTheme() {
        isDark = !isDark;
        if (window.SpeakReadyTheme) {
            isDark = window.SpeakReadyTheme.toggle() !== 'light';
            applyTheme();
            return;
        }
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        applyTheme();
    }

    function setupGuestHeaderClock() {
        const clock = document.getElementById('guestHeaderClock');
        const dateElement = document.getElementById('guestHeaderDate');
        const timeElement = document.getElementById('guestHeaderTime');
        if (!clock || !dateElement || !timeElement || clock.dataset.clockInitialized === 'true') return;
        clock.dataset.clockInitialized = 'true';

        const requestedLocale = document.documentElement.lang || navigator.language || 'en';
        let locale = 'en';
        try {
            if (Intl.DateTimeFormat.supportedLocalesOf([requestedLocale]).length) locale = requestedLocale;
        } catch (error) {
            locale = 'en';
        }

        const dateFormatter = new Intl.DateTimeFormat(locale, {
            weekday: 'short', month: 'short', day: 'numeric'
        });
        const fullDateFormatter = new Intl.DateTimeFormat(locale, {
            weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'
        });
        const timeFormatter = new Intl.DateTimeFormat(locale, {
            hour: 'numeric', minute: '2-digit', hour12: true
        });

        function renderGuestHeaderClock() {
            const now = new Date();
            const dateText = dateFormatter.format(now);
            const fullDateText = fullDateFormatter.format(now);
            const timeText = timeFormatter.format(now);
            dateElement.textContent = dateText;
            timeElement.textContent = timeText;
            clock.dateTime = now.toISOString();
            clock.setAttribute('aria-label', `Current date and time: ${fullDateText}, ${timeText}`);
            clock.title = `${fullDateText} at ${timeText}`;
        }

        renderGuestHeaderClock();
        const millisecondsUntilNextMinute = 60000 - (Date.now() % 60000) + 50;
        window.setTimeout(function () {
            renderGuestHeaderClock();
            window.setInterval(renderGuestHeaderClock, 60000);
        }, millisecondsUntilNextMinute);
    }

    function setupNavbar() {
        const navbar = document.getElementById('nbar');
        if (!navbar) return;
        let frame = null;
        function syncNavbar() {
            frame = null;
            navbar.classList.toggle('scr', window.scrollY > 40);
        }
        syncNavbar();
        window.addEventListener('scroll', function () {
            if (frame !== null) return;
            frame = window.requestAnimationFrame(syncNavbar);
        }, { passive: true });
    }

    onReady(function () {
        applyTheme();
        setupGuestHeaderClock();
        setupNavbar();
        document.getElementById('thbtn')?.addEventListener('click', toggleTheme);
    });

    const guestSectionIds = ['hero', 'features', 'how', 'benefits', 'developers', 'faq', 'contact'];
    const guestSections = guestSectionIds.map(function (id) { return document.getElementById(id); }).filter(Boolean);
    const guestNavigationLinks = Array.from(document.querySelectorAll([
        '#nbar .nav-link[href^="#"]',
        '#userCommandPalette .ucp-result[href^="#"]'
    ].join(',')));
    let guestNavigationFrame = 0;

    function normalizeGuestHash(hash) {
        return hash === '' || hash === '#' ? '#hero' : hash;
    }

    function setGuestActiveNavigation(sectionId) {
        if (!sectionId || !guestNavigationLinks.length) return;
        const activeHash = `#${sectionId}`;
        guestNavigationLinks.forEach(function (link) {
            const isActive = normalizeGuestHash(link.getAttribute('href') || '') === activeHash;
            link.classList.toggle('active', isActive);
            link.classList.toggle('is-active', isActive);
            if (isActive) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
    }

    function getGuestCurrentSectionId() {
        if (!guestSections.length) return null;
        const navOffset = (document.getElementById('nbar')?.offsetHeight || 0) + 24;
        const checkpoint = navOffset + Math.min(window.innerHeight * 0.22, 180);
        let current = guestSections[0];
        guestSections.forEach(function (section) {
            if (section.getBoundingClientRect().top <= checkpoint) current = section;
        });
        return current.id;
    }

    function syncGuestActiveNavigation() {
        if (guestNavigationFrame) return;
        guestNavigationFrame = window.requestAnimationFrame(function () {
            guestNavigationFrame = 0;
            setGuestActiveNavigation(getGuestCurrentSectionId());
        });
    }

    if (guestSections.length && guestNavigationLinks.length) {
        syncGuestActiveNavigation();
        window.addEventListener('scroll', syncGuestActiveNavigation, { passive: true });
        window.addEventListener('resize', syncGuestActiveNavigation, { passive: true });
        window.addEventListener('hashchange', syncGuestActiveNavigation);
        guestNavigationLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                const sectionId = normalizeGuestHash(link.getAttribute('href') || '').slice(1);
                if (guestSectionIds.includes(sectionId)) setGuestActiveNavigation(sectionId);
            });
        });
    }

    const revealElements = document.querySelectorAll('.rv');
    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('in');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        revealElements.forEach(function (element) { revealObserver.observe(element); });
    } else {
        revealElements.forEach(function (element) { element.classList.add('in'); });
    }

    function getFocusableElements(container) {
        if (!container) return [];
        return Array.from(container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter(function (element) {
            return !element.hidden && element.getAttribute('aria-hidden') !== 'true' && element.getClientRects().length > 0;
        });
    }

    function trapFocus(event, container) {
        if (event.key !== 'Tab') return;
        const focusable = getFocusableElements(container);
        if (!focusable.length) {
            event.preventDefault();
            container.focus({ preventScroll: true });
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus({ preventScroll: true });
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus({ preventScroll: true });
        }
    }

    function setupGuestCommandPalette() {
        const palette = document.getElementById('userCommandPalette');
        if (!palette || palette.dataset.ucpInitialized === 'true') return;
        palette.dataset.ucpInitialized = 'true';
        const dialog = palette.querySelector('.ucp-dialog');
        const results = Array.from(palette.querySelectorAll('[data-ucp-item]'));
        const openTriggers = Array.from(document.querySelectorAll('[data-ucp-open]'));
        let lastFocusedElement = null;
        let previousBodyOverflow = '';
        let activeItem = null;
        let paletteOpen = false;

        function setTriggerExpanded(expanded) {
            openTriggers.forEach(function (trigger) {
                trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        }

        function setActive(item, shouldScroll, shouldFocus) {
            results.forEach(function (result) {
                const selected = result === item;
                result.classList.toggle('is-active', selected);
                if (selected) result.setAttribute('aria-current', 'location');
                else result.removeAttribute('aria-current');
            });
            activeItem = item || null;
            if (activeItem && shouldScroll) activeItem.scrollIntoView({ block: 'nearest' });
            if (activeItem && shouldFocus) activeItem.focus({ preventScroll: true });
        }

        function currentResult() {
            if (window.location.hash) {
                const hashMatch = results.find(function (item) {
                    return item.getAttribute('href') === window.location.hash;
                });
                if (hashMatch) return hashMatch;
            }
            return results.find(function (item) { return item.classList.contains('active'); }) || results[0] || null;
        }

        function openPalette(trigger) {
            if (paletteOpen) {
                (activeItem || results[0] || dialog).focus({ preventScroll: true });
                return;
            }
            lastFocusedElement = trigger instanceof HTMLElement ? trigger : document.activeElement;
            previousBodyOverflow = document.body.style.overflow;
            paletteOpen = true;
            palette.hidden = false;
            palette.setAttribute('aria-hidden', 'false');
            setTriggerExpanded(true);
            document.body.style.overflow = 'hidden';
            setActive(currentResult(), false, false);
            window.requestAnimationFrame(function () {
                (activeItem || dialog).focus({ preventScroll: true });
            });
        }

        function closePalette(options) {
            if (!paletteOpen) return;
            const restoreFocus = !options || options.restoreFocus !== false;
            paletteOpen = false;
            palette.hidden = true;
            palette.setAttribute('aria-hidden', 'true');
            setTriggerExpanded(false);
            document.body.style.overflow = previousBodyOverflow;
            if (restoreFocus && lastFocusedElement?.isConnected && typeof lastFocusedElement.focus === 'function') {
                window.requestAnimationFrame(function () {
                    lastFocusedElement.focus({ preventScroll: true });
                });
            }
        }

        function moveActive(direction) {
            if (!results.length) return;
            let index = results.indexOf(activeItem);
            if (index === -1) index = direction > 0 ? -1 : 0;
            index = (index + direction + results.length) % results.length;
            setActive(results[index], true, true);
        }

        function fixedHeaderOffset() {
            const header = document.getElementById('nbar');
            if (!header || !header.getClientRects().length) return 0;
            const styles = window.getComputedStyle(header);
            if (styles.display === 'none' || (styles.position !== 'fixed' && styles.position !== 'sticky')) return 0;
            const rect = header.getBoundingClientRect();
            return rect.bottom > 0 ? rect.bottom : rect.height;
        }

        function scrollToHashDestination(destination) {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            const targetTop = destination.getBoundingClientRect().top + currentScroll - fixedHeaderOffset() - 14;
            const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: Math.max(0, Math.round(targetTop)), left: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
        }

        setTriggerExpanded(false);
        openTriggers.forEach(function (trigger) {
            trigger.addEventListener('click', function () { openPalette(trigger); });
        });
        palette.querySelectorAll('[data-ucp-close]').forEach(function (button) {
            button.addEventListener('click', function () { closePalette(); });
        });
        palette.addEventListener('mousedown', function (event) {
            if (event.target === palette) closePalette();
        });

        results.forEach(function (item) {
            item.addEventListener('mousemove', function () { setActive(item, false, false); });
            item.addEventListener('focus', function () { setActive(item, false, false); });
            item.addEventListener('click', function (event) {
                const href = item.getAttribute('href') || '';
                const destination = href.startsWith('#') && href.length > 1 ? document.getElementById(href.slice(1)) : null;
                closePalette({ restoreFocus: false });
                if (!destination) return;
                event.preventDefault();
                const hadTabIndex = destination.hasAttribute('tabindex');
                if (!hadTabIndex) destination.setAttribute('tabindex', '-1');
                window.requestAnimationFrame(function () {
                    scrollToHashDestination(destination);
                    destination.focus({ preventScroll: true });
                    window.history?.pushState(window.history.state, document.title, href);
                    setGuestActiveNavigation(href.slice(1));
                    if (!hadTabIndex) {
                        destination.addEventListener('blur', function () { destination.removeAttribute('tabindex'); }, { once: true });
                    }
                });
            });
        });

        palette.querySelectorAll('[data-ucp-action]').forEach(function (action) {
            action.addEventListener('click', function () {
                closePalette({ restoreFocus: false });
            });
        });

        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && !event.altKey && event.key.toLocaleLowerCase() === 'k') {
                event.preventDefault();
                if (paletteOpen) closePalette();
                else openPalette();
                return;
            }
            if (!paletteOpen) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closePalette();
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                moveActive(1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveActive(-1);
            } else {
                trapFocus(event, dialog);
            }
        });
    }

    onReady(setupGuestCommandPalette);
})();
