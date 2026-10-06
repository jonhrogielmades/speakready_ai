(function () {
 const DEFAULT_STORAGE_KEY = 'speakready.interview.setupDraft';
 const SAVE_DEBOUNCE_MS = 140;
 const root = window;

 function configuredStorageKey(explicitKey, form) {
 if (explicitKey) return explicitKey;
 if (form?.dataset?.setupDraftKey) return form.dataset.setupDraftKey;
 const configKey = root.SpeakReadyInterviewSetupDraftConfig?.storageKey;
 return configKey || DEFAULT_STORAGE_KEY;
 }

 function storageAvailable() {
 try {
 const probeKey = 'speakready.interview.storageProbe';
 root.localStorage.setItem(probeKey, '1');
 root.localStorage.removeItem(probeKey);
 return true;
 } catch (error) {
 return false;
 }
 }

 const canUseStorage = storageAvailable();

 function controlsForName(form, name) {
 return Array.from(form.elements || []).filter((element) => element.name === name);
 }

 function formControlNames(form) {
 return Array.from(form.elements || [])
 .map((element) => element.name || '')
 .filter((name) => name && name !== '_token')
 .filter((name, index, names) => names.indexOf(name) === index);
 }

 function selectedRadioValue(controls) {
 return controls.find((control) => control.checked)?.value || '';
 }

 function serializeControlValue(controls) {
 const first = controls[0];
 if (!first) return '';

 const tagName = String(first.tagName || '').toLowerCase();
 const type = String(first.type || '').toLowerCase();

 if (type === 'radio') {
 return selectedRadioValue(controls);
 }

 if (type === 'checkbox') {
 const checkedValues = controls.filter((control) => control.checked).map((control) => control.value);
 return controls.length > 1 || first.name.endsWith('[]') ? checkedValues : checkedValues[0] || '';
 }

 if (tagName === 'select' && first.multiple) {
 return Array.from(first.selectedOptions || []).map((option) => option.value);
 }

 return first.value || '';
 }

 function serializeForm(form, options) {
 const values = {};
 formControlNames(form).forEach((name) => {
 values[name] = serializeControlValue(controlsForName(form, name));
 });

 const targetPosition = controlsForName(form, 'target_position')[0];

 return {
 version: 1,
 saved_at: new Date().toISOString(),
 active_step_index: Number.isFinite(Number(options.activeStepIndex)) ? Number(options.activeStepIndex) : null,
 visited_step_ids: Array.isArray(options.visitedStepIds) ? options.visitedStepIds.filter(Boolean) : [],
 values,
 meta: {
 target_position_kind: targetPosition?.dataset?.targetKind || '',
 target_position_label: targetPosition?.dataset?.selectedTarget || targetPosition?.value || '',
 },
 };
 }

 function applyTargetPositionMeta(control, value, draft) {
 if (!control || control.name !== 'target_position') return;

 const nextValue = String(value || '').trim();
 control.dataset.selectedTarget = nextValue;
 control.dataset.targetKind = draft?.meta?.target_position_kind || control.dataset.targetKind || 'job';

 if (nextValue) {
 control.setAttribute('value', nextValue);
 } else {
 control.removeAttribute('value');
 }
 }

 function restoreControlValue(controls, value, draft) {
 const first = controls[0];
 if (!first) return;

 const tagName = String(first.tagName || '').toLowerCase();
 const type = String(first.type || '').toLowerCase();

 if (type === 'radio') {
 controls.forEach((control) => {
 control.checked = String(control.value) === String(value || '');
 });
 return;
 }

 if (type === 'checkbox') {
 const selectedValues = Array.isArray(value) ? value.map(String) : [String(value || '')];
 controls.forEach((control) => {
 control.checked = selectedValues.includes(String(control.value));
 });
 return;
 }

 if (tagName === 'select' && first.multiple) {
 const selectedValues = Array.isArray(value) ? value.map(String) : [];
 Array.from(first.options || []).forEach((option) => {
 option.selected = selectedValues.includes(String(option.value));
 });
 return;
 }

 first.value = String(value || '');
 applyTargetPositionMeta(first, value, draft);
 }

 function read(storageKey) {
 if (!canUseStorage) return null;

 try {
 const raw = root.localStorage.getItem(storageKey);
 if (!raw) return null;
 const draft = JSON.parse(raw);
 return draft && typeof draft === 'object' ? draft : null;
 } catch (error) {
 console.warn('Unable to read interview setup draft:', error);
 return null;
 }
 }

 function save(form, options = {}) {
 if (!form || !canUseStorage) return null;

 const storageKey = configuredStorageKey(options.storageKey, form);
 const draft = serializeForm(form, {
 activeStepIndex: typeof options.activeStepIndex === 'function' ? options.activeStepIndex() : options.activeStepIndex,
 visitedStepIds: typeof options.visitedStepIds === 'function' ? options.visitedStepIds() : options.visitedStepIds,
 });

 try {
 root.localStorage.setItem(storageKey, JSON.stringify(draft));
 return draft;
 } catch (error) {
 console.warn('Unable to save interview setup draft:', error);
 return null;
 }
 }

 function restore(form, options = {}) {
 if (!form || !canUseStorage) return null;

 const storageKey = configuredStorageKey(options.storageKey, form);
 const draft = read(storageKey);
 if (!draft?.values || typeof draft.values !== 'object') return null;

 Object.entries(draft.values).forEach(([name, value]) => {
 restoreControlValue(controlsForName(form, name), value, draft);
 });

 if (options.dispatchChange) {
 formControlNames(form).forEach((name) => {
 controlsForName(form, name).forEach((control) => {
 control.dispatchEvent(new Event('change', { bubbles: true }));
 });
 });
 }

 form.dispatchEvent(new CustomEvent('speakready:interview-setup-draft-restored', {
 bubbles: true,
 detail: { draft },
 }));

 return draft;
 }

 function clear(options = {}) {
 if (!canUseStorage) return;

 try {
 root.localStorage.removeItem(configuredStorageKey(options.storageKey));
 } catch (error) {
 console.warn('Unable to clear interview setup draft:', error);
 }
 }

 function watch(form, options = {}) {
 if (!form) return () => {};

 let timer = null;
 const persist = () => {
 if (typeof options.beforeSave === 'function') {
 options.beforeSave();
 }
 save(form, options);
 };
 const schedule = () => {
 root.clearTimeout(timer);
 timer = root.setTimeout(persist, SAVE_DEBOUNCE_MS);
 };

 form.addEventListener('input', schedule);
 form.addEventListener('change', schedule);
 root.addEventListener('pagehide', persist);
 root.addEventListener('beforeunload', persist);

 return () => {
 root.clearTimeout(timer);
 form.removeEventListener('input', schedule);
 form.removeEventListener('change', schedule);
 root.removeEventListener('pagehide', persist);
 root.removeEventListener('beforeunload', persist);
 };
 }

 root.SpeakReadyInterviewSetupDraft = {
 read,
 save,
 restore,
 clear,
 watch,
 storageKey: configuredStorageKey,
 };
})();
