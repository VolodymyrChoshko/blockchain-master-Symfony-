/**
 * @param {string} t
 */
export function title(t) {
  document.title = t;
}

/**
 * @param {string} behavior
 */
export function scrollToTop(behavior = 'normal') {
  if (behavior !== 'normal') {
    window.scroll({
      left: 0,
      top:  0,
      behavior
    });
    return;
  }
  document.body.scrollTop = document.documentElement.scrollTop = 0; // eslint-disable-line
}

/**
 * @param {number|HTMLElement} top
 * @param {string|number} behavior
 * @param {string} rest
 */
export function scroll(top = 0, behavior = 'smooth', rest = 'smooth') {
  if (typeof top !== 'number' && typeof top !== 'string') {
    top.scroll({
      left:     0,
      top:      behavior,
      behavior: rest
    });
  } else {
    window.scroll({
      left: 0,
      top,
      behavior
    });
  }
}

/**
 * @param {HTMLElement|undefined} element
 */
export const scrollIntoView = (element) => {
  if (element) {
    element.scrollIntoView({
      behavior: 'smooth',
      block:    'center'
    });
  }
};

/**
 *
 */
export function hideScrollbars() {
  // firefox, chrome
  document.documentElement.style.overflow = 'hidden';
  // ie only
  document.body.scroll = 'no';
}

/**
 *
 */
export function showScrollbars() {
  // firefox, chrome
  document.documentElement.style.overflow = 'auto';
  // ie only
  document.body.scroll = 'yes';
}

/**
 * @param {HTMLElement[]|HTMLFormControlsCollection} elements
 * @returns {*}
 */
export function serializeFormFields(elements) {
  const values = {};

  for (let i = 0; i < elements.length; i++) {
    const { name, value, type, checked } = elements[i];
    const isCheckbox = (type === 'checkbox' || type === 'radio');
    if (name && type === 'file') {
      if (elements[i].files.length > 0) {
        // eslint-disable-next-line prefer-destructuring
        values[name] = elements[i].files[0];
      } else {
        values[name] = null;
      }
    } else if (name && (!isCheckbox || (isCheckbox && checked))) {
      values[name] = value;
    }
  }

  return values;
}

/**
 * @param {HTMLElement|EventTarget} form
 */
export function serializeForm(form) {
  return serializeFormFields(form.elements);
}

/**
 * @param {EventTarget|HTMLElement} currentTarget
 * @returns {{name: string, value: *}}
 */
export function extractFormValue(currentTarget) {
  const name = currentTarget.getAttribute('name');
  const tag  = currentTarget.tagName;

  let value = '';
  if (tag === 'SELECT') {
    ({ value } = currentTarget.options[currentTarget.selectedIndex]);
  } else if (currentTarget.getAttribute('type') === 'checkbox') {
    value = currentTarget.checked ? 1 : 0;
  } else {
    ({ value } = currentTarget);
  }

  return {
    name,
    value
  };
}

/**
 * @param {HTMLElement|Node} element
 * @param {string} className
 * @param {boolean} returnParent
 * @returns {boolean|HTMLElement|Node}
 */
export function hasParentClass(element, className, returnParent = false) {
  do {
    if (element.classList && element.classList.contains(className)) {
      if (returnParent) {
        return element;
      }
      return true;
    }
    element = element.parentNode;
  } while (element);

  return false;
}

/**
 * @param {HTMLElement|Node} element
 * @param {string} attribute
 * @returns {boolean}
 */
export function hasParentAttribute(element, attribute) {
  do {
    if (element.getAttribute && element.getAttribute(attribute)) {
      return true;
    }
    element = element.parentNode;
  } while (element);

  return false;
}

/**
 * @param {string} key
 * @param {*} defaultValue
 * @returns {any}
 */
export function storageGetItem(key, defaultValue = null) {
  const value = localStorage.getItem(key);
  if (!value) {
    return defaultValue;
  }

  try {
    return JSON.parse(value);
  } catch (error) {
    console.error(error);
    return defaultValue;
  }
}

/**
 * @param {string} key
 * @param {*} value
 */
export function storageSetItem(key, value) {
  if (typeof value === 'object') {
    localStorage.setItem(key, JSON.stringify(value));
  } else {
    localStorage.setItem(key, value);
  }
}

/**
 * @param {string} key
 * @param {*} value
 */
export function storagePushItem(key, value) {
  const current = storageGetItem(key, []);
  current.push(value);
  storageSetItem(key, current);

  return current;
}

/**
 * @param {HTMLIFrameElement} iframe
 * @returns {Document}
 */
export function iFrameDocument(iframe) {
  return iframe.contentDocument || iframe.contentWindow.document;
}

/**
 * @param {HTMLIFrameElement} iframe
 * @param {*} html
 * @returns {string}
 */
