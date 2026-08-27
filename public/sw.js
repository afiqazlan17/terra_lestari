// Minimal service worker, present only so the browser considers this site
// installable as a PWA. It intentionally does not cache anything - every
// request still goes to the network as normal. Offline order queuing for
// POS is handled separately, in resources/views/pos/index.blade.php.

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', () => {
    // No-op: let the browser handle every request normally.
});
