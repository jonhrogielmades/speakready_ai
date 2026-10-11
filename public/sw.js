const CACHE_NAME = 'speakready-pwa-v35';
const CLIENT_REFRESH_PARAM = 'sr_sw_refresh';
const CLIENT_REFRESH_VALUE = 'pwa-v35';
const STATIC_ASSET_PATTERN = /\.(?:css|js|png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf|eot)$/i;
const NETWORK_FIRST_ASSET_PATTERN = /\.(?:css|js)$/i;

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

function isNetworkFirstStaticAsset(request) {
  if (!isSameOrigin(request.url)) return false;

  return NETWORK_FIRST_ASSET_PATTERN.test(new URL(request.url).pathname);
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
    event.respondWith(fetch(event.request, { cache: 'no-store' }));
    return;
  }

  if (!isCacheableStaticAsset(event.request)) return;

  event.respondWith(
    caches.open(CACHE_NAME).then(cache => {
      const freshResponse = fetch(event.request, { cache: 'no-cache' }).then(response => {
        if (response && response.ok) {
          cache.put(event.request, response.clone());
        }

        return response;
      });

      if (isNetworkFirstStaticAsset(event.request)) {
        return freshResponse.catch(() => caches.match(event.request));
      }

      return cache.match(event.request).then(cachedResponse => {
        if (cachedResponse) {
          event.waitUntil(freshResponse.catch(() => undefined));
          return cachedResponse;
        }

        return freshResponse.catch(() => caches.match(event.request));
      });
    })
  );
});

self.addEventListener('message', event => {
  const data = event.data || {};

  if (data.type !== 'SHOW_ADMIN_ACTIVITY_NOTIFICATION') return;

  event.waitUntil(
    self.registration.showNotification(data.title || 'SpeakReady AI', {
      body: data.body || 'New admin activity.',
      icon: '/img/app-icon-192.png',
      badge: '/img/app-icon-192.png',
      tag: data.tag || 'admin-activity',
      renotify: true,
      data: {
        url: data.url || '/admin/dashboard',
      },
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

self.addEventListener('push', event => {
  const payload = pushPayload(event);
  const notificationData = payload.data && typeof payload.data === 'object' && !Array.isArray(payload.data)
    ? payload.data
    : {};
  const targetUrl = payload.url || notificationData.url || '/notifications';

  event.waitUntil(
    self.registration.showNotification(payload.title || 'SpeakReady AI', {
      body: payload.body || payload.message || 'You have a new notification.',
      icon: payload.icon || '/img/app-icon-192.png',
      badge: payload.badge || '/img/app-icon-192.png',
      tag: payload.tag || 'speakready-notification',
      renotify: payload.renotify !== false,
      data: {
        ...notificationData,
        url: targetUrl,
      },
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();

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
