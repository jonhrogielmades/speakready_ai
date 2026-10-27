(function initializeSpeakReadyOnboardingVoiceover(globalScope) {
    'use strict';

    if (!globalScope || globalScope.SpeakReadyOnboardingVoiceover) return;

    const normalizeText = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const normalizeLocale = (value) => String(value || 'en-PH').replace('_', '-').trim() || 'en-PH';

    function create(options = {}) {
        const endpoint = String(options.endpoint || '').trim();
        const providerAvailable = options.providerAvailable !== false;
        const fetchImpl = options.fetchImpl || (typeof globalScope.fetch === 'function' ? globalScope.fetch.bind(globalScope) : null);
        const AudioCtor = options.AudioCtor || globalScope.Audio || null;
        const FormDataCtor = options.FormDataCtor || globalScope.FormData || null;
        const AbortControllerCtor = options.AbortControllerCtor || globalScope.AbortController || null;
        const createObjectURL = options.createObjectURL || globalScope.URL?.createObjectURL?.bind(globalScope.URL);
        const revokeObjectURL = options.revokeObjectURL || globalScope.URL?.revokeObjectURL?.bind(globalScope.URL);
        const speechSynthesis = options.speechSynthesis || globalScope.speechSynthesis || null;
        const UtteranceCtor = options.UtteranceCtor || globalScope.SpeechSynthesisUtterance || null;
        const setTimer = options.setTimeoutImpl || globalScope.setTimeout?.bind(globalScope) || setTimeout;
        const clearTimer = options.clearTimeoutImpl || globalScope.clearTimeout?.bind(globalScope) || clearTimeout;
        const providerTimeoutMs = Math.max(1, Number(options.providerTimeoutMs ?? options.timeoutMs ?? 7000) || 7000);
        const playbackStartTimeoutMs = Math.max(1, Number(options.playbackStartTimeoutMs ?? 2500) || 2500);
        const providerFailureCooldownMs = Math.max(0, Number(options.providerFailureCooldownMs ?? 30000) || 0);
        const defaultLocale = normalizeLocale(options.defaultLocale);
        const maxCacheEntries = Math.max(4, Number(options.maxCacheEntries ?? 48) || 48);
        const audioCache = new Map();
        const inflight = new Map();
        let activePlayback = null;
        let activeFallbackUtterance = null;
        let sequence = 0;
        let destroyed = false;
        let preferredVoice = null;
        let providerRetryAfter = 0;

        const getCsrfToken = () => {
            if (typeof options.getCsrfToken === 'function') return String(options.getCsrfToken() || '');
            return String(options.csrfToken || '');
        };

        const reportError = (error, phase) => {
            if (typeof options.onError === 'function') options.onError(error, phase);
        };

        const keyFor = (text, locale) => `${normalizeLocale(locale).toLowerCase()}\n${normalizeText(text)}`;

        function rememberAudio(key, blob) {
            audioCache.delete(key);
            audioCache.set(key, blob);
            while (audioCache.size > maxCacheEntries) audioCache.delete(audioCache.keys().next().value);
            return blob;
        }

        function cachedAudio(key) {
            if (!audioCache.has(key)) return null;
            const blob = audioCache.get(key);
            audioCache.delete(key);
            audioCache.set(key, blob);
            return blob;
        }

        function namedError(name, message) {
            const error = new Error(message);
            error.name = name;
            return error;
        }

        function canUseProvider() {
            return !destroyed && providerAvailable && endpoint !== '' && typeof fetchImpl === 'function' &&
                typeof AudioCtor === 'function' && typeof createObjectURL === 'function' &&
                typeof revokeObjectURL === 'function' && getCsrfToken() !== '' && Date.now() >= providerRetryAfter;
        }

        function requestBody(text, locale) {
            const language = normalizeLocale(locale).split('-')[0] || 'en';
            if (typeof FormDataCtor === 'function') {
                const body = new FormDataCtor();
                body.append('_token', getCsrfToken());
                body.append('speech_text', text);
                body.append('language', language);
                return body;
            }

            const body = new URLSearchParams();
            body.set('_token', getCsrfToken());
            body.set('speech_text', text);
            body.set('language', language);
            return body;
        }

        function fetchProviderAudio(text, config = {}) {
            const normalized = normalizeText(text);
            const locale = normalizeLocale(config.locale || defaultLocale);
            const key = keyFor(normalized, locale);
            if (config.providerVoiceover === false) {
                return Promise.reject(namedError('ProviderVoiceError', 'AI tutorial voice is disabled for this tour.'));
            }
            const cached = cachedAudio(key);
            if (cached) return Promise.resolve(cached);
            if (inflight.has(key)) return inflight.get(key).promise;
            if (!canUseProvider()) {
                return Promise.reject(namedError('ProviderVoiceError', 'AI tutorial voice is unavailable.'));
            }

            const controller = typeof AbortControllerCtor === 'function' ? new AbortControllerCtor() : null;
            const entry = { controller, promise: null };
            let timeoutId = null;
            const timeout = new Promise((_, reject) => {
                timeoutId = setTimer(() => {
                    controller?.abort();
                    reject(namedError('ProviderVoiceError', 'AI tutorial voice timed out.'));
                }, providerTimeoutMs);
            });
            const request = Promise.resolve().then(() => fetchImpl(endpoint, {
                method: 'POST',
                body: requestBody(normalized, locale),
                headers: {
                    Accept: 'audio/*',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                signal: controller?.signal,
            })).then(async (response) => {
                if (controller?.signal?.aborted) throw namedError('AbortError', 'AI tutorial voice request was stopped.');
                if (!response?.ok || typeof response.blob !== 'function') {
                    throw namedError('ProviderVoiceError', `AI tutorial voice request failed (${response?.status || 'network'}).`);
                }
                const blob = await response.blob();
                if (controller?.signal?.aborted) throw namedError('AbortError', 'AI tutorial voice request was stopped.');
                if (!blob || Number(blob.size) <= 0 || !String(blob.type || '').toLowerCase().startsWith('audio/')) {
                    throw namedError('ProviderVoiceError', 'AI tutorial voice returned invalid audio.');
                }
                return blob;
            });

            entry.promise = Promise.race([request, timeout])
                .then((blob) => {
                    if (destroyed) throw namedError('AbortError', 'AI tutorial voice runtime was destroyed.');
                    providerRetryAfter = 0;
                    return rememberAudio(key, blob);
                })
                .catch((error) => {
                    if (error?.name !== 'AbortError') providerRetryAfter = Date.now() + providerFailureCooldownMs;
                    throw error;
                })
                .finally(() => {
                    if (timeoutId !== null) clearTimer(timeoutId);
                    if (inflight.get(key) === entry) inflight.delete(key);
                });
            inflight.set(key, entry);
            return entry.promise;
        }

        function voiceMatchesLocale(voice, locale) {
            const voiceLocale = String(voice?.lang || '').toLowerCase();
            const target = String(locale || '').toLowerCase();
            const language = target.split('-')[0];
            return voiceLocale === target || voiceLocale.startsWith(`${language}-`) || voiceLocale === language;
        }

        function looksNatural(voice) {
            return /google|premium|natural|siri|microsoft|enhanced|neural/i.test(String(voice?.name || ''));
        }

        function looksMale(voice) {
            return /\b(male|man|boy|david|mark|george|daniel|fred|tom|ralph|bruce|arthur|albert|jorge|diego|carlos|miguel|juan|paul|ryan|liam|brian|guy|aaron|eric|nathan|christopher|jacob|justin|matthew|joey|onyx|echo)\b/i.test(String(voice?.name || ''));
        }

        function chooseFallbackVoice(locale) {
            if (!speechSynthesis || typeof speechSynthesis.getVoices !== 'function') return null;
            const voices = speechSynthesis.getVoices() || [];
            if (!voices.length) return preferredVoice;
            preferredVoice = voices.find((voice) => voiceMatchesLocale(voice, locale) && looksNatural(voice) && !looksMale(voice)) ||
                voices.find((voice) => voiceMatchesLocale(voice, locale) && !looksMale(voice)) ||
                voices.find((voice) => voiceMatchesLocale(voice, locale)) ||
                voices.find((voice) => looksNatural(voice) && !looksMale(voice)) ||
                voices.find((voice) => !looksMale(voice)) || voices[0] || null;
            return preferredVoice;
        }

        function browserFallback(text, config = {}) {
            if (typeof options.fallbackSpeak === 'function') {
                return Promise.resolve().then(() => options.fallbackSpeak(text, config)).then(Boolean);
            }
            if (!speechSynthesis || typeof speechSynthesis.speak !== 'function' || typeof UtteranceCtor !== 'function') {
                return Promise.resolve(false);
            }

            try {
                speechSynthesis.cancel?.();
                const utterance = new UtteranceCtor(text);
                utterance.lang = normalizeLocale(config.locale || defaultLocale);
                const rate = config.voiceoverRate ?? config.rate;
                const pitch = config.voiceoverPitch ?? config.pitch;
                const volume = config.voiceoverVolume ?? config.volume;
                utterance.rate = Number.isFinite(Number(rate)) ? Number(rate) : 0.9;
                utterance.pitch = Number.isFinite(Number(pitch)) ? Number(pitch) : 1;
                utterance.volume = Number.isFinite(Number(volume)) ? Number(volume) : 1;
                const voice = chooseFallbackVoice(utterance.lang);
                if (voice) utterance.voice = voice;
                utterance.onend = utterance.onerror = () => {
                    if (activeFallbackUtterance === utterance) activeFallbackUtterance = null;
                };
                activeFallbackUtterance = utterance;
                speechSynthesis.speak(utterance);
                return Promise.resolve(true);
            } catch (error) {
                reportError(error, 'fallback');
                return Promise.resolve(false);
            }
        }

        function cleanupActivePlayback({ pause = true } = {}) {
            const playback = activePlayback;
            if (!playback) return;
            activePlayback = null;
            playback.audio.removeEventListener?.('ended', playback.onEnded);
            playback.audio.removeEventListener?.('error', playback.onError);
            if (pause) playback.audio.pause?.();
            playback.audio.removeAttribute?.('src');
            playback.audio.load?.();
            revokeObjectURL(playback.url);
        }

        async function speak(text, config = {}) {
            const normalized = normalizeText(text);
            if (!normalized || destroyed) return { source: 'none' };

            const token = ++sequence;
            cleanupActivePlayback();
            speechSynthesis?.cancel?.();
            activeFallbackUtterance = null;
            let fallbackPromise = null;
            const fallbackOnce = () => {
                if (token !== sequence || destroyed) return Promise.resolve({ source: 'stale' });
                if (fallbackPromise) return fallbackPromise;
                fallbackPromise = browserFallback(normalized, config)
                    .then((spoken) => ({ source: spoken ? 'fallback' : 'none' }))
                    .catch((error) => {
                        reportError(error, 'fallback');
                        return { source: 'none' };
                    });
                return fallbackPromise;
            };

            const locale = normalizeLocale(config.locale || defaultLocale);
            const cacheKey = keyFor(normalized, locale);
            let blob = config.providerVoiceover === false ? null : cachedAudio(cacheKey);
            try {
                if (!blob) blob = await fetchProviderAudio(normalized, config);
            } catch (error) {
                if (token !== sequence || destroyed) return { source: 'stale' };
                if (error?.name !== 'AbortError') reportError(error, 'provider');
                return fallbackOnce();
            }
            if (token !== sequence || destroyed) return { source: 'stale' };

            let url;
            let audio;
            try {
                url = createObjectURL(blob);
                audio = new AudioCtor(url);
            } catch (error) {
                if (url) revokeObjectURL(url);
                reportError(error, 'audio-create');
                return fallbackOnce();
            }

            const playbackRate = config.providerVoiceoverRate ?? config.playbackRate;
            audio.preload = 'auto';
            audio.playsInline = true;
            audio.defaultPlaybackRate = Number.isFinite(Number(playbackRate)) ? Number(playbackRate) : 1;
            audio.playbackRate = audio.defaultPlaybackRate;
            let watchdogId = null;
            const onEnded = () => {
                if (activePlayback?.audio === audio) cleanupActivePlayback({ pause: false });
            };
            const onError = () => {
                if (activePlayback?.audio !== audio || token !== sequence || destroyed) return;
                cleanupActivePlayback();
                void fallbackOnce();
            };
            activePlayback = { audio, url, onEnded, onError };
            audio.addEventListener?.('ended', onEnded, { once: true });
            audio.addEventListener?.('error', onError, { once: true });

            try {
                const playResult = audio.play();
                await Promise.race([
                    Promise.resolve(playResult),
                    new Promise((_, reject) => {
                        watchdogId = setTimer(() => reject(namedError('ProviderVoiceError', 'AI tutorial audio could not start.')), playbackStartTimeoutMs);
                    }),
                ]);
                if (watchdogId !== null) clearTimer(watchdogId);
                if (token !== sequence || destroyed) {
                    if (activePlayback?.audio === audio) cleanupActivePlayback();
                    return { source: 'stale' };
                }
                return { source: 'provider' };
            } catch (error) {
                if (watchdogId !== null) clearTimer(watchdogId);
                if (activePlayback?.audio === audio) cleanupActivePlayback();
                if (token !== sequence || destroyed) return { source: 'stale' };
                reportError(error, 'audio-start');
                return fallbackOnce();
            }
        }

        function prefetch(texts, config = {}) {
            if (config.providerVoiceover === false || !canUseProvider()) return Promise.resolve([]);
            const list = (Array.isArray(texts) ? texts : [texts]).map(normalizeText).filter(Boolean);
            return Promise.all(list.map((text) => fetchProviderAudio(text, config).catch((error) => {
                if (error?.name !== 'AbortError') reportError(error, 'prefetch');
                return null;
            })));
        }

        function stop({ abortPending = false } = {}) {
            sequence += 1;
            cleanupActivePlayback();
            speechSynthesis?.cancel?.();
            activeFallbackUtterance = null;
            if (abortPending) {
                for (const entry of inflight.values()) entry.controller?.abort();
                inflight.clear();
            }
        }

        function clearCache() {
            audioCache.clear();
        }

        function destroy() {
            if (destroyed) return;
            stop({ abortPending: true });
            clearCache();
            destroyed = true;
        }

        return {
            speak,
            prefetch,
            preload: prefetch,
            stop,
            clearCache,
            destroy,
            supportsProvider: canUseProvider,
        };
    }

    globalScope.SpeakReadyOnboardingVoiceover = Object.freeze({ create });
})(typeof window !== 'undefined' ? window : globalThis);
