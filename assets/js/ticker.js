/**
 * Used by builder/actions/socketActions.js because setInterval() on the main
 * document does not always work with inactive tabs, but web workers do work.
 */
let interval;

// eslint-disable-next-line no-restricted-globals
self.addEventListener('message', (e) => {
  switch (e.data) {
    case 'start':
      interval = setInterval(() => {
        // eslint-disable-next-line no-restricted-globals
        self.postMessage('tick');
      }, 1000);
      break;
    case 'stop':
      clearInterval(interval);
      break;
  }
}, false);
