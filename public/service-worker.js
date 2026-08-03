const CACHE_NAME = 'il-paradiso-storefront-v1';
const OFFLINE_URL = '/offline.html';
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/assets/pwa/frontend/icon-192.png',
    '/assets/pwa/frontend/icon-512.png',
    '/assets/pwa/frontend/maskable-192.png',
    '/assets/pwa/frontend/maskable-512.png',
    '/assets/pwa/frontend/apple-touch-icon.png'
];
const EXCLUDED_PREFIXES = ['/admin', '/partner', '/employees', '/livewire', '/api', '/storage'];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_URLS)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(key => key.startsWith('il-paradiso-storefront-') && key !== CACHE_NAME).map(key => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || EXCLUDED_PREFIXES.some(prefix => url.pathname.startsWith(prefix))) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));

        return;
    }

    if (!url.pathname.startsWith('/assets/') && url.pathname !== '/manifest.webmanifest') {
        return;
    }

    event.respondWith(
        caches.match(request).then(cachedResponse => {
            const networkResponse = fetch(request)
                .then(response => {
                    if (response.ok) {
                        const responseCopy = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, responseCopy));
                    }

                    return response;
                })
                .catch(() => cachedResponse);

            return cachedResponse || networkResponse;
        })
    );
});
