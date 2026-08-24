/*  STATE  */
let isDark = (window.SpeakReadyTheme ? window.SpeakReadyTheme.get() : localStorage.getItem('theme')) !== 'light',
    currentUser = null;
let chatHistory = [];
let ovChartInst = null,
    anChartInst = null;

/*  MOBILE VIEWPORT  */
(function setupSpeakReadyViewportMetrics() {
    const root = document.documentElement;
    let frame = null;

    function viewportSize() {
        const visualViewport = window.visualViewport;
        const width = visualViewport && visualViewport.width ? visualViewport.width : window.innerWidth;
        const height = visualViewport && visualViewport.height ? visualViewport.height : window.innerHeight;

        return {
            width: Math.max(0, width || 0),
            height: Math.max(0, height || 0)
        };
    }

    function applyViewportMetrics() {
        frame = null;
        const size = viewportSize();

        if (size.height > 0) {
            root.style.setProperty('--sr-visual-vh', `${size.height.toFixed(2)}px`);
        }

        if (size.width > 0) {
            root.style.setProperty('--sr-layout-vw', `${size.width.toFixed(2)}px`);
        }

        document.body?.classList.toggle('sr-browser-fullscreen', Boolean(document.fullscreenElement));
    }

    function queueViewportMetricsRefresh() {
        if (frame !== null) {
            window.cancelAnimationFrame(frame);
        }

        frame = window.requestAnimationFrame(applyViewportMetrics);
    }

    applyViewportMetrics();
    window.SpeakReadyViewport = {
        refresh: queueViewportMetricsRefresh,
        refreshNow: applyViewportMetrics
    };

    window.addEventListener('resize', queueViewportMetricsRefresh, { passive: true });
    window.addEventListener('orientationchange', () => {
        queueViewportMetricsRefresh();
        window.setTimeout(queueViewportMetricsRefresh, 120);
        window.setTimeout(queueViewportMetricsRefresh, 320);
    }, { passive: true });
    document.addEventListener('fullscreenchange', queueViewportMetricsRefresh);
    document.addEventListener('visibilitychange', queueViewportMetricsRefresh);

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', queueViewportMetricsRefresh, { passive: true });
        window.visualViewport.addEventListener('scroll', queueViewportMetricsRefresh, { passive: true });
    }
})();

/*  THEME  */
function applyTheme() {
    const appliedTheme = window.SpeakReadyTheme
        ? window.SpeakReadyTheme.apply(isDark ? 'dark' : 'light', false)
        : (isDark ? 'dark' : 'light');
    isDark = appliedTheme !== 'light';
    
    // Landing icons
    const si = document.getElementById('suni'),
        mi = document.getElementById('mooni');
    if (si && mi) {
        si.style.display = isDark ? 'none' : 'inline';
        mi.style.display = isDark ? 'inline' : 'none';
    }
    
    // Dashboard icons
    const dsi = document.getElementById('dbSunI'),
        dmi = document.getElementById('dbMoonI');
    if (dsi && dmi) {
        dsi.style.display = isDark ? 'none' : 'inline';
        dmi.style.display = isDark ? 'inline' : 'none';
    }
    
    // Sync settings toggle
    const dmtog = document.getElementById('darkModeToggle');
    if (dmtog) dmtog.checked = isDark;
    
    // Update charts
    if (typeof updateChartColors === 'function') {
        updateChartColors();
    }
}

