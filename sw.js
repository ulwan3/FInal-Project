// Service Worker untuk Web Push Notifications
const CACHE_NAME = 'keuangan-mahasiswa-v1';
const urlsToCache = [
    '/',
    '/assets/css/style.css',
    '/assets/js/main.js'
];

self.addEventListener('install', function(event) {
    // Cache essential static assets but avoid caching auth pages
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache).catch(function(err) {
                    // Prevent install from failing if one asset can't be cached
                    console.warn('Some resources failed to cache during install', err);
                });
            })
    );
});

self.addEventListener('fetch', function(event) {
    // For navigation requests (pages) prefer network first,
    // fall back to cache if offline. For other requests, use cache first.
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(function() {
                return caches.match('/').then(function(resp) {
                    return resp || new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
                });
            })
        );
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                return response || fetch(event.request).catch(function() {
                    return response; // return cached response if fetch fails (or undefined)
                });
            })
    );
});

self.addEventListener('push', function(event) {
    if (!event.data) return;

    const data = event.data.json();
    const options = {
        body: data.message,
        icon: '/images/icon-192.png',
        badge: '/images/badge-72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/'
        },
        actions: [
            {
                action: 'open',
                title: 'Buka Aplikasi'
            },
            {
                action: 'close',
                title: 'Tutup'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.action === 'open') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    }
});