export function iFrameSrc(iframe, html = null) {
  const doc = iFrameDocument(iframe);

  if (html === null) {
    const node = doc.doctype;
    let docType = '';
    if (node) {
      docType = '<!DOCTYPE '
        + node.name
        + (node.publicId ? ' PUBLIC "' + node.publicId + '"' : '')
        + (!node.publicId && node.systemId ? ' SYSTEM' : '')
        + (node.systemId ? ' "' + node.systemId + '"' : '')
        + '>';
    }

    return `${docType}${doc.documentElement.outerHTML}`;
  }

  // console.error(html);
  doc.open();
  doc.write(html);
  doc.close();

  return html;
}

/**
 * @returns {{width: number, height: number}}
 */
export function getViewpointSize() {
  const width  = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
  const height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

  return {
    width,
    height
  };
}

/**
 * @param {Document} doc
 * @param {Window} win
 * @param {boolean} isStart
 * @returns {Node}
 */
export function getSelectedNode(doc = null, win = null, isStart = true) {
  if (!doc) {
    doc = document;
  }
  if (!win) {
    win = window;
  }

  let range;
  let container;

  if (doc.selection) {
    range = doc.selection.createRange();
    range.collapse(isStart);

    return range.parentElement();
  }

  const sel = win.getSelection();
  if (sel.getRangeAt) {
    if (sel.rangeCount > 0) {
      range = sel.getRangeAt(0);
    }
  } else {
    // Old WebKit
    range = doc.createRange();
    range.setStart(sel.anchorNode, sel.anchorOffset);
    range.setEnd(sel.focusNode, sel.focusOffset);

    // Handle the case when the selection was selected backwards (from the end to the start in the document)
    if (range.collapsed !== sel.isCollapsed) {
      range.setStart(sel.focusNode, sel.focusOffset);
      range.setEnd(sel.anchorNode, sel.anchorOffset);
    }
  }

  if (range) {
    container = range[isStart ? 'startContainer' : 'endContainer'];

    // Check if the container is a text node and return its parent if so
    return container.nodeType === 3 ? container.parentNode : container;
  }

  return false;
}

/**
 *
 * @param {Node|HTMLElement} element
 * @param {string} tagName
 * @returns {boolean}
 */
export function hasParentTag(element, tagName) {
  do {
    if (element.tagName === tagName) {
      return true;
    }
    element = element.parentNode;
  } while (element);

  return false;
}

/**
 * @param {Node|HTMLElement} element
 * @returns {HTMLElement}
 */
export function getParentDataGroup(element) {
  element = element.parentNode;
  while (element) {
    if (element.getAttribute && element.getAttribute('data-group')) {
      return element;
    }
    element = element.parentNode;
  }

  return null;
}

/**
 * @param {string} tag
 * @param {*} config
 * @returns {HTMLElement}
 */
export function createDocElement(tag, config = {}) {
  const el = document.createElement(tag);
  Object.keys(config).forEach((key) => {
    const value = config[key];
    if (key.indexOf('on') === 0) {
      el.addEventListener(key.substr(2).toLowerCase(), value, false);
    } else if (key === 'html') {
      el.innerHTML = value;
    } else if (key === 'className') {
      el.setAttribute('class', value);
    } else {
      el.setAttribute(key, value);
    }
  });

  return el;
}

/**
 * @param {HTMLElement} el
 * @param {*} styles
 */
export function setStyles(el, styles) {
  Object.keys(styles).forEach((key) => {
    const value = styles[key];
    el.style[key] = (typeof value === 'number') ? `${value}px` : value;
  });
}

/**
 * @param {string} css
 * @returns {null|string}
 */
export function cssExtractURL(css) {
  const matches = css.match(/url\("?(.*?)"?\)/);
  if (matches && matches[1] !== undefined) {
    return matches[1];
  }

  return null;
}

/**
 * @param {HTMLElement} element
 * @param {string} fontSize
 */
export const multilineEllipsis = (element, fontSize = '') => {
  let counter = 0;
  if (element && element.scrollHeight && element.scrollHeight > element.offsetHeight) {
    if (fontSize !== '') {
      element.style.fontSize = fontSize;
    }
    while (element.scrollHeight > element.offsetHeight) {
      element.textContent = element.textContent.replace(/\W*\s(\S)*$/, '...');
      if (counter++ > 2000) {
        break;
      }
    }
  }
};

export default {
  title,
  scroll,
  scrollToTop,
  scrollIntoView,
  iFrameDocument,
  iFrameSrc,
  setStyles,
  createDocElement,
  hasParentTag,
  hasParentClass,
  hasParentAttribute,
  hideScrollbars,
  showScrollbars,
  serializeForm,
  extractFormValue,
  multilineEllipsis,
  cssExtractURL,
  getParentDataGroup,
  getViewpointSize,
  getSelectedNode,
  storage: {
    getItem:  storageGetItem,
    setItem:  storageSetItem,
    pushItem: storagePushItem
  }
};
