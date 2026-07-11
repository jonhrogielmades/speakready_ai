<script>
    (function() {
        if (window.createSpeakReadyTour) return;

        function isMobileTour(serverDetectedMobile) {
            return Boolean(serverDetectedMobile) ||
                Boolean(document.getElementById('mob-content')) ||
                window.matchMedia('(max-width: 767px)').matches;
        }

        function isVisibleStep(step) {
            if (!step.element) return true;

            const element = document.querySelector(step.element);
            if (!element) return false;

            const style = window.getComputedStyle(element);
            return style.display !== 'none' &&
                style.visibility !== 'hidden' &&
                element.getClientRects().length > 0;
        }

        function getPopoverClass() {
            const themeClass = document.documentElement.classList.contains('lm') ?
                'driverjs-theme-light' :
                'driverjs-theme-dark';

            return `sr-driver-popover ${themeClass}`;
        }

        function getViewportBuffers(config) {
            const mobile = isMobileTour(config.serverDetectedMobile);

            return {
                top: mobile ? (config.mobileTopBuffer ?? 78) : (config.desktopTopBuffer ?? 24),
                bottom: mobile ? (config.mobileBottomBuffer ?? 96) : (config.desktopBottomBuffer ?? 24),
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

        window.createSpeakReadyTour = function(config) {
            if (typeof window.driver === 'undefined' || !window.driver.js) return null;

            const driverFactory = window.driver.js.driver;
            const completionKey = config.completionKey;
            let activeTour = null;
            let isStarting = false;

            function createTour() {
                const steps = getSteps(config);
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
                    steps,
                    onHighlightStarted: (element) => keepHighlightedElementInView(element, config),
                    onHighlighted: () => refreshTourPlacement(driverObj),
                    onDestroyStarted: () => {
                        const exitText = config.exitConfirmText || 'Are you sure you want to exit the tutorial?';

                        if (!driverObj.hasNextStep() || confirm(exitText)) {
                            if (completionKey) {
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

                        if (typeof config.onDestroyed === 'function') {
                            config.onDestroyed(driverObj);
                        }
                    },
                });

                return driverObj;
            }

            const controller = {
                start() {
                    if (activeTour || isStarting) return;

                    isStarting = true;

                    if (typeof config.beforeStart === 'function') {
                        config.beforeStart();
                    }

                    window.setTimeout(() => {
                        activeTour = createTour();
                        isStarting = false;

                        if (!activeTour) return;

                        requestAnimationFrame(() => activeTour.drive());
                    }, config.startDelay ?? 0);
                },

                startIfIncomplete(delay) {
                    if (!completionKey || !localStorage.getItem(completionKey)) {
                        window.setTimeout(() => {
                            if (!completionKey || !localStorage.getItem(completionKey)) {
                                controller.start();
                            }
                        }, delay ?? config.autoStartDelay ?? 500);
                    }
                },

                isCompleted() {
                    return Boolean(completionKey && localStorage.getItem(completionKey));
                },
            };

            if (config.exposeGlobal !== false) {
                window.startOnboardingTour = controller.start;
            }

            if (config.autoStart !== false) {
                controller.startIfIncomplete(config.autoStartDelay);
            }

            return controller;
        };
    })();
</script>
