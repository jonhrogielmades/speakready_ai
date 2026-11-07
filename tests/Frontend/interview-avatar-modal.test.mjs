import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const source = readFileSync(join(root, 'public/js/interview-setup-avatar-modal.js'), 'utf8');

class FakeEvent {
    constructor(type, options = {}) {
        Object.assign(this, options);
        this.type = type;
        this.bubbles = Boolean(options.bubbles);
        this.defaultPrevented = false;
        this.propagationStopped = false;
    }
    preventDefault() { this.defaultPrevented = true; }
    stopPropagation() { this.propagationStopped = true; }
}

class FakeNode {
    constructor(id = '') {
        this.id = id;
        this.children = [];
        this.parentNode = null;
        this.listeners = new Map();
        this.attributes = new Map();
        this.classes = new Set();
        this.classList = {
            add: (name) => this.classes.add(name),
            remove: (name) => this.classes.delete(name),
            contains: (name) => this.classes.has(name),
        };
        this.dataset = {};
        this.hidden = false;
    }
    addEventListener(type, callback, options = {}) {
        const callbacks = this.listeners.get(type) || [];
        callbacks.push({ callback, capture: options === true || Boolean(options.capture) });
        this.listeners.set(type, callbacks);
    }
    dispatchEvent(event) {
        if (!event.target) event.target = this;
        const ancestors = [];
        for (let node = this.parentNode; node; node = node.parentNode) ancestors.push(node);
        const notify = (node, capture) => {
            for (const listener of node.listeners.get(event.type) || []) {
                if (listener.capture === capture) listener.callback(event);
            }
        };
        for (const node of ancestors.reverse()) {
            notify(node, true);
            if (event.propagationStopped) return !event.defaultPrevented;
        }
        notify(this, true);
        notify(this, false);
        if (event.bubbles && !event.propagationStopped) {
            for (const node of ancestors.reverse()) {
                notify(node, false);
                if (event.propagationStopped) break;
            }
        }
        return !event.defaultPrevented;
    }
    setAttribute(name, value) {
        this.attributes.set(name, String(value));
        if (name === 'open') this.open = true;
    }
    getAttribute(name) { return this.attributes.get(name) ?? null; }
    hasAttribute(name) { return this.attributes.has(name); }
    removeAttribute(name) {
        this.attributes.delete(name);
        if (name === 'open') this.open = false;
    }
    appendChild(child) {
        child.remove();
        this.children.push(child);
        child.parentNode = this;
        return child;
    }
    insertBefore(child, sibling) {
        child.remove();
        const index = this.children.indexOf(sibling);
        this.children.splice(index < 0 ? this.children.length : index, 0, child);
        child.parentNode = this;
        return child;
    }
    remove() {
        if (!this.parentNode) return;
        const index = this.parentNode.children.indexOf(this);
        if (index >= 0) this.parentNode.children.splice(index, 1);
        this.parentNode = null;
    }
    contains(node) {
        for (let current = node; current; current = current.parentNode) {
            if (current === this) return true;
        }
        return false;
    }
    focus() { this.ownerDocument.activeElement = this; }
    closest(selector) { return selector === '.interviewer-avatar-choice' ? this.card : null; }
    querySelector(selector) { return this.queries?.get(selector) || null; }
    querySelectorAll(selector) {
        if (selector === 'input[type="radio"][name="interviewer_avatar"]') return this.radios || [];
        if (selector === '.interviewer-avatar-voice-preview') return this.voiceButtons || [];
        if (selector.startsWith('button, input,')) return this.focusables || [];
        return [];
    }
    getBoundingClientRect() { return { left: 100, right: 500, top: 100, bottom: 500 }; }
}