function toggleTheme() {
    isDark = !isDark;
    if (window.SpeakReadyTheme) {
        isDark = window.SpeakReadyTheme.toggle() !== 'light';
    } else {
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        applyTheme();
    }
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
        if (Intl.DateTimeFormat.supportedLocalesOf([requestedLocale]).length) {
            locale = requestedLocale;
        }
    } catch (error) {
        locale = 'en';
    }

    const dateFormatter = new Intl.DateTimeFormat(locale, {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });
    const fullDateFormatter = new Intl.DateTimeFormat(locale, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
    const timeFormatter = new Intl.DateTimeFormat(locale, {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });

    function renderGuestHeaderClock() {
        const now = new Date();
        const dateText = dateFormatter.format(now);
        const fullDateText = fullDateFormatter.format(now);
        const timeText = timeFormatter.format(now);

        dateElement.textContent = dateText;
        timeElement.textContent = timeText;
        clock.dateTime = now.toISOString();
        clock.setAttribute('aria-label', 'Current date and time: ' + fullDateText + ', ' + timeText);
        clock.title = fullDateText + ' at ' + timeText;
    }

    renderGuestHeaderClock();

    const millisecondsUntilNextMinute = 60000 - (Date.now() % 60000) + 50;
    window.setTimeout(function () {
        renderGuestHeaderClock();
        window.setInterval(renderGuestHeaderClock, 60000);
    }, millisecondsUntilNextMinute);
}

document.addEventListener('DOMContentLoaded', () => {
    applyTheme();
    setupGuestHeaderClock();
    const thbtn = document.getElementById('thbtn');
    if (thbtn) thbtn.addEventListener('click', toggleTheme);
});

/*  NAVBAR  */
window.addEventListener('scroll', () => {
    const navbar = document.getElementById('nbar');
    if (navbar) navbar.classList.toggle('scr', scrollY > 40);
});
let mbOpen = false;
const mobileToggle = document.getElementById('mbtog');
const guestQuickNavToggle = document.getElementById('guestQuickNavLauncher');
const mobileMenu = document.getElementById('mbmenu');
const barIcon = document.getElementById('barIcon');
const xIcon = document.getElementById('xIcon');
const mobileMenuToggles = [mobileToggle, guestQuickNavToggle].filter(Boolean);
let lastMobileMenuTrigger = null;
let restoreMobileMenuFocusAfterAuth = false;

function setMobileMenuState(open, focusFirstLink = false) {
    if (!mobileMenu) return;

    mbOpen = open;
    mobileMenu.classList.toggle('open', open);
    mobileMenu.setAttribute('aria-hidden', open ? 'false' : 'true');
    mobileMenuToggles.forEach(toggle => toggle.setAttribute('aria-expanded', open ? 'true' : 'false'));
    if (guestQuickNavToggle) {
        guestQuickNavToggle.classList.toggle('is-open', open);
        guestQuickNavToggle.setAttribute('aria-label', open ? 'Close quick navigation' : 'Open quick navigation');
    }

    if (barIcon) barIcon.style.display = open ? 'none' : 'inline';
    if (xIcon) xIcon.style.display = open ? 'inline' : 'none';

    if (open && focusFirstLink) {
        window.requestAnimationFrame(() => {
            mobileMenu.querySelector('a, button')?.focus({ preventScroll: true });
        });
    }
}

function focusMobileMenuDestination(link) {
    const href = link.getAttribute('href') || '';
    const destination = href.length > 1 && href.startsWith('#')
        ? document.querySelector(href)
        : document.getElementById('hero');

    if (!destination) {
        lastMobileMenuTrigger?.focus({ preventScroll: true });
        return;
    }

    const hadTabIndex = destination.hasAttribute('tabindex');
    if (!hadTabIndex) destination.setAttribute('tabindex', '-1');

    window.requestAnimationFrame(() => {
        destination.focus({ preventScroll: true });

        if (!hadTabIndex) {
            destination.addEventListener('blur', () => destination.removeAttribute('tabindex'), { once: true });
        }
    });
}

