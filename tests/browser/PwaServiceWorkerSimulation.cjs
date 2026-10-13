const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '../..');
const workerSource = fs.readFileSync(path.join(root, 'public/service-worker.js'), 'utf8');
const offlineHtml = fs.readFileSync(path.join(root, 'public/offline.html'), 'utf8');
const offlineUrl = 'http://localhost/RetailNova/offline.html';
const cachedEntries = new Map();
const listeners = new Map();
let networkRequests = [];

const cache = {
    async add(url) {
        assert.equal(url, offlineUrl, 'install caches only the public offline page');
        cachedEntries.set(url, new Response(offlineHtml, { headers: { 'Content-Type': 'text/html; charset=utf-8' } }));
    },
    async match(url) { return cachedEntries.get(url); },
};
const caches = {
    async open() { return cache; },
    async keys() { return ['retailnova-public-offline-v1']; },
    async delete() { return true; },
};
const self = {
    registration: { scope: 'http://localhost/RetailNova/' },
    addEventListener(name, handler) { listeners.set(name, handler); },
};
const networkFetch = async (request, options) => {
    networkRequests.push({ request, options });
    throw new Error('simulated offline network');
};
vm.runInNewContext(workerSource, { self, caches, fetch: networkFetch, URL, Response, Promise });

const check = (condition, message) => assert.ok(condition, message);
(async () => {
    let installTask;
    listeners.get('install')({ waitUntil(promise) { installTask = promise; } });
    await installTask;
    check(cachedEntries.size === 1 && cachedEntries.has(offlineUrl), 'install precaches only offline fallback');

    const authenticatedNavigation = { url: 'http://localhost/RetailNova/business/sales', method: 'GET', mode: 'navigate' };
    let fallbackPromise;
    listeners.get('fetch')({ request: authenticatedNavigation, respondWith(promise) { fallbackPromise = promise; } });
    const fallbackResponse = await fallbackPromise;
    check(fallbackResponse.status === 200, 'offline navigation returns fallback response');
    const fallbackBody = await fallbackResponse.text();
    check(fallbackBody.includes("You're offline") && fallbackBody.includes('আপনি অফলাইনে আছেন'), 'fallback is bilingual public page');
    check(networkRequests.length === 1 && networkRequests[0].options.cache === 'no-store', 'authenticated navigation is attempted on network without cache');
    check(cachedEntries.size === 1 && !cachedEntries.has(authenticatedNavigation.url), 'authenticated business data is never cached');

    let nonNavigationIntercepted = false;
    listeners.get('fetch')({
        request: { url: 'http://localhost/RetailNova/business/sales', method: 'POST', mode: 'cors' },
        respondWith() { nonNavigationIntercepted = true; },
    });
    check(!nonNavigationIntercepted, 'POST requests are not intercepted or queued');
    console.log(JSON.stringify({ checks: 5, passed: true, mode: 'service-worker-handler-simulation' }));
})().catch((error) => { console.error(error); process.exitCode = 1; });