function makePage({ native = true, fetchImpl, playImpl, initialSelection = 'friendly_recruiter' } = {}) {
    const document = new FakeNode('document');
    const elements = new Map();
    const add = (id, parent) => {
        const node = new FakeNode(id);
        node.ownerDocument = document;
        if (parent) parent.appendChild(node);
        elements.set(id, node);
        return node;
    };
    document.readyState = 'loading';
    document.createComment = () => {
        const comment = new FakeNode();
        comment.ownerDocument = document;
        return comment;
    };
    document.getElementById = (id) => elements.get(id) || null;
    document.documentElement = add('html', document);
    document.body = add('body', document.documentElement);
    const csrfMeta = add('csrfMeta', document.body);
    csrfMeta.setAttribute('content', 'test-csrf-token');
    document.queries = new Map([['meta[name="csrf-token"]', csrfMeta]]);
    const form = add('setupForm', document.body);
    const trigger = add('interviewerAvatarTrigger', form);
    const backgroundAction = add('backgroundAction', form);
    const previewImage = add('interviewerAvatarSelectedImage', trigger);
    const placeholderIcon = add('interviewerAvatarPlaceholderIcon', trigger);
    const previewName = add('interviewerAvatarSelectedName', trigger);
    const previewDescription = add('interviewerAvatarSelectedDescription', trigger);
    const triggerAction = add('interviewerAvatarTriggerAction', trigger);
    previewImage.hidden = initialSelection === null;
    placeholderIcon.hidden = initialSelection !== null;
    const dialog = add('interviewerAvatarDialog', form);
    dialog.dataset.voicePreviewUrl = '/interview/avatar-voice-preview';
    const group = add('interviewerAvatarGroup', dialog);
    const voiceStatus = add('interviewerAvatarVoiceStatus', dialog);
    const cancel = add('interviewerAvatarCancel', dialog);
    const confirm = add('interviewerAvatarConfirm', dialog);
    const radios = [];
    const voiceButtons = [];
    for (const [value, name] of [
        ['friendly_recruiter', 'Friendly Recruiter'],
        ['role_specialist', 'Role Specialist'],
        ['senior_leader', 'Senior Leader'],
        ['structured_hr', 'Structured HR'],
        ['hiring_manager', 'Hiring Manager'],
        ['panel_interviewer', 'Panel Interviewer'],
    ]) {
        const card = add(`card-${value}`, group);
        const radio = add(`radio-${value}`, card);
        radio.card = card;
        radio.value = value;
        radio.checked = value === initialSelection;
        radio.dataset.summaryLabel = name;
        const nameNode = add(`name-${value}`, card);
        nameNode.textContent = name;
        const description = add(`description-${value}`, card);
        description.textContent = `${name} interview style.`;
        const image = add(`image-${value}`, card);
        image.setAttribute('src', `/${value}.png`);
        const webp = add(`webp-${value}`, card);
        webp.setAttribute('srcset', `/${value}.webp`);
        card.queries = new Map([
            ['.interviewer-avatar-name', nameNode],
            ['.interviewer-avatar-description', description],
            ['.interviewer-avatar-portrait img', image],
            ['.interviewer-avatar-portrait source[type="image/webp"]', webp],
        ]);
        const voiceButton = add(`voice-${value}`, card);
        voiceButton.dataset.avatar = value;
        voiceButton.dataset.avatarLabel = name;
        const voiceLabel = add(`voice-label-${value}`, voiceButton);
        voiceButton.queries = new Map([['.interviewer-avatar-voice-preview-label', voiceLabel]]);
        voiceButtons.push(voiceButton);
        radios.push(radio);
    }
    group.radios = radios;
    dialog.voiceButtons = voiceButtons;
    dialog.focusables = [...radios, ...voiceButtons, cancel, confirm];
    if (native) {
        dialog.showModal = () => dialog.setAttribute('open', '');
        dialog.close = () => {
            dialog.removeAttribute('open');
            dialog.dispatchEvent(new FakeEvent('close'));
        };
    }
    const timers = [];
    const window = {
        setTimeout(callback, delay = 0) {
            if (delay === 0) { callback(); return 0; }
            const timer = { callback, delay, cleared: false };
            timers.push(timer);
            return timer;
        },
        clearTimeout(timer) { if (timer) timer.cleared = true; },
        addEventListener() {},
    };
    const fetchCalls = [];
    const audioInstances = [];
    const revokedUrls = [];
    class FakeAudio extends FakeNode {
        constructor(src) {
            super('audio');
            this.src = src;
            this.paused = false;
            this.playCount = 0;
            audioInstances.push(this);
        }
        play() {
            this.playCount++;
            this.paused = false;
            return playImpl ? playImpl(this) : Promise.resolve();
        }
        pause() { this.paused = true; }
    }
    const mockFetch = (url, options) => {
        fetchCalls.push({ url, options });
        return fetchImpl ? fetchImpl(url, options) : Promise.resolve({
            ok: true,
            status: 200,
            headers: { get: () => 'audio/wav' },
            blob: async () => ({ size: 40 }),
        });
    };
    const URL = {
        createObjectURL: () => 'blob:voice-preview',
        revokeObjectURL: (url) => revokedUrls.push(url),
    };
    vm.runInNewContext(source, { document, window, Event: FakeEvent, fetch: mockFetch, Audio: FakeAudio, AbortController, URL });
    const fire = (node, type, options = {}) => {
        const event = new FakeEvent(type, options);
        node.dispatchEvent(event);
        return event;
    };
    const choose = (value) => {
        for (const radio of radios) radio.checked = radio.value === value;
        fire(radios.find((radio) => radio.value === value), 'change', { bubbles: true });
    };
    return { document, form, trigger, backgroundAction, previewImage, placeholderIcon, previewName, previewDescription, triggerAction, dialog, group, confirm, cancel, radios, voiceButtons, voiceStatus, fetchCalls, audioInstances, revokedUrls, timers, fire, choose };
}

