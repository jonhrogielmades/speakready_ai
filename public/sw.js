const CACHE_NAME = 'speakready-pwa-v14';
const STATIC_ASSET_PATTERN = /\.(?:css|js|png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf|eot)$/i;

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
    fetch(event.request, { cache: 'no-cache' }).then(response => {
      if (!response || !response.ok) return response;

      const responseClone = response.clone();
      caches.open(CACHE_NAME).then(cache => {
        cache.put(event.request, responseClone);
      });

      return response;
    }).catch(() => caches.match(event.request))
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

self.addEventListener('notificationclick', event => {
  event.notification.close();

  const targetUrl = event.notification.data?.url || '/admin/dashboard';

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
