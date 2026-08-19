@auth
<script>
(function () {
    const language = @json($currentLanguageCode ?? 'en');
    if (!language || language === 'en') return;

    const endpoint = @json(route('user.language.translate'));
    const token = @json(csrf_token());
    const excludedSelector = 'script,style,noscript,textarea,input,select,option,code,pre,[data-ai-translate="skip"],.notranslate';
    const attributeNames = ['placeholder', 'title', 'aria-label'];
    const cachePrefix = 'sr.ai.translation.' + language + '.';
    let pendingTimer = null;
    let isApplying = false;

    function normalizeText(text) {
        return String(text || '').replace(/\s+/g, ' ').trim();
    }

    function shouldTranslate(text) {
        const value = normalizeText(text);
        if (value.length < 2 || value.length > 500) return false;
        if (/^[\d\s.,:%#/+()-]+$/.test(value)) return false;
        if (/^[\W_]+$/.test(value)) return false;
        if (/https?:\/\//i.test(value) || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return false;
        return /[A-Za-z]/.test(value);
    }

    function hashText(text) {
        let hash = 0;
        for (let i = 0; i < text.length; i++) {
            hash = ((hash << 5) - hash) + text.charCodeAt(i);
            hash |= 0;
        }
        return Math.abs(hash).toString(36) + '.' + text.length;
    }

    function readCached(text) {
        try {
            const raw = localStorage.getItem(cachePrefix + hashText(text));
            if (!raw) return null;
            const cached = JSON.parse(raw);
            return cached && cached.source === text ? cached.translated : null;
        } catch (error) {
            return null;
        }
    }

    function writeCached(text, translated) {
        try {
            localStorage.setItem(cachePrefix + hashText(text), JSON.stringify({
                source: text,
                translated: translated
            }));
        } catch (error) {
            // Storage may be full or unavailable; translation still works for this page.
        }
    }

    function closestExcluded(node) {
        const element = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
        return element ? element.closest(excludedSelector) : null;
    }

    function markAttribute(element, name) {
        element.setAttribute('data-sr-translated-' + name.replace(/[^a-z0-9]/gi, '-'), '1');
    }

    function attributeWasMarked(element, name) {
        return element.getAttribute('data-sr-translated-' + name.replace(/[^a-z0-9]/gi, '-')) === '1';
    }

    function addTarget(targets, text, target) {
        if (!targets.has(text)) targets.set(text, []);
        targets.get(text).push(target);
    }

    function collectTargets(root) {
        const targets = new Map();
        const base = root && root.nodeType === Node.ELEMENT_NODE ? root : document.body;
        if (!base || closestExcluded(base)) return targets;

        const walker = document.createTreeWalker(base, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                if (node.__srTranslated || closestExcluded(node)) return NodeFilter.FILTER_REJECT;
                return shouldTranslate(node.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
        });

        while (walker.nextNode()) {
            const node = walker.currentNode;
            addTarget(targets, normalizeText(node.nodeValue), { type: 'text', node: node });
        }

        base.querySelectorAll('*').forEach(function (element) {
            if (closestExcluded(element)) return;
            attributeNames.forEach(function (name) {
                if (attributeWasMarked(element, name)) return;
                const value = element.getAttribute(name);
                if (shouldTranslate(value)) {
                    addTarget(targets, normalizeText(value), { type: 'attribute', element: element, name: name });
                }
            });
        });

        return targets;
    }

    function applyTranslations(targets, translations) {
        isApplying = true;
        targets.forEach(function (items, source) {
            const translated = normalizeText(translations[source] || source);
            if (!translated) return;

            items.forEach(function (item) {
                if (item.type === 'text' && item.node && item.node.parentNode) {
                    const original = item.node.nodeValue || '';
                    const leading = (original.match(/^\s*/) || [''])[0];
                    const trailing = (original.match(/\s*$/) || [''])[0];
                    item.node.nodeValue = leading + translated + trailing;
                    item.node.__srTranslated = true;
                } else if (item.type === 'attribute' && item.element) {
                    item.element.setAttribute(item.name, translated);
                    markAttribute(item.element, item.name);
                }
            });
        });
        isApplying = false;
    }

    function translatePage(root) {
        const targets = collectTargets(root);
        if (targets.size === 0) return;

        const cached = {};
        const missing = [];

        targets.forEach(function (_items, source) {
            const translated = readCached(source);
            if (translated) {
                cached[source] = translated;
            } else {
                missing.push(source);
            }
        });

        if (Object.keys(cached).length > 0) {
            applyTranslations(targets, cached);
        }

        if (missing.length === 0) return;

        const chunks = [];
        for (let i = 0; i < missing.length; i += 80) {
            chunks.push(missing.slice(i, i + 80));
        }

        chunks.forEach(function (chunk) {
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ texts: chunk, language: language })
            })
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (data) {
                    if (!data || !data.translations) return;
                    Object.keys(data.translations).forEach(function (source) {
                        writeCached(source, data.translations[source]);
                    });
                    applyTranslations(targets, data.translations);
                })
                .catch(function () {
                    // Keep original English if the provider or network is unavailable.
                });
        });
    }

    function scheduleTranslation(root) {
        clearTimeout(pendingTimer);
        pendingTimer = setTimeout(function () {
            if ('requestIdleCallback' in window) {
                requestIdleCallback(function () { translatePage(root || document.body); }, { timeout: 1500 });
            } else {
                translatePage(root || document.body);
            }
        }, 250);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { scheduleTranslation(document.body); });
    } else {
        scheduleTranslation(document.body);
    }

    const observer = new MutationObserver(function (mutations) {
        if (isApplying) return;
        const target = mutations.find(function (mutation) {
            return mutation.addedNodes && mutation.addedNodes.length > 0;
        });
        if (target) scheduleTranslation(document.body);
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
</script>
@endauth
