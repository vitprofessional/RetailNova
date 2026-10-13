const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const manifest = JSON.parse(read('public/manifest.webmanifest'));
const worker = read('public/service-worker.js');
const bootstrap = read('public/js/pwa.js');
const offline = read('public/offline.html');
const pwaHead = read('resources/views/partials/pwa-head.blade.php');
const iconPaths = {
    '192x192-any': 'public/pwa/icon-192.png',
    '512x512-any': 'public/pwa/icon-512.png',
    '512x512-maskable': 'public/pwa/icon-maskable-512.png',
    '180x180-apple': 'public/pwa/apple-touch-icon.png',
};
let checks = 0;
const check = (condition, message) => { assert.ok(condition, message); checks++; };

check(manifest.name === 'RetailNova' && manifest.short_name === 'RetailNova', 'manifest identity');
check(manifest.start_url === './' && manifest.scope === './' && manifest.id === './', 'base-relative manifest URLs');
check(manifest.display === 'standalone', 'standalone display mode');
check(pwaHead.includes("asset('manifest.webmanifest')"), 'manifest URL uses Laravel base URL');
check(bootstrap.includes("new URL('service-worker.js', scopeUrl)"), 'worker URL follows manifest directory');
check(bootstrap.includes("workerUrl.origin === location.origin"), 'worker is limited to same origin');
check(bootstrap.includes('window.isSecureContext') && bootstrap.includes("location.hostname === 'localhost'"), 'secure context guard with localhost exception');
check(worker.includes("request.method !== 'GET' || request.mode !== 'navigate'"), 'only GET navigations use worker fallback');
check(worker.includes("fetch(request, { cache: 'no-store' })"), 'navigation responses are network-only');
check(worker.includes('cache.add(OFFLINE_URL)') && !worker.includes('cache.put('), 'only the explicit offline page is cached');
check(!worker.includes('skipWaiting') && !worker.includes('clients.claim'), 'worker does not force takeover');
check(/You're offline/.test(offline) && /আপনি অফলাইনে আছেন/.test(offline), 'offline fallback is English and Bangla');
check(bootstrap.includes('beforeinstallprompt') && bootstrap.includes('appinstalled'), 'Android install lifecycle is user initiated');
check(bootstrap.includes("display-mode: standalone") && bootstrap.includes('navigator.standalone'), 'standalone mode hides install action');

const expectedManifestIcons = [
    { src: 'pwa/icon-192.png', sizes: '192x192', purpose: 'any' },
    { src: 'pwa/icon-512.png', sizes: '512x512', purpose: 'any' },
    { src: 'pwa/icon-maskable-512.png', sizes: '512x512', purpose: 'maskable' },
];
for (const expected of expectedManifestIcons) {
    check(manifest.icons.some((icon) => icon.src === expected.src && icon.sizes === expected.sizes && icon.purpose === expected.purpose), `manifest icon ${expected.src}`);
}
const expectedDimensions = {
    [iconPaths['192x192-any']]: [192, 192],
    [iconPaths['512x512-any']]: [512, 512],
    [iconPaths['512x512-maskable']]: [512, 512],
    [iconPaths['180x180-apple']]: [180, 180],
};
for (const [file, dimensions] of Object.entries(expectedDimensions)) {
    const bytes = fs.readFileSync(path.join(root, file));
    check(bytes.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10])), `${file} is PNG`);
    check(bytes.readUInt32BE(16) === dimensions[0] && bytes.readUInt32BE(20) === dimensions[1], `${file} dimensions ${dimensions.join('x')}`);
}
check(pwaHead.includes("asset('pwa/apple-touch-icon.png')"), 'Apple touch icon registered in Laravel head');
check(pwaHead.includes("asset('pwa/icon-192.png')"), 'browser icon registered in Laravel head');
console.log(JSON.stringify({ checks, passed: true, installability: 'manifest-and-icon-assets-present' }));