if (mobileMenu && mobileMenuToggles.length) {
    mobileMenuToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            lastMobileMenuTrigger = toggle;
            setMobileMenuState(!mbOpen, !mbOpen);
        });
    });

    document.querySelectorAll('#mbmenu a, #mbmenu button').forEach(el =>
        el.addEventListener('click', () => {
            const opensAuthPanel = el.matches('[data-bs-target="#lofc"]');
            restoreMobileMenuFocusAfterAuth = opensAuthPanel;
            setMobileMenuState(false);

            if (el.matches('a[href^="#"]')) {
                focusMobileMenuDestination(el);
            }
        })
    );

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || !mbOpen) return;

        event.preventDefault();
        setMobileMenuState(false);
        lastMobileMenuTrigger?.focus({ preventScroll: true });
    });

    setMobileMenuState(false);
}

const desktopMenuBreakpoint = window.matchMedia('(min-width: 992px)');
const closeMobileMenuAtDesktop = event => {
    if (!event.matches || !mbOpen) return;

    const focusedMenuItem = mobileMenu?.contains(document.activeElement) ? document.activeElement : null;
    const focusedHref = focusedMenuItem?.getAttribute?.('href');
    setMobileMenuState(false);

    if (focusedMenuItem) {
        window.requestAnimationFrame(() => {
            const desktopLinks = Array.from(document.querySelectorAll('#nbar .d-lg-flex .nav-link'));
            const focusTarget = desktopLinks.find(link => link.getAttribute('href') === focusedHref)
                || document.querySelector('#nbar a');
            focusTarget?.focus({ preventScroll: true });
        });
    }
};

if (typeof desktopMenuBreakpoint.addEventListener === 'function') {
    desktopMenuBreakpoint.addEventListener('change', closeMobileMenuAtDesktop);
} else if (typeof desktopMenuBreakpoint.addListener === 'function') {
    desktopMenuBreakpoint.addListener(closeMobileMenuAtDesktop);
}

const guestAuthPanel = document.getElementById('lofc');
guestAuthPanel?.addEventListener('hidden.bs.offcanvas', () => {
    if (!restoreMobileMenuFocusAfterAuth) return;

    restoreMobileMenuFocusAfterAuth = false;
    window.requestAnimationFrame(() => {
        lastMobileMenuTrigger?.focus({ preventScroll: true });
    });
});

const guestSectionIds = ['hero', 'features', 'how', 'benefits', 'developers', 'faq', 'contact'];
const guestSections = guestSectionIds
    .map(id => document.getElementById(id))
    .filter(Boolean);
const guestNavigationLinks = Array.from(document.querySelectorAll([
    '#nbar .nav-link[href^="#"]',
    '#mbmenu a[href^="#"]',
    '#userCommandPalette .ucp-result[href^="#"]'
].join(',')));

function normalizeGuestHash(hash) {
    return hash === '' || hash === '#' ? '#hero' : hash;
}

function setGuestActiveNavigation(sectionId) {
    if (!sectionId || !guestNavigationLinks.length) return;

    const activeHash = `#${sectionId}`;

    guestNavigationLinks.forEach(link => {
        const linkHash = normalizeGuestHash(link.getAttribute('href') || '');
        const isActive = linkHash === activeHash;

        link.classList.toggle('active', isActive);
        link.classList.toggle('is-active', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
        }
    });
}

function getGuestCurrentSectionId() {
    if (!guestSections.length) return null;

    const navOffset = (document.getElementById('nbar')?.offsetHeight || 0) + 24;
    const checkpoint = navOffset + Math.min(window.innerHeight * 0.22, 180);
    let current = guestSections[0];

    guestSections.forEach(section => {
        if (section.getBoundingClientRect().top <= checkpoint) {
            current = section;
        }
    });

    return current.id;
}

let guestNavRaf = 0;
function syncGuestActiveNavigation() {
    if (guestNavRaf) return;

    guestNavRaf = window.requestAnimationFrame(() => {
        guestNavRaf = 0;
        setGuestActiveNavigation(getGuestCurrentSectionId());
    });
}

