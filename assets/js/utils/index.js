import cloneDeep from 'clone-deep';
import { bindActionCreators } from 'redux';

/**
 * @param {*} actions
 * @returns {function(*)}
 */
export function mapDispatchToProps(...actions) {
  const mapped = {};
  for (let i = 0; i < actions.length; i++) {
    const keys = Object.keys(actions[i]);
    for (let y = 0; y < keys.length; y++) {
      const key = keys[y];
      mapped[key] = actions[i][key];
    }
  }

  return (dispatch) => {
    return bindActionCreators(mapped, dispatch);
  };
}

/**
 * @param {*} initialState
 * @param {*} handlers
 * @returns {Function}
 */
export const createReducer = (initialState, handlers) => {
  const initial = cloneDeep(initialState);
  return (state = initial, action = {}) => {
    if (handlers[action.type]) {
      return handlers[action.type].call(null, state, action);
    }

    return state;
  };
};

/**
 * @param {boolean} isLoading
 * @param {boolean} mask
 */
export const loading = (isLoading, mask = false) => {
  let el = document.getElementById('spinner');
  if (!el) {
    el = document.createElement('div');
    el.setAttribute('id', 'spinner');
    el.setAttribute('class', 'fancybox-loading');
    el.setAttribute('style', 'display: none;');
    document.body.appendChild(el);
  }

  let maskEl = document.getElementById('loading-mask');
  if (!maskEl) {
    maskEl = document.createElement('div');
    maskEl.setAttribute('id', 'loading-mask');
    maskEl.setAttribute('class', 'mask');
    document.body.appendChild(maskEl);
  }

  if (isLoading) {
    el.style.display = 'block';
    if (mask) {
      maskEl.classList.add('mounted');
    }
    setTimeout(() => {
      if (mask) {
        maskEl.classList.add('visible');
      }
    }, 250);
  } else {
    el.style.display = 'none';
    maskEl.classList.remove('mounted');
    maskEl.classList.remove('visible');
  }
};

/**
 * @param {string} str
 * @param {string} search
 * @param {string} replacement
 * @returns {string}
 */
export function replaceAll(str, search, replacement) {
  while (str.includes(search)) {
    str = str.replace(search, replacement);
  }
  return str;
}

/**
 * @param {string} prefix
 * @returns {string}
 */
export function uniqueID(prefix = '') {
  return `${prefix}${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * @param {string} str
 * @param {string} chars
 * @returns {string}
 */
export function trimLeft(str, chars) {
  if (chars === undefined) {
    // eslint-disable-next-line no-useless-escape
    chars = '\s';
  }
  const regex = `^[${chars}]+`;

  return str.replace(new RegExp(regex), '');
}
