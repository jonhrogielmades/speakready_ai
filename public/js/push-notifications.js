(function () {
  const state = {
    keyPromise: null,
    registrationPromise: null,
  };

  function config() {
    return window.SpeakReadyPushConfig || {};
  }

  function csrfToken() {
    return config().csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function scheduleIdle(callback) {
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(callback, { timeout: 2500 });
      return;
    }

    window.setTimeout(callback, 900);
  }

  function isSupported() {
    return Boolean(
      window.isSecureContext &&
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window
    );
  }

  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i += 1) {
      outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
  }

  async function requestJson(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        ...(options.headers || {}),
      },
    });
    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json') ? await response.json().catch(() => null) : null;

    if (!response.ok) {
      throw new Error(payload?.message || 'Push notification request failed.');
    }

    return payload || {};
  }

  async function fetchPublicKey() {
    if (state.keyPromise) return state.keyPromise;

    const publicKeyUrl = config().publicKeyUrl;
    if (!publicKeyUrl) return null;

    state.keyPromise = requestJson(publicKeyUrl)
      .then((payload) => (payload?.supported && payload.publicKey ? payload.publicKey : null))
      .catch((error) => {
        console.warn('Push notifications are not configured.', error);
        return null;
      });

    return state.keyPromise;
  }

  async function serviceWorkerRegistration() {
    if (state.registrationPromise) return state.registrationPromise;
    if (window.SpeakReadyServiceWorkerRegistration) {
      state.registrationPromise = Promise.resolve(window.SpeakReadyServiceWorkerRegistration);
      return state.registrationPromise;
    }

    const serviceWorkerUrl = config().serviceWorkerUrl || '/sw.js?v=12';

    state.registrationPromise = navigator.serviceWorker.register(serviceWorkerUrl)
      .then((registration) => {
        window.SpeakReadyServiceWorkerRegistration = registration;
        return registration;
      });

    return state.registrationPromise;
  }

  function subscriptionPayload(subscription) {
    const payload = subscription.toJSON();
    const encodings = PushManager.supportedContentEncodings || ['aes128gcm'];

    return {
      endpoint: payload.endpoint,
      expirationTime: payload.expirationTime || null,
      keys: payload.keys || {},
      contentEncoding: encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm',
    };
  }

  async function saveSubscription(subscription) {
    const subscribeUrl = config().subscribeUrl;
    if (!subscribeUrl) return { success: false };

    return requestJson(subscribeUrl, {
      method: 'POST',
      body: JSON.stringify(subscriptionPayload(subscription)),
    });
  }

  async function enable(options = {}) {
    if (!isSupported()) {
      return { status: 'unsupported' };
    }

    const shouldRequestPermission = options.requestPermission !== false;
    let permission = Notification.permission;

    if (permission !== 'granted' && shouldRequestPermission) {
      permission = await Notification.requestPermission();
    }

    if (permission !== 'granted') {
      return { status: permission === 'denied' ? 'denied' : 'default' };
    }

    const publicKey = await fetchPublicKey();
    if (!publicKey) {
      return { status: 'unconfigured' };
    }

    const registration = await serviceWorkerRegistration();
    let subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
      subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicKey),
      });
    }

    await saveSubscription(subscription);

    return { status: 'granted', subscription };
  }

  async function syncExisting() {
    if (!isSupported() || Notification.permission !== 'granted') {
      return { status: 'skipped' };
    }

    return enable({ requestPermission: false });
  }

  async function disable() {
    if (!isSupported()) {
      return { status: 'unsupported' };
    }

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
      return { status: 'empty' };
    }

    const unsubscribeUrl = config().unsubscribeUrl;
    if (unsubscribeUrl) {
      await requestJson(unsubscribeUrl, {
        method: 'DELETE',
        body: JSON.stringify({ endpoint: subscription.endpoint }),
      });
    }

    await subscription.unsubscribe();

    return { status: 'unsubscribed' };
  }

  window.SpeakReadyPush = {
    isSupported,
    enable,
    syncExisting,
    disable,
  };

  document.addEventListener('DOMContentLoaded', function () {
    scheduleIdle(function () {
      syncExisting().catch((error) => {
        console.warn('Unable to sync push notification subscription.', error);
      });
    });
  });
})();