if (guestSections.length && guestNavigationLinks.length) {
    syncGuestActiveNavigation();
    window.addEventListener('scroll', syncGuestActiveNavigation, { passive: true });
    window.addEventListener('resize', syncGuestActiveNavigation);
    window.addEventListener('hashchange', syncGuestActiveNavigation);

    guestNavigationLinks.forEach(link => {
        link.addEventListener('click', () => {
            const href = normalizeGuestHash(link.getAttribute('href') || '');
            const sectionId = href.slice(1);

            if (guestSectionIds.includes(sectionId)) {
                setGuestActiveNavigation(sectionId);
                window.setTimeout(syncGuestActiveNavigation, 400);
            }
        });
    });
}

/*  REVEAL  */
const rvObs = new IntersectionObserver(
    entries => entries.forEach(e => {
        if (e.isIntersecting) e.target.classList.add('in');
    }), {
        threshold: 0.1,
        rootMargin: '0px 0px -40px 0px'
    }
);
document.querySelectorAll('.rv').forEach(el => rvObs.observe(el));

/*  VIDEO POPUP  */
$('.vidpop').magnificPopup({
    type: 'iframe',
    iframe: {
        patterns: {
            youtube: {
                index: 'youtube.com/',
                id: 'v=',
                src: 'https://www.youtube.com/embed/%id%?autoplay=1&rel=0'
            }
        }
    },
    mainClass: 'mfp-fade',
    removalDelay: 160
});

/*  PRICING TOGGLE  */
const ptog = document.getElementById('ptog');
if (ptog) {
    ptog.addEventListener('change', function() {
        const y = this.checked;
        document.getElementById('ptogThumb').style.transform = y ? 'translateX(24px)' : 'translateX(0)';
        document.querySelectorAll('.pv').forEach(el => el.textContent = y ? el.dataset.y : el.dataset.m);
        document.querySelectorAll('.pper').forEach((el, i) => {
            if (i < 2) el.textContent = y ? 'per month, billed yearly' : 'per month, billed monthly';
        });
    });
}

/*  AUTH FUNCTIONS  */
function swTab(t) {
    const isL = t === 'login' || t === 'signin';
    const loginPanel = document.getElementById('fLogin');
    const signupPanel = document.getElementById('fSignup');
    const loginTab = document.getElementById('tabLogin');
    const signupTab = document.getElementById('tabSignup');
    const loginErr = document.getElementById('loginErr');
    const signupErr = document.getElementById('signupErr');

    const activePanel = isL ? loginPanel : signupPanel;
    const inactivePanel = isL ? signupPanel : loginPanel;

    if (activePanel && inactivePanel && inactivePanel.style.display !== 'none') {
        inactivePanel.classList.add('is-switching-out');
        window.setTimeout(function () {
            inactivePanel.style.display = 'none';
            inactivePanel.classList.remove('is-switching-out');
            activePanel.style.display = 'block';
        }, 150);
    } else {
        if (loginPanel) loginPanel.style.display = isL ? 'block' : 'none';
        if (signupPanel) signupPanel.style.display = isL ? 'none' : 'block';
    }

    loginTab?.classList.toggle('on', isL);
    signupTab?.classList.toggle('on', !isL);
    if (loginErr) loginErr.style.display = 'none';
    if (signupErr) signupErr.style.display = 'none';
}

function showErrLogin(msg) {
    const el = document.getElementById('loginErr');
    document.getElementById('loginErrMsg').textContent = msg;
    el.style.display = 'block';
}

function showErrSignup(msg) {
    const el = document.getElementById('signupErr');
    document.getElementById('signupErrMsg').textContent = msg;
    el.style.display = 'block';
}

function setLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    if (loading) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Please wait...';
    } else {
        btn.disabled = false;
        if (btnId === 'loginBtn') btn.innerHTML = 'Log In <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i>';
        else btn.innerHTML = 'Create Free Account <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i>';
    }
}

