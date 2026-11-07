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
    addEventListener(type, callback) {
        const callbacks = this.listeners.get(type) || [];
        callbacks.push(callback);
        this.listeners.set(type, callbacks);
    }
    dispatchEvent(event) {
        if (!event.target) event.target = this;
        for (const callback of this.listeners.get(event.type) || []) callback(event);
        if (event.bubbles && !event.propagationStopped && this.parentNode) this.parentNode.dispatchEvent(event);
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
        if (selector.startsWith('button, input,')) return this.focusables || [];
        return [];
    }
    getBoundingClientRect() { return { left: 100, right: 500, top: 100, bottom: 500 }; }
}

function makePage({ native = true } = {}) {
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
    const form = add('setupForm', document.body);
    const trigger = add('interviewerAvatarTrigger', form);
    const previewImage = add('interviewerAvatarSelectedImage', trigger);
    const previewName = add('interviewerAvatarSelectedName', trigger);
    const previewDescription = add('interviewerAvatarSelectedDescription', trigger);
    const dialog = add('interviewerAvatarDialog', form);
    const group = add('interviewerAvatarGroup', dialog);
    const close = add('interviewerAvatarClose', dialog);
    const cancel = add('interviewerAvatarCancel', dialog);
    const confirm = add('interviewerAvatarConfirm', dialog);
    const radios = [];
    for (const [value, name] of [['friendly_recruiter', 'Friendly Recruiter'], ['role_specialist', 'Role Specialist'], ['senior_leader', 'Senior Leader']]) {
        const card = add(`card-${value}`, group);
        const radio = add(`radio-${value}`, card);
        radio.card = card;
        radio.value = value;
        radio.checked = value === 'friendly_recruiter';
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
        radios.push(radio);
    }
    group.radios = radios;
    dialog.focusables = [close, ...radios, cancel, confirm];
    if (native) {
        dialog.showModal = () => dialog.setAttribute('open', '');
        dialog.close = () => {
            dialog.removeAttribute('open');
            dialog.dispatchEvent(new FakeEvent('close'));
        };
    }
    const window = { setTimeout: (callback) => callback() };
    vm.runInNewContext(source, { document, window, Event: FakeEvent });
    const fire = (node, type, options = {}) => {
        const event = new FakeEvent(type, options);
        node.dispatchEvent(event);
        return event;
    };
    const choose = (value) => {
        for (const radio of radios) radio.checked = radio.value === value;
        fire(radios.find((radio) => radio.value === value), 'change', { bubbles: true });
    };
    return { document, form, trigger, previewImage, previewName, previewDescription, dialog, group, confirm, cancel, radios, fire, choose };
}

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

test('Cancel, Escape, and backdrop clicks restore the committed selection', () => {
    const page = makePage();
    page.fire(page.document, 'DOMContentLoaded');
    let changeCount = 0;
    page.form.addEventListener('change', () => changeCount++);
    for (const cancelWith of ['button', 'escape', 'backdrop']) {
        page.fire(page.trigger, 'click');
        page.choose('senior_leader');
        const beforeCancel = changeCount;
        if (cancelWith === 'button') page.fire(page.cancel, 'click');
        if (cancelWith === 'escape') page.fire(page.document, 'keydown', { key: 'Escape' });
        if (cancelWith === 'backdrop') page.fire(page.dialog, 'click', { clientX: 50, clientY: 50 });
        assert.equal(page.radios[0].checked, true);
        assert.equal(page.previewName.textContent, 'Friendly Recruiter');
        assert.ok(changeCount > beforeCancel, `${cancelWith} must notify setup listeners`);
        assert.equal(page.dialog.open, false);
    }
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
    const backdropClick = page.fire(page.form, 'click', { bubbles: true });
    assert.equal(backdropClick.defaultPrevented, true);
    assert.equal(backdropClick.propagationStopped, true);
    assert.equal(page.dialog.parentNode, page.form);
    assert.equal(page.radios[2].checked, true);
});

test('both setup forms keep moved radio options associated with the setup form', () => {
    for (const layout of ['desktop', 'mobile']) {
        const view = readFileSync(join(root, `resources/views/${layout}/interview/setup.blade.php`), 'utf8');
        assert.match(view, /name="interviewer_avatar" form="setupForm"/);
    }
});