const settlePromises = () => new Promise((resolve) => setImmediate(resolve));

test('unselected setup shows a clear prompt and only changes after confirming an interviewer', () => {
    const page = makePage({ initialSelection: null });
    page.fire(page.document, 'DOMContentLoaded');
    assert.equal(page.radios.some((radio) => radio.checked), false);
    assert.equal(page.previewImage.hidden, true);
    assert.equal(page.previewImage.getAttribute('src'), null);
    assert.equal(page.placeholderIcon.hidden, false);
    assert.equal(page.previewName.textContent, 'Choose an AI interviewer');
    assert.equal(page.previewDescription.textContent, 'Select a portrait and voice to continue');
    assert.equal(page.triggerAction.textContent, 'Choose');
    assert.equal(page.trigger.dataset.selectedAvatar, undefined);

    page.fire(page.trigger, 'click');
    page.choose('role_specialist');
    page.fire(page.cancel, 'click');
    assert.equal(page.radios.some((radio) => radio.checked), false, 'cancel must not silently choose an avatar');
    assert.equal(page.previewName.textContent, 'Choose an AI interviewer');
    assert.equal(page.previewImage.hidden, true);

    page.fire(page.trigger, 'click');
    page.choose('role_specialist');
    page.fire(page.confirm, 'click');
    assert.equal(page.previewName.textContent, 'Role Specialist');
    assert.equal(page.previewImage.hidden, false);
    assert.equal(page.placeholderIcon.hidden, true);
    assert.equal(page.triggerAction.textContent, 'Change');
    assert.equal(page.trigger.dataset.selectedAvatar, 'role_specialist');
});

test('modal initializes from restored selection, commits a new choice, and restores focus', () => {
    const page = makePage();
    page.choose('role_specialist'); // Draft restoration happens before DOM ready.
    page.fire(page.document, 'DOMContentLoaded');
    assert.equal(page.previewName.textContent, 'Role Specialist');
    assert.equal(page.previewImage.src, '/role_specialist.webp');

    page.fire(page.trigger, 'click');
    assert.equal(page.dialog.open, true);
    assert.equal(page.document.activeElement.value, 'role_specialist');
    assert.equal(page.document.body.classList.contains('interviewer-avatar-modal-open'), true);
    page.choose('senior_leader');
    assert.equal(page.previewName.textContent, 'Role Specialist');
    page.fire(page.confirm, 'click');
    assert.equal(page.previewName.textContent, 'Senior Leader');
    assert.equal(page.previewDescription.textContent, 'Senior Leader interview style.');
    assert.equal(page.dialog.open, false);
    assert.equal(page.document.activeElement, page.trigger);
    assert.equal(page.document.body.classList.contains('interviewer-avatar-modal-open'), false);
});

test('Cancel and Escape restore the committed selection', () => {
    const page = makePage();
    page.fire(page.document, 'DOMContentLoaded');
    let changeCount = 0;
    page.form.addEventListener('change', () => changeCount++);
    for (const cancelWith of ['button', 'escape']) {
        page.fire(page.trigger, 'click');
        page.choose('senior_leader');
        const beforeCancel = changeCount;
        if (cancelWith === 'button') page.fire(page.cancel, 'click');
        if (cancelWith === 'escape') page.fire(page.document, 'keydown', { key: 'Escape' });
        assert.equal(page.radios[0].checked, true);
        assert.equal(page.previewName.textContent, 'Friendly Recruiter');
        assert.ok(changeCount > beforeCancel, `${cancelWith} must notify setup listeners`);
        assert.equal(page.dialog.open, false);
    }
});