function doLogin() {
    const email = document.getElementById('loginEmail').value.trim();
    const pass = document.getElementById('loginPass').value;
    document.getElementById('loginErr').style.display = 'none';
    if (!email) return showErrLogin('Please enter your email address.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showErrLogin('Please enter a valid email address.');
    if (!pass) return showErrLogin('Please enter your password.');
    if (pass.length < 6) return showErrLogin('Password must be at least 6 characters.');
    setLoading('loginBtn', true);
    setTimeout(() => {
        setLoading('loginBtn', false);
        const name = email.split('@')[0].replace(/[._]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        loginSuccess({
            name,
            email,
            plan: 'Pro Plan'
        });
    }, 900);
}

function doSignup() {
    const name = document.getElementById('signupName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();
    const pass = document.getElementById('signupPass').value;
    document.getElementById('signupErr').style.display = 'none';
    if (!name) return showErrSignup('Please enter your full name.');
    if (!email) return showErrSignup('Please enter your email address.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showErrSignup('Please enter a valid email address.');
    if (pass.length < 8) return showErrSignup('Password must be at least 8 characters.');
    setLoading('signupBtn', true);
    setTimeout(() => {
        setLoading('signupBtn', false);
        loginSuccess({
            name,
            email,
            plan: 'Starter (Trial)'
        });
    }, 1000);
}

function quickLogin(provider) {
    const names = {
        google: 'Alex Johnson',
        github: 'Dev User'
    };
    const emails = {
        google: 'user@gmail.com',
        github: 'user@github.com'
    };
    // close offcanvas
    bootstrap.Offcanvas.getInstance(document.getElementById('lofc'))?.hide();
    setTimeout(() => loginSuccess({
        name: names[provider],
        email: emails[provider],
        plan: 'Pro Plan'
    }), 300);
}

function loginSuccess(user) {
    currentUser = user;
    chatHistory = [];
    const oc = bootstrap.Offcanvas.getInstance(document.getElementById('lofc'));
    if (oc) oc.hide();
    const initials = user.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
    // topbar
    document.getElementById('userAvatar').textContent = initials;
    document.getElementById('userName').textContent = user.name;
    document.getElementById('userPlan').textContent = user.plan;
    // profile dropdown
    document.getElementById('pdAvatar').textContent = initials;
    document.getElementById('pdName').textContent = user.name;
    document.getElementById('pdEmail').textContent = user.email;
    document.getElementById('pdPlan').textContent = user.plan;
    document.getElementById('pdPlanDetail').textContent = user.plan;
    // greeting + settings
    document.getElementById('greetName').textContent = user.name.split(' ')[0];
    document.getElementById('settingsAvatar').textContent = initials;
    document.getElementById('settingsName').textContent = user.name;
    document.getElementById('settingsEmail').textContent = user.email;
    document.getElementById('profileName').value = user.name;
    document.getElementById('profileEmail').value = user.email;
    // switch view
    document.getElementById('landing').style.display = 'none';
    document.getElementById('dashboard').style.display = 'block';
    window.scrollTo(0, 0);
    setTimeout(() => {
        initOverviewChart();
    }, 200);
}

function doLogout() {
    currentUser = null;
    chatHistory = [];
    document.getElementById('dashboard').style.display = 'none';
    document.getElementById('landing').style.display = 'block';
    window.scrollTo(0, 0);
    // reset chat UI
    document.getElementById('chatBody').innerHTML = `
    <div class="d-flex flex-column gap-1">
      <div class="msg msg-ai">ðŸ‘‹ Hi! I'm your SpeakReady AI assistant. I can help you with support analytics, agent configuration, automation workflows, and business insights. What would you like to know?</div>
      <div class="msg-time" style="align-self:flex-start;padding-left:4px">SpeakReady AI Â· Just now</div>
    </div>`;
}

/*  DASHBOARD NAVIGATION  */
function dbNav(section, btn) {
    document.querySelectorAll('.db-nl').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.db-section').forEach(s => s.classList.remove('active'));
    if (btn) btn.classList.add('active');
    // find the correct sidebar button if btn not provided
    if (!btn) {
        document.querySelectorAll('.db-nl').forEach(b => {
            if (b.getAttribute('onclick') && b.getAttribute('onclick').includes("'" + section + "'")) b.classList.add('active');
        });
    }
    const sec = document.getElementById('sec-' + section);
    if (sec) {
        sec.classList.add('active');
        sec.style.animation = 'fadeIn .4s ease';
    }
    // close mobile sidebar + dropdowns
    document.getElementById('dbSidebar')?.classList.remove('mob-open');
    document.getElementById('notifDropdown')?.classList.remove('open');
    document.getElementById('profileDropdown')?.classList.remove('open');
    const ch = document.getElementById('profileChevron');
    if (ch) ch.style.transform = 'rotate(0deg)';
    // init charts when relevant section shown
    if (section === 'analytics') setTimeout(initAnalyticsChart, 100);
    if (section === 'overview') setTimeout(initOverviewChart, 100);
}

function chartColors() {
    return {
        grid: isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)',
        ticks: isDark ? '#6b6b8a' : '#7878a0'
    };
}

function initOverviewChart() {
    const ctx = document.getElementById('ovChart');
    if (!ctx) return;
    if (ovChartInst) {
        ovChartInst.destroy();
        ovChartInst = null;
    }
    const c = ctx.getContext('2d');
    const g = c.createLinearGradient(0, 0, 0, 280);
    g.addColorStop(0, 'rgba(59,130,246,0.35)');
    g.addColorStop(1, 'rgba(59,130,246,0.02)');
    let labels = typeof window.dashboardChartLabels !== 'undefined' && window.dashboardChartLabels.length > 0 ? window.dashboardChartLabels : Array.from({
        length: 30
    }, (_, i) => `${i+1}`);
    let data = typeof window.dashboardChartData !== 'undefined' && window.dashboardChartData.length > 0 ? window.dashboardChartData : [420, 480, 510, 440, 600, 580, 720, 690, 750, 810, 780, 860, 820, 900, 940, 880, 960, 1020, 1100, 1080, 1150, 1200, 1180, 1260, 1310, 1280, 1350, 1400, 1460, 1520];
    const {
        grid,
        ticks
    } = chartColors();
    ovChartInst = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Readiness Score',
                data,
                fill: true,
                backgroundColor: g,
                borderColor: '#3b82f6',
                borderWidth: 2.5,
                pointRadius: 0,
                pointHoverRadius: 5,
                tension: .42
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(22,22,42,.95)',
                    titleColor: '#60a5fa',
                    bodyColor: '#a8a8c8',
                    borderColor: 'rgba(59,130,246,.3)',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: c => ' ' + c.parsed.y + '% Readiness'
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: grid
                    },
                    ticks: {
                        color: ticks,
                        font: {
                            family: 'Space Grotesk',
                            size: 11
                        },
                        maxTicksLimit: 10
                    }
                },
                y: {
                    grid: {
                        color: grid
                    },
                    ticks: {
                        color: ticks,
                        font: {
                            family: 'Space Grotesk',
                            size: 11
                        },
                        callback: v => v + '%'
                    }
                }
            }
        }
    });
}

