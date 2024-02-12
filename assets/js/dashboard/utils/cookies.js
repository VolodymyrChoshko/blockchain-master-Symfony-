/**
 * @returns {string|*}
 */
const getDomain = () => {
  const { host } = document.location;
  const match = host.match(/(\d+)\.(([^.]+)\.blocksedit.com)/);
  if (match) {
    return `.${match[2]}`;
  }

  return host;
};

/**
 * @param name
 * @param value
 * @param days
 */
export const setCookie = (name, value, days) => {
  let expires = '';
  if (days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = `; expires=${date.toUTCString()}`;
  }
  document.cookie = `${name}=${value || ''}${expires};domain=${getDomain()};path=/`;
};

/**
 * @param name
 * @returns {string|null}
 */
export const getCookie = (name) => {
  const nameEQ = `${name}=`;
  const ca = document.cookie.split(';');
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) === ' ') {
      c = c.substring(1, c.length);
    }
    if (c.indexOf(nameEQ) === 0) {
      return c.substring(nameEQ.length, c.length);
    }
  }

  return null;
};

/**
 * @param name
 */
export const eraseCookie = (name) => {
  document.cookie = `${name}=;domain=${getDomain()};path=/;expires=Thu, 01 Jan 1970 00:00:01 GMT;`;
};
