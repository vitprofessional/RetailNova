(function () {
    'use strict';

    var manifestLink = document.querySelector('link[rel="manifest"]');
    var installButton = document.getElementById('rn-pwa-install');
    var instructionsModal = document.getElementById('rn-pwa-ios-help');
    if (!manifestLink) return;

    var manifestUrl = new URL(manifestLink.href, location.href);
    var scopeUrl = new URL('./', manifestUrl);
    var workerUrl = new URL('service-worker.js', scopeUrl);
    var supportedSecureContext = window.isSecureContext ||
        location.hostname === 'localhost' || location.hostname === '127.0.0.1';

    if ('serviceWorker' in navigator && supportedSecureContext && workerUrl.origin === location.origin) {
        navigator.serviceWorker.register(workerUrl.href, { scope: scopeUrl.pathname }).catch(function () {
            // PWA enhancement must not affect normal application navigation.
        });
    }

    if (!installButton) return;

    var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (isStandalone) return;

    var ua = navigator.userAgent || '';
    var isIos = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var isSafari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS|OPiOS/.test(ua);
    var deferredPrompt = null;
    var dismissedKey = 'retailnova-install-dismissed';
    var wasDismissed = false;
    try { wasDismissed = sessionStorage.getItem(dismissedKey) === '1'; } catch (_) {}

    if (isIos && isSafari) {
        installButton.hidden = false;
        installButton.dataset.installMode = 'ios';
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        if (!installButton || wasDismissed) return;
        event.preventDefault();
        deferredPrompt = event;
        installButton.hidden = false;
        installButton.dataset.installMode = 'android';
    });

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        installButton.hidden = true;
    });

    installButton.addEventListener('click', async function () {
        if (installButton.dataset.installMode === 'ios') {
            if (window.jQuery && instructionsModal) window.jQuery(instructionsModal).modal('show');
            return;
        }
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        var choice = await deferredPrompt.userChoice;
        if (choice && choice.outcome === 'dismissed') {
            try { sessionStorage.setItem(dismissedKey, '1'); } catch (_) {}
        }
        deferredPrompt = null;
        installButton.hidden = true;
    });
})();