function initAnalyticsChart() {
    const ctx = document.getElementById('anChart');
    if (!ctx) return;
    if (anChartInst) {
        anChartInst.destroy();
        anChartInst = null;
    }
    const c = ctx.getContext('2d');
    const g1 = c.createLinearGradient(0, 0, 0, 250);
    g1.addColorStop(0, 'rgba(59,130,246,0.3)');
    g1.addColorStop(1, 'rgba(59,130,246,0.01)');
    const g2 = c.createLinearGradient(0, 0, 0, 250);
    g2.addColorStop(0, 'rgba(52,211,153,0.2)');
    g2.addColorStop(1, 'rgba(52,211,153,0.01)');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const {
        grid,
        ticks
    } = chartColors();
    anChartInst = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                    label: 'Conversations',
                    data: [8200, 9100, 10400, 9800, 11200, 12800, 14100, 15600, 17200, 19000, 21400, 24800],
                    fill: true,
                    backgroundColor: g1,
                    borderColor: '#3b82f6',
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointBackgroundColor: '#3b82f6',
                    tension: .4
                },
                {
                    label: 'Resolved by AI',
                    data: [6560, 7644, 8736, 8330, 9632, 11264, 12408, 13728, 15136, 16720, 18834, 22016],
                    fill: true,
                    backgroundColor: g2,
                    borderColor: '#34d399',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#34d399',
                    tension: .4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: isDark ? '#a8a8c8' : '#3d3d5c',
                        font: {
                            family: 'Space Grotesk',
                            size: 12
                        },
                        boxWidth: 12,
                        borderRadius: 4
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(22,22,42,.95)',
                    titleColor: '#60a5fa',
                    bodyColor: '#a8a8c8',
                    borderColor: 'rgba(59,130,246,.3)',
                    borderWidth: 1,
                    padding: 10
                }
            },
            scales: {
                x: {
                    grid: {
                        color: grid
                    },
                    ticks: {
                        color: ticks,
                        font: {
                            family: 'Space Grotesk',
                            size: 11
                        }
                    }
                },
                y: {
                    grid: {
                        color: grid
                    },
                    ticks: {
                        color: ticks,
                        font: {
                            family: 'Space Grotesk',
                            size: 11
                        },
                        callback: v => v >= 1000 ? (v / 1000).toFixed(1) + 'K' : v
                    }
                }
            }
        }
    });
}

