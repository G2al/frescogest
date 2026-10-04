const CACHE_NAME = 'il-paradiso-employees-v1';
const OFFLINE_URL = '/employee-offline.html';
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/employee-manifest.webmanifest',
    '/assets/pwa/employees/icon-192.png',
    '/assets/pwa/employees/icon-512.png',
    '/assets/pwa/employees/maskable-192.png',
    '/assets/pwa/employees/maskable-512.png',
    '/assets/pwa/employees/apple-touch-icon.png'
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_URLS)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(key => key.startsWith('il-paradiso-employees-') && key !== CACHE_NAME).map(key => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate' && url.pathname.startsWith('/dipendenti')) {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));

        return;
    }

    if (PRECACHE_URLS.includes(url.pathname)) {
        event.respondWith(caches.match(request).then(response => response || fetch(request)));
    }
});
