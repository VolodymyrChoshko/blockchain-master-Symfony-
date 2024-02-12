const listeners = [];
let isListeningPopstate = false;

/**
 *
 */
export const trigger = () => {
  listeners.forEach((cb) => {
    cb(document.location);
  });
};

/**
 * @param {function} cb
 * @returns {(function(): void)|*}
 */
export const onLocationChange = (cb) => {
  if (!isListeningPopstate) {
    window.addEventListener('popstate', trigger);
    isListeningPopstate = true;
  }

  listeners.push(cb);
  cb(document.location);

  return () => {
    const index = listeners.indexOf(cb);
    if (index !== -1) {
      listeners.splice(index, 1);
    }
  };
};

/**
 *
 * @param {string} url
 * @param {*} data
 * @param {string} title
 */
export const pushHistoryState = (url, data = {}, title = '') => {
  history.pushState(data, title, url);
  trigger();
};