function updateChartColors() {
    [ovChartInst, anChartInst].forEach(ch => {
        if (!ch) return;
        const {
            grid,
            ticks
        } = chartColors();
        ch.options.scales.x.grid.color = grid;
        ch.options.scales.x.ticks.color = ticks;
        ch.options.scales.y.grid.color = grid;
        ch.options.scales.y.ticks.color = ticks;
        if (ch.options.plugins.legend) ch.options.plugins.legend.labels.color = isDark ? '#a8a8c8' : '#3d3d5c';
        ch.update();
    });
}

/*  AI CHAT (Anthropic API)  */
async function sendChat() {
    const inp = document.getElementById('chatInp');
    const msg = inp.value.trim();
    if (!msg) return;
    inp.value = '';
    inp.style.height = 'auto';
    appendMsg(msg, 'user');
    chatHistory.push({
        role: 'user',
        content: msg
    });
    document.getElementById('chatSendBtn').disabled = true;
    const typingId = appendTyping();
    try {
        const res = await fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                model: 'claude-sonnet-4-20250514',
                max_tokens: 1000,
                system: `You are SpeakReady AI, an intelligent AI assistant built into the SpeakReady AI business automation platform. The user is ${currentUser?.name || 'a user'} on the ${currentUser?.plan || 'Pro'} plan. You help with: AI agent performance, support ticket analytics, workflow automation suggestions, business metrics insights, and platform usage. Current platform stats: 24.8K conversations today, 98.2% resolution rate, 1.4s avg response, $18.2K monthly savings, 4 active agents. Be concise, professional, and data-driven. Use emojis sparingly.`,
                messages: chatHistory
            })
        });
        removeTyping(typingId);
        if (res.ok) {
            const data = await res.json();
            const reply = data.content?.find(b => b.type === 'text')?.text || 'I could not generate a response.';
            chatHistory.push({
                role: 'assistant',
                content: reply
            });
            appendMsg(reply, 'ai');
        } else {
            appendMsg('âš ï¸ Sorry, I had trouble connecting. Please check your API key or try again.', 'ai');
        }
    } catch (e) {
        removeTyping(typingId);
        appendMsg('âš ï¸ Network error. Please ensure you are connected to the internet.', 'ai');
    }
    document.getElementById('chatSendBtn').disabled = false;
}

