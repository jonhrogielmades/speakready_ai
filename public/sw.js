const CACHE_NAME = 'speakready-pwa-v46';
const CLIENT_REFRESH_PARAM = 'sr_sw_refresh';
const CLIENT_REFRESH_VALUE = 'pwa-v46';
const OFFLINE_PAGE = '/offline.html';
const NOTIFICATION_ICON = '/img/notification-icon-192.png?v=3';
const NOTIFICATION_BADGE = '/img/notification-badge.png?v=4';
const DEFAULT_NOTIFICATION_ACTIONS = [
  { action: 'open', title: 'Open' },
  { action: 'view-notifications', title: 'View all' },
];
const CODE_ASSET_PATTERN = /\.(?:css|js|mjs)$/i;
const STATIC_ASSET_PATTERN = /\.(?:css|js|mjs|png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf|eot)$/i;

function isSameOrigin(requestUrl) {
  return new URL(requestUrl).origin === self.location.origin;
}

function isHtmlNavigation(request) {
  return request.mode === 'navigate' || (request.headers.get('accept') || '').includes('text/html');
}

function isCacheableStaticAsset(request) {
  if (!isSameOrigin(request.url)) return false;

  return STATIC_ASSET_PATTERN.test(new URL(request.url).pathname);
}

function isCodeAsset(request) {
  if (!isSameOrigin(request.url)) return false;

  return CODE_ASSET_PATTERN.test(new URL(request.url).pathname);
}

function refreshedClientUrl(clientUrl) {
  try {
    const url = new URL(clientUrl);
    if (url.origin !== self.location.origin) return null;
    if (url.searchParams.get(CLIENT_REFRESH_PARAM) === CLIENT_REFRESH_VALUE) return null;

    url.searchParams.set(CLIENT_REFRESH_PARAM, CLIENT_REFRESH_VALUE);

    return url.toString();
  } catch (error) {
    return null;
  }
}

function refreshControlledClients() {
  return self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
    return Promise.all(
      clients.map(client => {
        const nextUrl = refreshedClientUrl(client.url);
        if (!nextUrl) {
          return undefined;
        }

        if ('navigate' in client) {
          return client.navigate(nextUrl).catch(() => {
            client.postMessage({
              type: 'SPEAKREADY_PWA_REFRESH_REQUIRED',
              cacheName: CACHE_NAME,
            });
          });
        }

        client.postMessage({
          type: 'SPEAKREADY_PWA_REFRESH_REQUIRED',
          cacheName: CACHE_NAME,
        });

        return undefined;
      })
    );
  });
}

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll([
      OFFLINE_PAGE,
      '/img/logo.png',
    ])).catch(() => undefined)
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
      .then(() => refreshControlledClients())
  );
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  if (isHtmlNavigation(event.request)) {
    event.respondWith(
      fetch(event.request, { cache: 'no-store' }).catch(() => {
        return caches.match(OFFLINE_PAGE).then(offlineResponse => offlineResponse || new Response(
          '<!doctype html><title>SpeakReady AI is offline</title><p>Please reconnect and try again.</p>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        ));
      })
    );
    return;
  }

  if (!isCacheableStaticAsset(event.request)) return;

  event.respondWith(
    caches.open(CACHE_NAME).then(cache => {
      const freshResponse = () => fetch(event.request, { cache: 'no-cache' }).then(response => {
        if (response && response.ok) {
          return cache.put(event.request, response.clone())
            .catch(() => undefined)
            .then(() => response);
        }

        return response;
      });

      if (isCodeAsset(event.request)) {
        // CSS and JavaScript must come from the current deployment whenever the
        // network is available. The cache is only an offline/error fallback.
        return freshResponse().catch(() => cache.match(event.request));
      }

      return cache.match(event.request).then(cachedResponse => {
        if (cachedResponse) {
          event.waitUntil(freshResponse().catch(() => undefined));
          return cachedResponse;
        }

        return freshResponse().catch(() => cache.match(event.request));
      });
    })
  );
});

function pushPayload(event) {
  if (!event.data) return {};

  try {
    return event.data.json();
  } catch (error) {
    return {
      title: 'SpeakReady AI',
      body: event.data.text(),
    };
  }
}

function notificationActions(payload) {
  if (Array.isArray(payload.actions)) {
    return payload.actions
      .filter(action => action && typeof action.title === 'string')
      .slice(0, 2);
  }

  return DEFAULT_NOTIFICATION_ACTIONS;
}

function notificationVibration(payload) {
  if (Array.isArray(payload.vibrate) && payload.vibrate.every(value => Number.isFinite(Number(value)))) {
    return payload.vibrate.map(value => Math.max(0, Number(value))).slice(0, 8);
  }

  return [120, 50, 120];
}

self.addEventListener('push', event => {
  const payload = pushPayload(event);
  const notificationData = payload.data && typeof payload.data === 'object' && !Array.isArray(payload.data)
    ? payload.data
    : {};
  const targetUrl = payload.url || notificationData.url || '/notifications';
  const timestamp = Number.isFinite(Number(payload.timestamp)) ? Number(payload.timestamp) : Date.now();

  event.waitUntil(
    self.registration.showNotification(payload.title || 'SpeakReady AI', {
      body: payload.body || payload.message || 'You have a new notification.',
      icon: payload.icon || NOTIFICATION_ICON,
      badge: payload.badge || NOTIFICATION_BADGE,
      image: payload.image || undefined,
      tag: payload.tag || 'speakready-notification',
      renotify: payload.renotify !== false,
      requireInteraction: payload.requireInteraction === true,
      silent: payload.silent === true,
      timestamp,
      vibrate: notificationVibration(payload),
      actions: notificationActions(payload),
      data: {
        ...notificationData,
        url: targetUrl,
      },
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'dismiss') {
    return;
  }

  const targetUrl = event.notification.data?.url || '/notifications';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
      for (const client of clients) {
        if ('focus' in client) {
          client.navigate(targetUrl);
          return client.focus();
        }
      }

      if (self.clients.openWindow) {
        return self.clients.openWindow(targetUrl);
      }

      return undefined;
    })
  );
});
