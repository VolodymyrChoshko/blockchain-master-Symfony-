import router from 'lib/router';

/**
 * @param base64String
 * @return {Uint8Array}
 */
export const urlBase64ToUint8Array = (base64String) => {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
};

/**
 * @param message
 * @return {Promise<unknown>}
 */
export const sendMessage = (message) => {
  return new Promise((resolve, reject) => {
    if ('serviceWorker' in navigator) {
      const messageChannel           = new MessageChannel();
      messageChannel.port1.onmessage = (event) => {
        if (event.data.error) {
          reject(event.data.error);
        } else {
          resolve(event.data);
        }
      };

      navigator.serviceWorker.controller.postMessage(message, [messageChannel.port2]);
    }
  });
};

/**
 *
 */
export const registerServiceWorker = () => {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker
      .register(router.asset('build/sw.js'), { scope: '/' })
      .then((registration) => {
        // return registration.update();
        return registration;
      })
      .catch((err) => {
        console.error('Unable to register service worker.', err);
      });
  }
};

/**
 * @return {Promise<unknown>}
 */
export const requestPushSubscription = () => {
  return new Promise((resolve, reject) => {
    if (!window.initialState.webPushPubKey) {
      reject(new Error('webPushPubKey not set.'));
      return;
    }

    if ('serviceWorker' in navigator && 'PushManager' in window) {
      navigator.serviceWorker
        .register(router.asset('build/sw.js'), { scope: '/' })
        .then(async (registration) => {
          const subscription = await registration.pushManager.getSubscription();
          if (subscription) {
            await subscription.unsubscribe();
          }

          const subscribeOptions = {
            userVisibleOnly:      true,
            applicationServerKey: urlBase64ToUint8Array(window.initialState.webPushPubKey),
          };

          return registration.pushManager.subscribe(subscribeOptions);
        })
        .then(resolve)
        .catch((err) => {
          reject(err);
        });
    } else {
      reject(new Error('serviceWorker not defined.'));
    }
  });
};