function appendMsg(text, role) {
    const body = document.getElementById('chatBody');
    const time = new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
    const wrap = document.createElement('div');
    wrap.className = 'd-flex flex-column gap-1';
    wrap.innerHTML = `
    <div class="msg msg-${role}" style="animation:fadeIn .3s ease">${escapeHtml(text).replace(/\n/g,'<br>')}</div>
    <div class="msg-time" style="align-self:${role==='ai'?'flex-start':'flex-end'};padding:0 4px">${role==='ai'?'SpeakReady AI':'You'} Â· ${time}</div>`;
    body.appendChild(wrap);
    body.scrollTop = body.scrollHeight;
}

let typingCounter = 0;

function appendTyping() {
    const id = 'typ-' + (++typingCounter);
    const body = document.getElementById('chatBody');
    const el = document.createElement('div');
    el.id = id;
    el.className = 'typing-ind';
    el.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
    body.appendChild(el);
    body.scrollTop = body.scrollHeight;
    return id;
}

function removeTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

function clearChat() {
    chatHistory = [];
    document.getElementById('chatBody').innerHTML = `
    <div class="d-flex flex-column gap-1">
      <div class="msg msg-ai">ðŸ‘‹ Hi! I'm your SpeakReady AI assistant. I can help you with support analytics, agent configuration, automation workflows, and business insights. What would you like to know?</div>
      <div class="msg-time" style="align-self:flex-start;padding-left:4px">SpeakReady AI Â· Just now</div>
    </div>`;
}

function quickMsg(msg) {
    document.getElementById('chatInp').value = msg;
    sendChat();
}

function escapeHtml(t) {
    return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/*  AUTO RESIZE TEXTAREA  */
document.getElementById('chatInp')?.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});

/*  NOTIFICATION DROPDOWN  */
function toggleNotif(e) {
    if (e) e.stopPropagation();
    const nd = document.getElementById('notifDropdown');
    const pd = document.getElementById('profileDropdown');
    if (pd) pd.classList.remove('open');
    const ch = document.getElementById('profileChevron');
    if (ch) ch.style.transform = 'rotate(0deg)';
    if (nd) nd.classList.toggle('open');
}

function markAllRead() {
    document.querySelectorAll('.notif-dot:not(.read)').forEach(d => d.classList.add('read'));
    document.querySelectorAll('.notif-unread').forEach(n => n.classList.remove('notif-unread'));
    const unreadCount = document.getElementById('unreadCount');
    if (unreadCount) {
        unreadCount.textContent = '0 new';
        unreadCount.style.background = 'rgba(59,130,246,.1)';
        unreadCount.style.color = '#60a5fa';
    }
    const notifBadge = document.getElementById('notifBadge');
    if (notifBadge) notifBadge.style.display = 'none';
}

/*  PROFILE DROPDOWN  */
function toggleProfile(e) {
    if (e) e.stopPropagation();
    const pd = document.getElementById('profileDropdown');
    const nd = document.getElementById('notifDropdown');
    if (nd) nd.classList.remove('open');
    if (pd) pd.classList.toggle('open');
    const ch = document.getElementById('profileChevron');
    if (ch && pd) {
        ch.style.transform = pd.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
    }
}

/* Close dropdowns on outside click */
document.addEventListener('click', (e) => {
    if (!document.getElementById('notifWrap')?.contains(e.target))
        document.getElementById('notifDropdown')?.classList.remove('open');
    if (!document.getElementById('profileWrap')?.contains(e.target)) {
        document.getElementById('profileDropdown')?.classList.remove('open');
        const ch = document.getElementById('profileChevron');
        if (ch) ch.style.transform = 'rotate(0deg)';
    }
});
