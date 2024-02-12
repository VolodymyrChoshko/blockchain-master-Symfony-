/**
 * @param {string|number} value
 * @returns {boolean}
 */
const isStringifiedJSON = (value) => {
  if (typeof value !== 'string') {
    return false;
  }

  if (['true', 'false', 'null'].indexOf(value) !== -1) {
    return true;
  }
  if (value.startsWith('"') && value.endsWith('"')) {
    return true;
  }
  if (value.startsWith('{') && value.endsWith('}')) {
    return true;
  }
  return value.startsWith('[') && value.endsWith(']');
};

/**
 * @param {*} num
 * @returns {boolean}
 */
const isNumeric = (num) => {
  if (typeof num === 'string' && (num.startsWith('0') || num === '')) {
    return false;
  }
  // eslint-disable-next-line no-restricted-globals
  return !isNaN(num);
};

/**
 * @param {string} key
 * @param {*} defaultValue
 * @returns {*}
 */
export const get = (key, defaultValue = null) => {
  const item = localStorage.getItem(key);
  if (item === null) {
    return defaultValue;
  }
  if (isStringifiedJSON(item) || isNumeric(item)) {
    try {
      return JSON.parse(item);
    } catch (error) {
      console.error(error);
      return defaultValue;
    }
  }

  return item;
};

/**
 * @param {string} key
 * @param {*} value
 */
export const set = (key, value) => {
  if (['object', 'boolean'].indexOf(typeof value) !== -1) {
    localStorage.setItem(key, JSON.stringify(value));
  } else {
    localStorage.setItem(key, value);
  }
};

/**
 * @param {string} key
 */
export const remove = (key) => {
  localStorage.removeItem(key);
};

export default {
  get,
  set,
  remove
};