test('native backdrop clicks are blocked and leave the pending selection open', () => {
    const page = makePage();
    page.fire(page.document, 'DOMContentLoaded');
    page.fire(page.trigger, 'click');
    page.choose('senior_leader');

    const backdropClick = page.fire(page.dialog, 'click', { bubbles: true, clientX: 50, clientY: 50 });
    assert.equal(backdropClick.defaultPrevented, true);
    assert.equal(backdropClick.propagationStopped, true);
    assert.equal(page.dialog.open, true);
    assert.equal(page.radios[2].checked, true, 'the new choice should remain pending');
    assert.equal(page.previewName.textContent, 'Friendly Recruiter', 'the committed choice should stay unchanged');
    assert.equal(page.document.body.classList.contains('interviewer-avatar-modal-open'), true);
});

test('Enter on a focused radio commits without submitting the setup form', () => {
    const page = makePage();
    page.fire(page.document, 'DOMContentLoaded');
    page.fire(page.trigger, 'click');
    page.choose('role_specialist');
    const enter = page.fire(page.group, 'keydown', { key: 'Enter', target: page.radios[1] });
    assert.equal(enter.defaultPrevented, true);
    assert.equal(enter.propagationStopped, true);
    assert.equal(page.previewName.textContent, 'Role Specialist');
    assert.equal(page.dialog.open, false);
});

test('fallback dialog escapes clipped form container and still updates draft listeners', () => {
    const page = makePage({ native: false });
    page.fire(page.document, 'DOMContentLoaded');
    let formChanges = 0;
    page.form.addEventListener('change', () => formChanges++);
    page.fire(page.trigger, 'click');
    assert.equal(page.dialog.parentNode, page.document.body);
    assert.equal(page.dialog.hasAttribute('open'), true);
    assert.equal(page.dialog.classList.contains('is-fallback'), true);
    assert.equal(page.document.body.classList.contains('interviewer-avatar-fallback-backdrop'), true);
    page.choose('senior_leader');
    assert.ok(formChanges > 0);
    page.fire(page.confirm, 'click');
    assert.equal(page.dialog.parentNode, page.form);
    assert.equal(page.dialog.hasAttribute('open'), false);
    assert.equal(page.dialog.classList.contains('is-fallback'), false);
    assert.equal(page.document.body.classList.contains('interviewer-avatar-fallback-backdrop'), false);
    assert.equal(page.previewName.textContent, 'Senior Leader');

    page.fire(page.trigger, 'click');
    page.choose('role_specialist');
    let backgroundClicks = 0;
    page.backgroundAction.addEventListener('click', () => backgroundClicks++);
    const backgroundClick = page.fire(page.backgroundAction, 'click', { bubbles: true });
    assert.equal(backgroundClick.defaultPrevented, true);
    assert.equal(backgroundClick.propagationStopped, true);
    assert.equal(backgroundClicks, 0, 'the background control must not receive the click');
    assert.equal(page.dialog.parentNode, page.document.body);
    assert.equal(page.dialog.open, true);
    assert.equal(page.radios[1].checked, true, 'the new choice should remain pending');
    assert.equal(page.previewName.textContent, 'Senior Leader', 'the committed choice should stay unchanged');
    assert.equal(page.document.body.classList.contains('interviewer-avatar-fallback-backdrop'), true);
});

test('both setup forms keep moved radio options associated with the setup form', () => {
    for (const layout of ['desktop', 'mobile']) {
        const view = readFileSync(join(root, `resources/views/${layout}/interview/setup.blade.php`), 'utf8');
        assert.match(view, /name="interviewer_avatar" form="setupForm"/);
        assert.match(view, /data-voice-preview-url="\{\{ route\('interview\.avatarVoicePreview'\) \}\}"/);
        assert.match(view, /class="interviewer-avatar-voice-preview"/);
        assert.doesNotMatch(view, /id="interviewerAvatarClose"/);
        assert.match(view, /id="interviewerAvatarCancel"/);
    }
});

