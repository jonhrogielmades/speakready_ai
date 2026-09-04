<script>
    (function() {
        if (window.createSpeakReadyTour) return;

        function isMobileTour(serverDetectedMobile) {
            return Boolean(serverDetectedMobile) ||
                Boolean(document.getElementById('mob-content')) ||
                window.matchMedia('(max-width: 767px)').matches;
        }

        function getStepElement(step) {
            if (!step || !step.element) return null;

            if (typeof step.element === 'string') {
                return document.querySelector(step.element);
            }

            if (step.element instanceof Element) {
                return step.element;
            }

            return null;
        }

        function isElementVisible(element) {
            if (!element) return false;

            const style = window.getComputedStyle(element);
            return style.display !== 'none' &&
                style.visibility !== 'hidden' &&
                element.getClientRects().length > 0;
        }

        function isVisibleStep(step) {
            if (!step.element) return true;

            return isElementVisible(getStepElement(step));
        }

        function normalizePageScope(value) {
            if (!value) return '';

            try {
                const url = new URL(String(value), window.location.origin);
                const pathname = url.pathname.replace(/\/+$/, '') || '/';
                return `${pathname}${url.search || ''}`;
            } catch (error) {
                const normalized = String(value).trim().replace(/\/+$/, '') || '/';
                return normalized.startsWith('/') ? normalized : `/${normalized}`;
            }
        }

        function getCurrentPageScope() {
            return normalizePageScope(`${window.location.pathname}${window.location.search}`);
        }

        function isCurrentTourScope(scope) {
            return !scope || normalizePageScope(scope) === getCurrentPageScope();
        }

        function getPageContentRoot() {
            return document.querySelector('[data-user-ajax-content]') ||
                document.getElementById('userAppContent') ||
                document.getElementById('mob-content') ||
                document.querySelector('.db-content');
        }

        function isInsidePageContent(element) {
            const root = getPageContentRoot();
            return !root || root === element || root.contains(element);
        }

        let currentTourHighlightedElement = null;

        function setTourHighlightedElement(element) {
            if (currentTourHighlightedElement && currentTourHighlightedElement !== element) {
                currentTourHighlightedElement.classList?.remove('sr-tour-highlighted');
            }

            currentTourHighlightedElement = element && element.isConnected ? element : null;
            currentTourHighlightedElement?.classList?.add('sr-tour-highlighted');
        }

        function clearTourHighlightedElement() {
            currentTourHighlightedElement?.classList?.remove('sr-tour-highlighted');
            currentTourHighlightedElement = null;
        }

        function makeRegistrationKey(scope, completionKey, isFallbackTour) {
            return [
                normalizePageScope(scope) || getCurrentPageScope(),
                completionKey || 'automatic',
                isFallbackTour ? 'fallback' : 'page',
            ].join('::');
        }

        function getRegisteredTour() {
            const registeredTour = window.__speakReadyRegisteredTour;

            if (!registeredTour || typeof registeredTour !== 'object') return null;
            if (!registeredTour.controller || !isCurrentTourScope(registeredTour.scope)) return null;

            return registeredTour;
        }

        function bumpTourRegistrationVersion() {
            window.__speakReadyTourRegistrationVersion = (window.__speakReadyTourRegistrationVersion || 0) + 1;

            return window.__speakReadyTourRegistrationVersion;
        }

        function destroyTourDriver(driverObj) {
            if (!driverObj || typeof driverObj.destroy !== 'function') return;

            try {
                window.__speakReadyForceDestroy = true;
                driverObj.destroy();
            } catch (error) {
                console.warn('Unable to destroy duplicate tutorial:', error);
            } finally {
                window.__speakReadyForceDestroy = false;
            }
        }

        function getPopoverClass() {
            const themeClass = document.documentElement.classList.contains('lm') ?
                'driverjs-theme-light' :
                'driverjs-theme-dark';

            return `sr-driver-popover ${themeClass}`;
        }

        function getViewportBuffers(config) {
            const mobile = isMobileTour(config.serverDetectedMobile);
            const mobileHeader = document.getElementById('mob-header');
            const mobileNav = document.getElementById('mob-bottom-nav');
            const measuredMobileTop = isElementVisible(mobileHeader) ?
                Math.ceil(mobileHeader.getBoundingClientRect().height + 10) :
                78;
            const measuredMobileBottom = isElementVisible(mobileNav) ?
                Math.ceil(mobileNav.getBoundingClientRect().height + 12) :
                96;

            return {
                top: mobile ? (config.mobileTopBuffer ?? measuredMobileTop) : (config.desktopTopBuffer ?? 24),
                bottom: mobile ? (config.mobileBottomBuffer ?? measuredMobileBottom) : (config.desktopBottomBuffer ?? 24),
            };
        }

        function getScrollParent(element) {
            let parent = element.parentElement;

            while (parent && parent !== document.body) {
                const style = window.getComputedStyle(parent);
                const overflowY = `${style.overflowY}${style.overflow}`;

                if (/(auto|scroll|overlay)/.test(overflowY) && parent.scrollHeight > parent.clientHeight) {
                    return parent;
                }

                parent = parent.parentElement;
            }

            return document.scrollingElement || document.documentElement;
        }

        function keepHighlightedElementInView(element, config) {
            if (!element) return;

            const rect = element.getBoundingClientRect();
            const { top: topBuffer, bottom: bottomBuffer } = getViewportBuffers(config);
            const usableHeight = Math.max(160, window.innerHeight - topBuffer - bottomBuffer);
            const isClipped = rect.top < topBuffer || rect.bottom > (window.innerHeight - bottomBuffer);
            const isTallerThanUsableArea = rect.height > usableHeight;

            if (!isClipped && !isTallerThanUsableArea) return;

            const scrollParent = getScrollParent(element);
            const currentScroll = scrollParent === document.scrollingElement ||
                scrollParent === document.documentElement ||
                scrollParent === document.body ?
                window.scrollY :
                scrollParent.scrollTop;
            const parentRect = scrollParent === document.scrollingElement ||
                scrollParent === document.documentElement ||
                scrollParent === document.body ?
                { top: 0 } :
                scrollParent.getBoundingClientRect();
            const targetTop = currentScroll + rect.top - parentRect.top - topBuffer - Math.max(12, (usableHeight - Math.min(rect.height, usableHeight)) / 2);

            if (scrollParent === document.scrollingElement ||
                scrollParent === document.documentElement ||
                scrollParent === document.body) {
                window.scrollTo({ top: Math.max(0, targetTop), behavior: 'auto' });
                return;
            }

            scrollParent.scrollTo({ top: Math.max(0, targetTop), behavior: 'auto' });
        }

        function refreshTourPlacement(driverObj) {
            window.setTimeout(() => {
                if (!driverObj || !driverObj.isActive || !driverObj.isActive()) return;

                if (typeof driverObj.refresh === 'function') {
                    driverObj.refresh();
                    return;
                }

                const activeIndex = typeof driverObj.getActiveIndex === 'function' ? driverObj.getActiveIndex() : null;
                if (Number.isInteger(activeIndex) && typeof driverObj.moveTo === 'function') {
                    driverObj.moveTo(activeIndex);
                }
            }, 40);
        }

        function getSteps(config) {
            const sourceSteps = isMobileTour(config.serverDetectedMobile) ? config.stepsMobile : config.stepsDesktop;
            return (sourceSteps || []).filter(isVisibleStep);
        }

        function escapeTourHtml(value) {
            return String(value || '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));
        }

        function getNativeTourFactory() {
            return function nativeDriverFactory(options) {
                const steps = options.steps || [];
                let active = false;
                let activeIndex = 0;
                let overlay = null;
                let stage = null;
                let popover = null;
                let previousFocus = null;
                let cleanupCallbacks = [];

                function cleanupLater(callback) {
                    cleanupCallbacks.push(callback);
                }

                function getCurrentStep() {
                    return steps[activeIndex] || null;
                }

                function getCurrentElement() {
                    return getStepElement(getCurrentStep());
                }

                function ensureDom() {
                    if (overlay && stage && popover) return;

                    overlay = document.createElement('div');
                    overlay.className = 'sr-native-tour-overlay';
                    overlay.setAttribute('aria-hidden', 'true');

                    stage = document.createElement('div');
                    stage.className = 'sr-native-tour-stage';
                    stage.setAttribute('aria-hidden', 'true');

                    popover = document.createElement('section');
                    popover.className = `sr-native-tour-popover driver-popover ${options.popoverClass || ''}`.trim();
                    popover.setAttribute('role', 'dialog');
                    popover.setAttribute('aria-modal', 'true');
                    popover.setAttribute('aria-live', 'polite');

                    document.body.append(overlay, stage, popover);
                    document.body.classList.add('sr-native-tour-active');

                    const onKeydown = (event) => {
                        if (!active) return;

                        if (event.key === 'Escape') {
                            event.preventDefault();
                            requestDestroy();
                        } else if (event.key === 'ArrowRight') {
                            event.preventDefault();
                            if (api.hasNextStep()) api.moveTo(activeIndex + 1);
                            else requestDestroy();
                        } else if (event.key === 'ArrowLeft' && activeIndex > 0) {
                            event.preventDefault();
                            api.moveTo(activeIndex - 1);
                        }
                    };

                    const onViewportChange = () => {
                        if (active) api.refresh();
                    };

                    document.addEventListener('keydown', onKeydown);
                    window.addEventListener('resize', onViewportChange, { passive: true });
                    window.addEventListener('scroll', onViewportChange, { passive: true });

                    cleanupLater(() => document.removeEventListener('keydown', onKeydown));
                    cleanupLater(() => window.removeEventListener('resize', onViewportChange));
                    cleanupLater(() => window.removeEventListener('scroll', onViewportChange));
                }

                function getClampedStageFrame(rect, padding) {
                    const viewportWidth = Math.max(1, window.innerWidth);
                    const viewportHeight = Math.max(1, window.innerHeight);
                    const minEdge = 4;
                    const maxLeft = Math.max(minEdge, viewportWidth - minEdge - 1);
                    const maxTop = Math.max(minEdge, viewportHeight - minEdge - 1);
                    const left = Math.min(Math.max(minEdge, rect.left - padding), maxLeft);
                    const top = Math.min(Math.max(minEdge, rect.top - padding), maxTop);
                    const right = Math.min(viewportWidth - minEdge, Math.max(left + 1, rect.right + padding));
                    const bottom = Math.min(viewportHeight - minEdge, Math.max(top + 1, rect.bottom + padding));

                    return {
                        top,
                        left,
                        width: Math.max(1, right - left),
                        height: Math.max(1, bottom - top),
                    };
                }

                function getPopoverPlacement(rect, popoverRect, preferredSide, preferredAlign, viewportBuffers) {
                    const gap = 12;
                    const margin = 14;
                    const safeTop = Math.max(margin, viewportBuffers?.top ?? margin);
                    const safeBottom = Math.max(margin, viewportBuffers?.bottom ?? margin);
                    const safeLeft = margin;
                    const safeRight = margin;
                    let side = preferredSide || 'bottom';
                    let top = rect.bottom + gap;
                    let left = rect.left + (rect.width - popoverRect.width) / 2;

                    if (side === 'top') {
                        top = rect.top - popoverRect.height - gap;
                    } else if (side === 'left') {
                        top = rect.top + (rect.height - popoverRect.height) / 2;
                        left = rect.left - popoverRect.width - gap;
                    } else if (side === 'right') {
                        top = rect.top + (rect.height - popoverRect.height) / 2;
                        left = rect.right + gap;
                    }

                    if (preferredAlign === 'start') {
                        if (side === 'top' || side === 'bottom') left = rect.left;
                        else top = rect.top;
                    } else if (preferredAlign === 'end') {
                        if (side === 'top' || side === 'bottom') left = rect.right - popoverRect.width;
                        else top = rect.bottom - popoverRect.height;
                    }

                    if (top < safeTop && side === 'top') {
                        top = rect.bottom + gap;
                    }

                    if (top + popoverRect.height > window.innerHeight - safeBottom && side === 'bottom') {
                        top = rect.top - popoverRect.height - gap;
                    }

                    if (left < safeLeft && side === 'left') {
                        left = rect.right + gap;
                    }

                    if (left + popoverRect.width > window.innerWidth - safeRight && side === 'right') {
                        left = rect.left - popoverRect.width - gap;
                    }

                    const maxTop = Math.max(safeTop, window.innerHeight - popoverRect.height - safeBottom);
                    const maxLeft = Math.max(safeLeft, window.innerWidth - popoverRect.width - safeRight);

                    return {
                        top: Math.min(Math.max(safeTop, top), maxTop),
                        left: Math.min(Math.max(safeLeft, left), maxLeft),
                    };
                }

                function render(emitHighlightCallbacks) {
                    if (!active) return;

                    const step = getCurrentStep();
                    const element = getCurrentElement();

                    if (!step || !isElementVisible(element)) {
                        const nextVisibleIndex = steps.findIndex((candidate, index) => index > activeIndex && isVisibleStep(candidate));
                        if (nextVisibleIndex !== -1) {
                            activeIndex = nextVisibleIndex;
                            render(emitHighlightCallbacks);
                            return;
                        }

                        requestDestroy();
                        return;
                    }

                    ensureDom();

                    if (emitHighlightCallbacks && typeof options.onHighlightStarted === 'function') {
                        options.onHighlightStarted(element);
                    }

                    window.setTimeout(() => {
                        if (!active) return;

                        const latestElement = getCurrentElement();
                        if (!isElementVisible(latestElement)) {
                            render(false);
                            return;
                        }

                        const rect = latestElement.getBoundingClientRect();
                        const padding = options.stagePadding ?? 8;
                        const radius = options.stageRadius ?? 14;
                        const popoverConfig = step.popover || {};
                        const stageFrame = getClampedStageFrame(rect, padding);
                        const viewportBuffers = getViewportBuffers(options);
                        const availablePopoverHeight = Math.max(140, window.innerHeight - viewportBuffers.top - viewportBuffers.bottom - 8);

                        stage.style.top = `${stageFrame.top}px`;
                        stage.style.left = `${stageFrame.left}px`;
                        stage.style.width = `${stageFrame.width}px`;
                        stage.style.height = `${stageFrame.height}px`;
                        stage.style.borderRadius = `${radius}px`;

                        const isLast = !api.hasNextStep();
                        popover.innerHTML = `
                            <button type="button" class="sr-native-tour-close driver-popover-close-btn" aria-label="Close tutorial">&times;</button>
                            <h3 class="driver-popover-title">${escapeTourHtml(popoverConfig.title || 'Tutorial')}</h3>
                            <p class="driver-popover-description">${escapeTourHtml(popoverConfig.description || '')}</p>
                            <footer class="driver-popover-footer">
                                <span class="driver-popover-progress-text">${activeIndex + 1} of ${steps.length}</span>
                                <div class="driver-popover-navigation-btns">
                                    <button type="button" class="driver-popover-prev-btn" ${activeIndex === 0 ? 'disabled' : ''}>Back</button>
                                    <button type="button" class="driver-popover-next-btn">${isLast ? 'Done' : 'Next'}</button>
                                </div>
                            </footer>
                        `;

                        const closeButton = popover.querySelector('.sr-native-tour-close');
                        const previousButton = popover.querySelector('.driver-popover-prev-btn');
                        const nextButton = popover.querySelector('.driver-popover-next-btn');

                        closeButton?.addEventListener('click', requestDestroy, { once: true });
                        previousButton?.addEventListener('click', () => api.moveTo(activeIndex - 1), { once: true });
                        nextButton?.addEventListener('click', () => {
                            if (api.hasNextStep()) api.moveTo(activeIndex + 1);
                            else requestDestroy();
                        }, { once: true });

                        popover.style.visibility = 'hidden';
                        popover.style.display = 'block';
                        popover.style.maxHeight = `${availablePopoverHeight}px`;

                        const popoverRect = popover.getBoundingClientRect();
                        const placement = getPopoverPlacement(rect, popoverRect, popoverConfig.side, popoverConfig.align, viewportBuffers);

                        popover.style.top = `${placement.top}px`;
                        popover.style.left = `${placement.left}px`;
                        popover.style.visibility = 'visible';

                        if (emitHighlightCallbacks && typeof options.onHighlighted === 'function') {
                            options.onHighlighted(latestElement);
                        }

                        window.setTimeout(() => {
                            popover.querySelector('.driver-popover-next-btn, .driver-popover-prev-btn, .sr-native-tour-close')?.focus({ preventScroll: true });
                        }, 0);
                    }, 20);
                }

                function requestDestroy() {
                    if (typeof options.onDestroyStarted === 'function') {
                        options.onDestroyStarted();
                        return;
                    }

                    api.destroy();
                }

                const api = {
                    drive() {
                        if (!steps.length || active) return;

                        previousFocus = document.activeElement;
                        active = true;
                        activeIndex = Math.min(Math.max(0, activeIndex), steps.length - 1);
                        render(true);
                    },
                    isActive() {
                        return active;
                    },
                    hasNextStep() {
                        return activeIndex < steps.length - 1;
                    },
                    getActiveIndex() {
                        return activeIndex;
                    },
                    moveTo(index) {
                        if (!active) return;

                        activeIndex = Math.min(Math.max(0, index), steps.length - 1);
                        render(true);
                    },
                    refresh() {
                        render(false);
                    },
                    destroy() {
                        if (!active && !overlay && !stage && !popover) return;

                        active = false;
                        document.body.classList.remove('sr-native-tour-active');
                        overlay?.remove();
                        stage?.remove();
                        popover?.remove();
                        overlay = null;
                        stage = null;
                        popover = null;

                        while (cleanupCallbacks.length) {
                            const cleanup = cleanupCallbacks.pop();
                            try {
                                cleanup();
                            } catch (error) {
                                console.warn('Tutorial cleanup failed:', error);
                            }
                        }

                        if (previousFocus && previousFocus.isConnected && typeof previousFocus.focus === 'function') {
                            window.setTimeout(() => previousFocus.focus({ preventScroll: true }), 0);
                        }

                        if (typeof options.onDestroyed === 'function') {
                            options.onDestroyed();
                        }
                    },
                };

                return api;
            };
        }

        function normalizeLabel(value) {
            return String(value || '')
                .replace(/\s+/g, ' ')
                .replace(/\s*-\s*SpeakReady AI.*$/i, '')
                .trim();
        }

        function getPageTitle(config) {
            const content = document.querySelector('[data-user-ajax-content]');
            const configuredTitle = normalizeLabel(config && config.pageTitle);
            const contentTitle = normalizeLabel(content && content.getAttribute('data-page-title'));
            const headingTitle = normalizeLabel(document.querySelector('#userAppContent h1, #userAppContent h2, #mob-content h1, #mob-content h2, .db-content h1, .db-content h2')?.textContent);
            const documentTitle = normalizeLabel(document.title);

            return configuredTitle || contentTitle || headingTitle || documentTitle || 'this page';
        }

        function routeMatches(routeName, routePrefixes) {
            return routePrefixes.some((routePrefix) => routeName === routePrefix || routeName.startsWith(`${routePrefix}.`));
        }

        function getTourProfile(config, pageTitle) {
            const routeName = String(config?.routeName || '').toLowerCase();
            const path = String(window.location.pathname || '').toLowerCase();
            const base = {
                heroTitle: `${pageTitle} overview`,
                heroDescription: 'Start here to understand the goal of this page and the main information it gives you.',
                workspaceTitle: 'Main workspace',
                workspaceDescription: 'Use this area to review, practice, update, or manage the page content.',
                metricsTitle: 'Progress details',
                metricsDescription: 'Cards, lists, and tables summarize your interview activity so you can spot what needs attention.',
                actionsTitle: 'Available actions',
                actionsDescription: 'Use the primary buttons and controls here to continue the workflow for this page.',
                navigationTitle: 'User navigation',
                navigationDescription: 'Move between mock interviews, modules, challenges, coach, progress, feedback, and reports.',
                toolsTitle: 'Page tools',
                toolsDescription: 'Use search, replay this tutorial, switch fullscreen or theme, check notifications, and manage account or language options.',
                heroSelectors: [],
                workspaceSelectors: [],
                metricsSelectors: [],
                actionSelectors: [],
            };
            const withDefaults = (overrides) => Object.assign({}, base, overrides);

            if (routeMatches(routeName, ['dashboard']) || path.endsWith('/dashboard')) {
                return withDefaults({
                    heroTitle: 'Readiness workspace',
                    heroDescription: 'This dashboard ties together your latest interview readiness, practice history, and next recommended actions.',
                    workspaceTitle: 'Recommended next steps',
                    workspaceDescription: 'Use AI recommendations, recent sessions, and the daily challenge to decide what to practice next.',
                    metricsTitle: 'Readiness snapshot',
                    metricsDescription: 'Your score, XP, streak, rating, and trend summarize recent practice across interviews, challenges, and drills.',
                    actionsTitle: 'Start practice',
                    actionsDescription: 'Jump into a mock interview, challenge, module, or coach prompt from the visible action buttons.',
                    heroSelectors: ['.sr-hero-image-panel', '.sr-score-panel', '#srDashboardTitle'],
                    workspaceSelectors: ['#card-ai-recommendations', '#card-recent-sessions', '#dashboardCoachForm'],
                    metricsSelectors: ['.sr-mobile-stat-grid', '.sr-stats-desktop', '#card-progress-chart', '#card-skill-radar'],
                    actionSelectors: ['#card-daily-challenge', '.sr-challenge-cta', '.sr-btn-primary'],
                });
            }

            if (routeMatches(routeName, ['interview.setup'])) {
                return withDefaults({
                    heroTitle: 'Mock interview setup',
                    heroDescription: 'Build a Philippines-focused job or school interview with role, structure, accessibility, assistance, and response-mode choices.',
                    workspaceTitle: 'Setup panels',
                    workspaceDescription: 'Work through each setup panel to choose the scenario, difficulty, timing, camera option, AI assistance, and answer mode.',
                    metricsTitle: 'Live setup summary',
                    metricsDescription: 'The summary panel keeps your current choices aligned before the custom interview is generated.',
                    actionsTitle: 'Setup controls',
                    actionsDescription: 'Use Back, Next, and Start to move through the guided setup without losing your selected options.',
                    heroSelectors: ['#sec-interview-setup .setup-hero', '#setupStepper'],
                    workspaceSelectors: ['#panel-basic', '#panel-structure', '#panel-inclusive', '#panel-content', '#panel-response'],
                    metricsSelectors: ['#panel-summary'],
                    actionSelectors: ['#setupStepNext', '#btn-start-interview', '#setupStepPrev'],
                });
            }

            if (routeMatches(routeName, ['interview.session', 'interview.review', 'user.review'])) {
                return withDefaults({
                    heroTitle: 'Interview practice session',
                    heroDescription: 'Answer the AI interviewer, use typed or voice responses, and review trustworthy coaching after each scored session.',
                    workspaceTitle: 'Question and response',
                    workspaceDescription: 'The interviewer panel, answer box, and session controls guide the current question and your reply.',
                    metricsTitle: 'Optional coaching signals',
                    metricsDescription: 'Camera, STAR, transcript, and voice analytics are practice signals only; readiness is still based on answer quality.',
                    actionsTitle: 'Session actions',
                    actionsDescription: 'Use the visible controls to listen, record, move between questions, retry, export, or finish the session.',
                    heroSelectors: ['.ai-avatar-panel', '#sessionHero', '#review-summary'],
                    workspaceSelectors: ['#answerForm', '#questionPanel', '#reviewAnswers', '.retry-panel'],
                    metricsSelectors: ['#cameraPanel', '#overallReadiness', '#voiceAnalyticsPanel', '.star-item', '#feedback-summary'],
                    actionSelectors: ['#sessionControls', '#btnFinish', '#btnNext', '.retry-action', '.js-export-session'],
                });
            }

            if (routeMatches(routeName, ['user.feedback'])) {
                return withDefaults({
                    heroTitle: 'Feedback center',
                    heroDescription: 'Review recent AI summaries, answer-by-answer coaching, practice recommendations, and searchable interview history.',
                    workspaceTitle: 'Coaching panels',
                    workspaceDescription: 'Use answer coaching and next-practice recommendations to turn scored feedback into focused practice.',
                    metricsTitle: 'Feedback history',
                    metricsDescription: 'Filter and scan previous sessions by scenario, score, rating, feedback, and available follow-up actions.',
                    actionsTitle: 'Filters and actions',
                    actionsDescription: 'Search, filter, open details, retry answers, clear history, or continue practice from the controls here.',
                    heroSelectors: ['#feedbackModulesLikeHero', '#feedbackAiSummary'],
                    workspaceSelectors: ['#feedbackAnswerCoaching', '#feedbackPracticeRecommendations'],
                    metricsSelectors: ['#feedbackTable', '#feedbackPagination', '#feedback-empty-state'],
                    actionSelectors: ['#feedback-filters', '#feedbackSearch', '#scenarioFilter'],
                });
            }

            if (routeMatches(routeName, ['user.progress'])) {
                return withDefaults({
                    heroTitle: 'Progress analytics',
                    heroDescription: 'Track readiness movement, practice consistency, learning progress, goals, and badges in one private dashboard.',
                    workspaceTitle: 'Practice plan and trends',
                    workspaceDescription: 'Follow personalized practice recommendations and compare readiness trends across your completed sessions.',
                    metricsTitle: 'Skill signals',
                    metricsDescription: 'Scenario, skill, STAR, activity, milestone, and achievement panels show where growth is happening.',
                    actionsTitle: 'Open next work',
                    actionsDescription: 'Use suggested modules, session history, and goal links to continue the most useful next practice.',
                    heroSelectors: ['#progressModulesLikeHero', '#progress-stats'],
                    workspaceSelectors: ['#personalized-practice-plan', '#readiness-trend', '#history-table'],
                    metricsSelectors: ['#category-perf', '#skill-tracker', '#strengths-tracker', '#activity-calendar', '#goals-milestones', '#achievements-badges'],
                    actionSelectors: ['#recommended-next', '#learning-progress', '.progress-actions'],
                });
            }

            if (routeMatches(routeName, ['user.reports'])) {
                return withDefaults({
                    heroTitle: 'Interview reports',
                    heroDescription: 'Reports turn completed scored interviews into readiness summaries, question review, improvement themes, and exports.',
                    workspaceTitle: 'Report sections',
                    workspaceDescription: 'Read the summary, comparison, feedback, question analysis, and learning recommendations for the latest report.',
                    metricsTitle: 'Question and improvement details',
                    metricsDescription: 'Use question review and improvement areas to see what worked, what was missing, and what to practice next.',
                    actionsTitle: 'Export tools',
                    actionsDescription: 'Download PDF, Excel, or CSV files, or print the report for school, job, or mentor review.',
                    heroSelectors: ['#portfolioReport .sr-page-hero', '#report-readiness'],
                    workspaceSelectors: ['#report-feedback', '#report-comparison', '#report-learning'],
                    metricsSelectors: ['#report-question-review', '#report-improvements', '#report-empty-state'],
                    actionSelectors: ['#report-export', '.js-export-pdf', '.js-export-excel', '.js-print-report'],
                });
            }

            if (routeMatches(routeName, ['user.coach'])) {
                return withDefaults({
                    heroTitle: 'AI Chatbot Coach',
                    heroDescription: 'Chat with the interview coach for Philippines preparation, resumes, job descriptions, answer evidence, and career planning.',
                    workspaceTitle: 'Coach conversation',
                    workspaceDescription: 'Messages appear in the chat area, while the input supports prompts, documents, resumes, and job descriptions.',
                    metricsTitle: 'Conversation history',
                    metricsDescription: 'Return to earlier coaching threads or start fresh when you switch roles, schools, or target scenarios.',
                    actionsTitle: 'Ask the coach',
                    actionsDescription: 'Send a question, attach supporting files, clear a thread, or open quick prompts from the coach controls.',
                    heroSelectors: ['#ai-coach-page .coach-progress-hero', '#coachChatTitle'],
                    workspaceSelectors: ['#chatBox', '#coach-input-area', '#coachFiles'],
                    metricsSelectors: ['#coach-sidebar', '#coachActionsMenu'],
                    actionSelectors: ['#coachActions', '#coachActionsToggle', '#coachSendBtn', '.chat-send-btn'],
                });
            }

            if (routeMatches(routeName, ['user.learning'])) {
                return withDefaults({
                    heroTitle: 'Philippines interview challenges',
                    heroDescription: 'Challenge paths use Learning Games to build XP, energy management, combo streaks, and role-specific practice.',
                    workspaceTitle: 'Challenge journey',
                    workspaceDescription: 'Switch paths, choose a level, review its goals and energy cost, then start the challenge.',
                    metricsTitle: 'Player stats',
                    metricsDescription: 'Track level, energy, accuracy, combo streaks, certificates, and skill XP as you complete challenges.',
                    actionsTitle: 'Start or upgrade',
                    actionsDescription: 'Start a challenge or open Skill Trees to spend earned XP on training perks.',
                    heroSelectors: ['#learning-games-page .sr-learning-hero', '#learning-games-page .sr-page-hero'],
                    workspaceSelectors: ['#modules-list', '#nav-pills-container', '#learningCategorySelect'],
                    metricsSelectors: ['#dashboard-stats', '.level-card', '.ll-stat-card'],
                    actionSelectors: ['#btn-skill-tree', '.start-challenge-btn'],
                });
            }

            if (routeMatches(routeName, ['user.modules'])) {
                return withDefaults({
                    heroTitle: 'Interview modules',
                    heroDescription: 'Modules organize lessons, resources, quizzes, and practice activities around Philippines interview skills.',
                    workspaceTitle: 'Module library',
                    workspaceDescription: 'Search modules, open a learning path, and use chapter tabs to move between content, resources, quizzes, and activities.',
                    metricsTitle: 'Learning progress',
                    metricsDescription: 'Cards and progress indicators show which modules are started, active, recommended, or completed.',
                    actionsTitle: 'Module actions',
                    actionsDescription: 'Open a module, continue a chapter, take a quiz, or complete a practice activity from the visible controls.',
                    heroSelectors: ['#interview-modules-page .modules-hero', '#interview-modules-page .sr-page-hero', '#modules-hero-title'],
                    workspaceSelectors: ['#moduleSearchInput', '#modules-list', '#moduleTabs', '#chapters'],
                    metricsSelectors: ['.module-card', '.module-path-copy', '.module-rec-copy'],
                    actionSelectors: ['#quizzes-tab', '#activities-tab', 'a[href*="/modules/"]', '.module-card .btn'],
                });
            }

            if (routeMatches(routeName, ['user.skills'])) {
                return withDefaults({
                    heroTitle: 'Skill Trees',
                    heroDescription: 'Spend Skill XP earned from PH Challenges on perks that strengthen your learning and practice loop.',
                    workspaceTitle: 'Available perks',
                    workspaceDescription: 'Review each perk, its XP type, cost, unlocked state, and the benefit it adds to your training.',
                    metricsTitle: 'Skill XP overview',
                    metricsDescription: 'XP totals show leadership, communication, technical, and problem-solving growth.',
                    actionsTitle: 'Unlock or return',
                    actionsDescription: 'Unlock affordable perks or return to PH Challenges to earn more XP.',
                    heroSelectors: ['#skill-trees-page .skill-tree-hero', '#skill-trees-page .sr-page-hero'],
                    workspaceSelectors: ['.perks-panel', '.perk-card'],
                    metricsSelectors: ['.skill-xp-overview', '.stat-card'],
                    actionSelectors: ['.btn-unlock', '.skill-back-link'],
                });
            }

            if (routeMatches(routeName, ['user.notifications'])) {
                return withDefaults({
                    heroTitle: 'Notifications',
                    heroDescription: 'Review alerts, activity updates, reminders, and system messages connected to your practice account.',
                    workspaceTitle: 'Notification list',
                    workspaceDescription: 'Unread and read items appear here with dates, context, and quick actions.',
                    metricsTitle: 'Activity state',
                    metricsDescription: 'Badges and empty states show whether anything needs attention.',
                    actionsTitle: 'Notification actions',
                    actionsDescription: 'Mark items as read, delete individual updates, clear groups, or open all notification details.',
                    heroSelectors: ['#notifications-page .notif-hero', '#notificationActionStatus'],
                    workspaceSelectors: ['#notificationsPageList', '.notification-row'],
                    metricsSelectors: ['#notificationsPageList', '.notifications-empty-state'],
                    actionSelectors: ['.notification-action-btn', '#notificationActionStatus'],
                });
            }

            if (routeMatches(routeName, ['user.account'])) {
                return withDefaults({
                    heroTitle: 'Account management',
                    heroDescription: 'Keep your profile, target role, photo, language, password, and account controls up to date.',
                    workspaceTitle: 'Profile details',
                    workspaceDescription: 'Update your name, email, target position, and profile photo including crop controls when a new image is selected.',
                    metricsTitle: 'Security and preferences',
                    metricsDescription: 'Password, language, notifications, and account safety controls help keep the practice experience personal.',
                    actionsTitle: 'Save changes',
                    actionsDescription: 'Use the save, password, crop, and account-action buttons to apply only the changes you want.',
                    heroSelectors: ['#account-page .sr-page-hero', '#accountProfileForm'],
                    workspaceSelectors: ['#accountProfileForm', '#profileCropModal'],
                    metricsSelectors: ['#accountPasswordForm', '#accountDeleteForm'],
                    actionSelectors: ['#accountProfileForm button[type="submit"]', '#accountPasswordForm button[type="submit"]', '#profileCropZoom'],
                });
            }

            return base;
        }

        function makeCompletionKey(config) {
            if (config && config.completionKey) return config.completionKey;

            const routeName = normalizeLabel(config && config.routeName)
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');

            if (routeName) {
                return `onboarding_completed_${routeName}`;
            }

            const pathKey = normalizeLabel(window.location.pathname || 'user_page')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '') || 'user_page';

            return `onboarding_completed_${pathKey}`;
        }

        function findVisibleElement(selectors, options) {
            const contentOnly = Boolean(options && options.contentOnly);

            for (const selector of selectors) {
                const elements = document.querySelectorAll(selector);
                for (const element of elements) {
                    if (contentOnly && !isInsidePageContent(element)) continue;

                    if (isElementVisible(element)) {
                        return element;
                    }
                }
            }

            return null;
        }

        function pushStep(steps, usedElements, element, title, description, side, align) {
            if (!element || usedElements.has(element)) return;

            usedElements.add(element);
            steps.push({
                element,
                popover: {
                    title,
                    description,
                    side: side || 'bottom',
                    align: align || 'center',
                },
            });
        }

        function buildUniversalTourSteps(config) {
            const mobile = isMobileTour(config && config.serverDetectedMobile);
            const pageTitle = getPageTitle(config);
            const steps = [];
            const usedElements = new Set();
            const contentOnly = !(config && config.includeShellSteps);
            const tourProfile = getTourProfile(config, pageTitle);

            const pageHero = findVisibleElement([
                ...(tourProfile.heroSelectors || []),
                '#userAppContent .sr-hero-card',
                '#userAppContent .sr-page-hero',
                '#userAppContent .progress-hero',
                '#userAppContent .feedback-hero',
                '#userAppContent .setup-hero',
                '#userAppContent .modules-hero',
                '#userAppContent .mod-hero',
                '#userAppContent .mission-progress-hero',
                '#userAppContent .sr-learning-hero',
                '#userAppContent .coach-progress-hero',
                '#userAppContent .mastery-hero-card',
                '#userAppContent .notif-hero',
                '#userAppContent .skill-tree-hero',
                '#userAppContent h1',
                '#userAppContent h2',
                '#mob-content .sr-hero-card',
                '#mob-content .sr-page-hero',
                '#mob-content .progress-hero',
                '#mob-content .feedback-hero',
                '#mob-content .setup-hero',
                '#mob-content .modules-hero',
                '#mob-content .mod-hero',
                '#mob-content .mission-progress-hero',
                '#mob-content .sr-learning-hero',
                '#mob-content .coach-progress-hero',
                '#mob-content .mastery-hero-card',
                '#mob-content .notif-hero',
                '#mob-content .skill-tree-hero',
                '#mob-content h1',
                '#mob-content h2',
            ], { contentOnly });

            pushStep(
                steps,
                usedElements,
                pageHero,
                tourProfile.heroTitle,
                tourProfile.heroDescription,
                'bottom',
                'start'
            );

            const primaryWorkspace = findVisibleElement([
                ...(tourProfile.workspaceSelectors || []),
                '#userAppContent form',
                '#userAppContent .chat-container',
                '#userAppContent #workspaceRow',
                '#userAppContent .table-responsive',
                '#userAppContent .accordion',
                '#userAppContent .module-card',
                '#userAppContent .ll-module-card',
                '#userAppContent .level-card',
                '#userAppContent .mastery-panel',
                '#userAppContent .feedback-insight-panel',
                '#userAppContent .notifications-list-panel',
                '#userAppContent .perks-panel',
                '#userAppContent .premium-panel',
                '#userAppContent .sr-card',
                '#userAppContent .card',
                '#mob-content form',
                '#mob-content .chat-container',
                '#mob-content #workspaceRow',
                '#mob-content .table-responsive',
                '#mob-content .accordion',
                '#mob-content .module-card',
                '#mob-content .ll-module-card',
                '#mob-content .level-card',
                '#mob-content .mastery-panel',
                '#mob-content .feedback-insight-panel',
                '#mob-content .notifications-list-panel',
                '#mob-content .perks-panel',
                '#mob-content .premium-panel',
                '#mob-content .sr-card',
                '#mob-content .card',
            ], { contentOnly });

            pushStep(
                steps,
                usedElements,
                primaryWorkspace,
                tourProfile.workspaceTitle,
                tourProfile.workspaceDescription,
                'top',
                'center'
            );

            const metricsOrList = findVisibleElement([
                ...(tourProfile.metricsSelectors || []),
                '#dashboard-stats',
                '#progress-stats',
                '#userAppContent .mastery-stats-grid',
                '#userAppContent .stat-grid',
                '#userAppContent .db-stat-card',
                '#userAppContent .sr-stat-card',
                '#userAppContent .stat-card',
                '#userAppContent .ll-stat-card',
                '#userAppContent table',
                '#userAppContent .list-group',
                '#mob-content .mastery-stats-grid',
                '#mob-content .stat-grid',
                '#mob-content .db-stat-card',
                '#mob-content .sr-stat-card',
                '#mob-content .stat-card',
                '#mob-content .ll-stat-card',
                '#mob-content table',
                '#mob-content .list-group',
            ], { contentOnly });

            pushStep(
                steps,
                usedElements,
                metricsOrList,
                tourProfile.metricsTitle,
                tourProfile.metricsDescription,
                'top',
                'center'
            );

            const actionArea = findVisibleElement([
                ...(tourProfile.actionSelectors || []),
                '#userAppContent .sr-page-actions',
                '#userAppContent .progress-actions',
                '#userAppContent .coach-actions',
                '#userAppContent .start-challenge-btn',
                '#userAppContent .mission-btn-primary',
                '#userAppContent .btn-unlock',
                '#userAppContent .btn-primary',
                '#userAppContent button[type="submit"]',
                '#userAppContent a.btn',
                '#mob-content .sr-page-actions',
                '#mob-content .progress-actions',
                '#mob-content .coach-actions',
                '#mob-content .start-challenge-btn',
                '#mob-content .mission-btn-primary',
                '#mob-content .btn-unlock',
                '#mob-content .btn-primary',
                '#mob-content button[type="submit"]',
                '#mob-content a.btn',
            ], { contentOnly });

            pushStep(
                steps,
                usedElements,
                actionArea,
                tourProfile.actionsTitle,
                tourProfile.actionsDescription,
                'top',
                'center'
            );

            if (!contentOnly) {
                const navigation = mobile ?
                    findVisibleElement(['#mob-bottom-nav', '#mob-header']) :
                    findVisibleElement(['#dbSidebar', '.db-nav']);

                pushStep(
                    steps,
                    usedElements,
                    navigation,
                    mobile ? 'Mobile navigation' : 'Sidebar navigation',
                    mobile ?
                        'Use Home, Progress, Interview, Feedback, and More to move through the updated practice areas.' :
                        tourProfile.navigationDescription,
                    mobile ? 'top' : 'right',
                    'center'
                );

                const pageTools = mobile ?
                    findVisibleElement(['#mob-header', '#mobTutorialBtn', '#mobFullscreenBtn', '#mobThBtn', '#mobBellBtn', '#mobProfileBtn', '.ucp-mobile-launcher']) :
                    findVisibleElement(['.db-top', '#dbPageSearch', '#dbTutorialBtn', '#dbFullscreenBtn', '#dbThBtn', '#bellBtn', '#userPill', '[data-ucp-open]']);

                pushStep(
                    steps,
                    usedElements,
                    pageTools,
                    tourProfile.toolsTitle,
                    tourProfile.toolsDescription,
                    mobile ? 'bottom' : 'bottom',
                    'center'
                );
            }

            if (!steps.length) {
                const content = findVisibleElement(['[data-user-ajax-content]', '#userAppContent', '#mob-content', '.db-content']);
                pushStep(
                    steps,
                    usedElements,
                    content,
                    `${pageTitle} tutorial`,
                    'This page is ready to use. Follow the visible panels and controls to continue your interview preparation.',
                    'bottom',
                    'center'
                );
            }

            return steps;
        }

        window.buildSpeakReadyDefaultTourSteps = buildUniversalTourSteps;

        window.resetSpeakReadyOnboardingForNavigation = function() {
            window.__speakReadyTourResetVersion = (window.__speakReadyTourResetVersion || 0) + 1;
            bumpTourRegistrationVersion();
            clearTourHighlightedElement();

            if (window.__speakReadyActiveDriver && typeof window.__speakReadyActiveDriver.destroy === 'function') {
                destroyTourDriver(window.__speakReadyActiveDriver);
            }

            window.__speakReadyActiveDriver = null;
            window.__speakReadyTourController = null;
            window.__speakReadyTourScope = null;
            window.__speakReadyRegisteredTour = null;
            window.__speakReadyPageTourRegistered = false;
            window.__speakReadyFallbackTourRegistered = false;
            window.__speakReadyFallbackTour = null;

            try {
                delete window.startOnboardingTour;
            } catch (error) {
                window.startOnboardingTour = undefined;
            }
        };

        function clearRegisteredTour(controller) {
            if (window.__speakReadyTourController && window.__speakReadyTourController !== controller) return;

            clearTourHighlightedElement();
            window.__speakReadyTourController = null;
            window.__speakReadyTourScope = null;
            if (window.__speakReadyRegisteredTour && window.__speakReadyRegisteredTour.controller === controller) {
                window.__speakReadyRegisteredTour = null;
            }
            window.__speakReadyPageTourRegistered = false;
            window.__speakReadyFallbackTourRegistered = false;
            window.__speakReadyFallbackTour = null;

            try {
                delete window.startOnboardingTour;
            } catch (error) {
                window.startOnboardingTour = undefined;
            }
        }

        function hasCurrentRegisteredTour() {
            return typeof window.startOnboardingTour === 'function' &&
                window.__speakReadyTourController &&
                isCurrentTourScope(window.__speakReadyTourScope);
        }

        window.createSpeakReadyTour = function(config) {
            config = config || {};

            const isFallbackTour = Boolean(config.isFallback);
            const driverFactory = (typeof window.driver !== 'undefined' && window.driver.js && typeof window.driver.js.driver === 'function') ?
                window.driver.js.driver :
                getNativeTourFactory();
            const completionKey = config.completionKey;
            const registrationScope = normalizePageScope(config.pageScope) || getCurrentPageScope();
            const registrationKey = makeRegistrationKey(registrationScope, completionKey, isFallbackTour);
            const existingRegistration = getRegisteredTour();

            if (existingRegistration && existingRegistration.key === registrationKey) {
                if (config.exposeGlobal !== false) {
                    window.startOnboardingTour = existingRegistration.controller.start;
                    window.__speakReadyTourController = existingRegistration.controller;
                    window.__speakReadyTourScope = existingRegistration.scope;
                }

                if (isFallbackTour) {
                    window.__speakReadyFallbackTourRegistered = true;
                    window.__speakReadyFallbackTour = existingRegistration.controller;
                } else {
                    window.__speakReadyPageTourRegistered = true;
                }

                return existingRegistration.controller;
            }

            if (isFallbackTour && existingRegistration && !existingRegistration.isFallback) {
                return existingRegistration.controller;
            }

            const registrationVersion = bumpTourRegistrationVersion();
            let activeTour = null;
            let isStarting = false;

            if (isFallbackTour) {
                window.__speakReadyFallbackTourRegistered = true;
            } else {
                window.__speakReadyPageTourRegistered = true;
            }

            function createTour() {
                let steps = getSteps(config);

                if (!steps.length && config.allowDefaultFallback !== false) {
                    steps = buildUniversalTourSteps(config).filter(isVisibleStep);
                }

                if (!steps.length) return null;

                let driverObj;
                driverObj = driverFactory({
                    showProgress: true,
                    animate: true,
                    smoothScroll: false,
                    stagePadding: config.stagePadding ?? 8,
                    stageRadius: config.stageRadius ?? 14,
                    overlayOpacity: config.overlayOpacity ?? 0.58,
                    popoverClass: getPopoverClass(),
                    serverDetectedMobile: config.serverDetectedMobile,
                    mobileTopBuffer: config.mobileTopBuffer,
                    mobileBottomBuffer: config.mobileBottomBuffer,
                    desktopTopBuffer: config.desktopTopBuffer,
                    desktopBottomBuffer: config.desktopBottomBuffer,
                    steps,
                    onHighlightStarted: (element, step, options) => {
                        setTourHighlightedElement(element);

                        if (typeof config.onHighlightStarted === 'function') {
                            config.onHighlightStarted(element, driverObj, step, options);
                        }

                        keepHighlightedElementInView(element, config);
                    },
                    onHighlighted: (element, step, options) => {
                        setTourHighlightedElement(element);

                        if (typeof config.onHighlighted === 'function') {
                            config.onHighlighted(element, driverObj, step, options);
                        }

                        refreshTourPlacement(driverObj);
                    },
                    onDestroyStarted: () => {
                        const forceDestroy = window.__speakReadyForceDestroy === true;
                        const exitText = config.exitConfirmText || 'Are you sure you want to exit the tutorial?';

                        if (forceDestroy || !driverObj.hasNextStep() || confirm(exitText)) {
                            if (!forceDestroy && completionKey) {
                                localStorage.setItem(completionKey, 'true');
                            }

                            if (typeof config.onBeforeDestroy === 'function') {
                                config.onBeforeDestroy(driverObj);
                            }

                            driverObj.destroy();
                        }
                    },
                    onDestroyed: () => {
                        if (activeTour === driverObj) {
                            activeTour = null;
                        }

                        if (window.__speakReadyActiveDriver === driverObj) {
                            window.__speakReadyActiveDriver = null;
                        }

                        clearTourHighlightedElement();

                        if (typeof config.onDestroyed === 'function') {
                            config.onDestroyed(driverObj);
                        }
                    },
                });

                return driverObj;
            }

            const controller = {
                start() {
                    if (!isCurrentTourScope(registrationScope)) {
                        clearRegisteredTour(controller);
                        return false;
                    }

                    if (window.__speakReadyRegisteredTour && window.__speakReadyRegisteredTour.controller !== controller) {
                        return false;
                    }

                    if (activeTour || isStarting) return true;

                    isStarting = true;

                    if (typeof config.beforeStart === 'function') {
                        config.beforeStart();
                    }

                    const resetVersion = window.__speakReadyTourResetVersion || 0;

                    window.setTimeout(() => {
                        if ((window.__speakReadyTourResetVersion || 0) !== resetVersion) {
                            isStarting = false;
                            return;
                        }

                        if ((window.__speakReadyTourRegistrationVersion || 0) !== registrationVersion) {
                            isStarting = false;
                            return;
                        }

                        if (!isCurrentTourScope(registrationScope)) {
                            isStarting = false;
                            clearRegisteredTour(controller);
                            return;
                        }

                        if (window.__speakReadyRegisteredTour && window.__speakReadyRegisteredTour.controller !== controller) {
                            isStarting = false;
                            return;
                        }

                        if (window.__speakReadyActiveDriver && window.__speakReadyActiveDriver !== activeTour) {
                            destroyTourDriver(window.__speakReadyActiveDriver);
                            window.__speakReadyActiveDriver = null;
                        }

                        activeTour = createTour();
                        isStarting = false;

                        if (!activeTour) {
                            if (typeof config.onDestroyed === 'function') {
                                config.onDestroyed(null);
                            }
                            return;
                        }

                        window.__speakReadyActiveDriver = activeTour;
                        requestAnimationFrame(() => activeTour.drive());
                    }, config.startDelay ?? 0);

                    return true;
                },

                startIfIncomplete(delay) {
                    if (!isCurrentTourScope(registrationScope)) {
                        clearRegisteredTour(controller);
                        return;
                    }

                    if (!completionKey || !localStorage.getItem(completionKey)) {
                        window.setTimeout(() => {
                            if (isCurrentTourScope(registrationScope) && (!completionKey || !localStorage.getItem(completionKey))) {
                                controller.start();
                            }
                        }, delay ?? config.autoStartDelay ?? 500);
                    }
                },

                isCompleted() {
                    return Boolean(completionKey && localStorage.getItem(completionKey));
                },

                isForCurrentPage() {
                    return isCurrentTourScope(registrationScope);
                },

                destroy() {
                    isStarting = false;
                    const ownsGlobalRegistration = !window.__speakReadyRegisteredTour ||
                        window.__speakReadyRegisteredTour.controller === controller;

                    if (activeTour) {
                        const tourToDestroy = activeTour;
                        activeTour = null;
                        destroyTourDriver(tourToDestroy);
                    }

                    if (ownsGlobalRegistration && window.__speakReadyActiveDriver) {
                        destroyTourDriver(window.__speakReadyActiveDriver);
                        window.__speakReadyActiveDriver = null;
                    }

                    clearRegisteredTour(controller);
                },
            };

            if (existingRegistration && existingRegistration.controller && existingRegistration.controller !== controller) {
                if (typeof existingRegistration.controller.destroy === 'function') {
                    existingRegistration.controller.destroy();
                } else if (window.__speakReadyActiveDriver) {
                    destroyTourDriver(window.__speakReadyActiveDriver);
                    window.__speakReadyActiveDriver = null;
                }
            }

            if (config.exposeGlobal !== false) {
                window.startOnboardingTour = controller.start;
                window.__speakReadyTourController = controller;
                window.__speakReadyTourScope = registrationScope;
                window.__speakReadyRegisteredTour = {
                    key: registrationKey,
                    scope: registrationScope,
                    controller,
                    isFallback: isFallbackTour,
                    completionKey: completionKey || null,
                };
            }

            if (config.autoStart !== false) {
                controller.startIfIncomplete(config.autoStartDelay);
            }

            return controller;
        };

        window.initSpeakReadyFallbackTour = function(config) {
            const suppliedContext = config || {};
            const context = isCurrentTourScope(suppliedContext.pageScope) ? suppliedContext : {};
            context.pageScope = getCurrentPageScope();
            context.includeShellSteps = suppliedContext.includeShellSteps !== false;
            const registeredTour = getRegisteredTour();

            if ((window.__speakReadyPageTourRegistered || window.__speakReadyFallbackTourRegistered) && hasCurrentRegisteredTour()) {
                return registeredTour?.controller || window.__speakReadyTourController || window.__speakReadyFallbackTour || null;
            }

            if (typeof window.startOnboardingTour === 'function' && !hasCurrentRegisteredTour()) {
                clearRegisteredTour(window.__speakReadyTourController);
            }

            if (typeof window.createSpeakReadyTour !== 'function') return null;

            const steps = buildUniversalTourSteps(context).filter(isVisibleStep);
            if (!steps.length) return null;

            const controller = window.createSpeakReadyTour({
                completionKey: makeCompletionKey(context),
                pageTitle: context.pageTitle,
                routeName: context.routeName,
                serverDetectedMobile: context.serverDetectedMobile,
                includeShellSteps: context.includeShellSteps !== false,
                stepsDesktop: steps,
                stepsMobile: steps,
                autoStart: context.autoStart === true,
                autoStartDelay: context.autoStartDelay ?? 700,
                startDelay: context.startDelay ?? 0,
                isFallback: true,
            });

            window.__speakReadyFallbackTour = controller;
            return controller;
        };
    })();
</script>
