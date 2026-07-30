
// sw.js  –  ForkFresh Service Worker
// Handles background push notifications in the browser.
// Place this file at the ROOT of the ForkFresh folder so its
// scope covers both pages.


const CACHE_NAME = 'forkfresh-v1';

// ── Install 
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// ── Activate 
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// ── Push received 
self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch {
        data = { title: 'ForkFresh', body: event.data ? event.data.text() : 'Update!' };
    }

    const title   = data.title  || 'ForkFresh 🍴';
    const options = {
        body:    data.body    || 'You have a new notification.',
        icon:    data.icon    || '/ForkFresh/assets/icon-192.png',
        badge:   data.badge   || '/ForkFresh/assets/badge-72.png',
        vibrate: [200, 100, 200],
        tag:     data.tag     || 'forkfresh-notif',
        renotify: true,
        data: {
            url: data.url || '/ForkFresh/track-order/index.html',
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// ── Notification click 
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/ForkFresh/track-order/index.html';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(windowClients => {
                // Focus existing tab if open
                for (const client of windowClients) {
                    if (client.url.includes(targetUrl) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Otherwise open a new tab
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
    );
});

// ── Push subscription change 
self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil(
        self.registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: self.__VAPID_PUBLIC_KEY__,
        }).then(subscription => {
            return fetch('/ForkFresh/backend/api/subscribe.php?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(subscription),
            });
        })
    );
});