test('voice preview sends the selected avatar to the server without selecting it or submitting the form', async () => {
    const page = makePage();
    page.fire(page.document, 'DOMContentLoaded');
    page.fire(page.trigger, 'click');
    let submits = 0;
    page.form.addEventListener('submit', () => submits++);

    const previewClick = page.fire(page.voiceButtons[5], 'click', { bubbles: true });
    await settlePromises();
    assert.equal(previewClick.defaultPrevented, true);
    assert.equal(previewClick.propagationStopped, true);
    assert.equal(page.radios[0].checked, true);
    assert.equal(submits, 0);
    assert.equal(page.fetchCalls.length, 1);
    assert.equal(page.fetchCalls[0].url, '/interview/avatar-voice-preview');
    assert.deepEqual(JSON.parse(page.fetchCalls[0].options.body), { interviewer_avatar: 'panel_interviewer' });
    assert.equal(page.fetchCalls[0].options.headers['X-CSRF-TOKEN'], 'test-csrf-token');
    assert.equal(page.fetchCalls[0].options.headers.Accept, 'audio/*');
    assert.equal(page.voiceButtons[5].dataset.state, 'playing');
    assert.equal(page.audioInstances[0].playCount, 1);

    page.fire(page.voiceButtons[5], 'click');
    assert.equal(page.audioInstances[0].paused, true);
    assert.deepEqual(page.revokedUrls, ['blob:voice-preview']);
    assert.equal(page.voiceButtons[5].dataset.state, 'idle');
});

test('voice preview plays OpenAI MP3 audio when Gemini is unavailable', async () => {
    const page = makePage({ fetchImpl: async () => ({
        ok: true,
        status: 200,
        headers: { get: () => 'audio/mpeg' },
        blob: async () => ({ size: 512, type: 'audio/mpeg' }),
    }) });
    page.fire(page.document, 'DOMContentLoaded');
    page.fire(page.trigger, 'click');
    page.fire(page.voiceButtons[5], 'click');
    await settlePromises();

    assert.equal(page.fetchCalls[0].options.headers.Accept, 'audio/*');
    assert.equal(page.audioInstances.length, 1);
    assert.equal(page.audioInstances[0].playCount, 1);
    assert.equal(page.voiceButtons[5].dataset.state, 'playing');
});

test('slow voice generation still lets the user play the fetched sample after autoplay is blocked', async () => {
    let playAttempts = 0;
    const page = makePage({ playImpl: () => {
        playAttempts++;
        if (playAttempts === 1) return Promise.reject(Object.assign(new Error('User activation expired'), { name: 'NotAllowedError' }));
        return Promise.resolve();
    } });
    page.fire(page.document, 'DOMContentLoaded');
    page.fire(page.trigger, 'click');
    page.fire(page.voiceButtons[0], 'click');
    await settlePromises();
    assert.equal(page.voiceButtons[0].dataset.state, 'ready');
    assert.match(page.voiceStatus.textContent, /Tap Play/);
    assert.equal(page.revokedUrls.length, 0);

    page.fire(page.voiceButtons[0], 'click');
    await settlePromises();
    assert.equal(page.voiceButtons[0].dataset.state, 'playing');
    assert.equal(page.fetchCalls.length, 1, 'second tap should reuse the fetched audio');
    page.fire(page.cancel, 'click');
    assert.equal(page.audioInstances[0].paused, true);
    assert.deepEqual(page.revokedUrls, ['blob:voice-preview']);
});

test('voice preview reports provider errors and a 55-second timeout', async () => {
    const unavailable = makePage({ fetchImpl: async () => ({
        ok: false,
        status: 503,
        json: async () => ({ error: 'AI voice preview is not available.' }),
    }) });
    unavailable.fire(unavailable.document, 'DOMContentLoaded');
    unavailable.fire(unavailable.trigger, 'click');
    unavailable.fire(unavailable.voiceButtons[0], 'click');
    await settlePromises();
    assert.equal(unavailable.voiceStatus.textContent, 'AI voice preview is not available.');
    assert.equal(unavailable.voiceButtons[0].dataset.state, 'idle');

    const timeout = makePage({ fetchImpl: (_url, options) => new Promise((_resolve, reject) => {
        options.signal.addEventListener('abort', () => reject(Object.assign(new Error('Aborted'), { name: 'AbortError' })));
    }) });
    timeout.fire(timeout.document, 'DOMContentLoaded');
    timeout.fire(timeout.trigger, 'click');
    timeout.fire(timeout.voiceButtons[0], 'click');
    const timer = timeout.timers.find((entry) => entry.delay === 55000);
    assert.ok(timer);
    timer.callback();
    await settlePromises();
    assert.match(timeout.voiceStatus.textContent, /took too long/);
    assert.equal(timeout.voiceButtons[0].dataset.state, 'idle');
});
