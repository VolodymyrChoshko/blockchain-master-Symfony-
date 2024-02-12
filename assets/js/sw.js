/**
 *
 */
self.addEventListener('push', (/** @type PushEvent */ e) => {
  const options = e.data?.json();
  if (!options) {
    return;
  }

  if (options.body && options.icon) {
    options.data = {
      url: options.url,
    };
    e.waitUntil(self.registration.showNotification('BlocksEdit', options));
  }
});

/**
 *
 */
self.addEventListener('notificationclick', (/** @type NotificationEvent */ e) => {
  e.notification.close();
  if (e.notification.data.url) {
    e.waitUntil(
      clients.matchAll({ type: 'window' })
        .then((windowClients) => {
          for (let i = 0; i < windowClients.length; i++) {
            const client = windowClients[i];
            if (client.url === e.notification.data.url && 'focus' in client) {
              return client.focus();
            }
          }

          if (clients.openWindow) {
            return clients.openWindow(e.notification.data.url);
          }

          return null;
        })
    );
  }
});

/**
 *
 */
self.addEventListener('message', (event) => {
  event.ports[0].postMessage({ 'test': 'This is my response.' });
});
