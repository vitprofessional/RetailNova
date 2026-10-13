/* RetailNova worker: only the public offline page is cached. */
const CACHE_PREFIX = 'retailnova-public-offline-';
const CACHE_NAME = `${CACHE_PREFIX}v1`;
const OFFLINE_URL = new URL('offline.html', self.registration.scope).href;

self.addEventListener('install', (event) => {
    // Keep the worker waiting until existing clients close; don't interrupt POS work.
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL)));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(
        keys.filter((key) => key.startsWith(CACHE_PREFIX) && key !== CACHE_NAME)
            .map((key) => caches.delete(key))
    )));
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET' || request.mode !== 'navigate') return;

    event.respondWith(fetch(request, { cache: 'no-store' }).catch(async () => {
        const cache = await caches.open(CACHE_NAME);
        return (await cache.match(OFFLINE_URL)) || new Response(
            '<!doctype html><meta charset="utf-8"><title>RetailNova offline</title><p>You are offline · আপনি অফলাইনে আছেন</p>',
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }));
});
