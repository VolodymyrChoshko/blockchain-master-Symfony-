import css from 'css';
import { replaceAll } from 'utils';
import browser from 'utils/browser';
import { inlineStyles } from './constants';

/**
 * @param val
 * @returns {string|*}
 */
const trim = (val) => {
  if (!val || typeof val !== 'string') {
    return val;
  }

  return val.trim();
};

/**
 *
 */
class HTMLUtils {
  /**
   *
   */
  constructor() {
    this.supportsTemplates = ('content' in document.createElement('template'));
  }

  /**
   * @param element
   */
  findPreviousSiblingNode = (element) => {
    while (element.previousSibling) {
      if (element.previousSibling.nodeType !== 8 && element.previousSibling.nodeType !== 3) {
        return element.previousSibling;
      }
      console.log(element.previousSibling);
      element = element.previousSibling;
    }

    return null;
  };

  /**
   * @param {string} style
   * @returns {*}
   */
  getStyleObject = (style) => {
    if (!style) {
      return {};
    }

    return style.split(';').reduce((ruleMap, ruleString) => {
      const rulePair = ruleString.split(':');
      const k = trim(rulePair[0]);
      const v = trim(rulePair[1]);
      if (k && v) {
        ruleMap[k] = v;
      }

      return ruleMap;
    }, {});
  };

  /**
   * @param {HTMLElement} element
   * @param {string} key
   * @param {string} defaultValue
   * @returns {string}
   */
  getStyleValue = (element, key, defaultValue = '') => {
    const styles = this.getStyleObject(element.getAttribute('style') || '');
    if (styles[key]) {
      return styles[key];
    }

    return defaultValue;
  };

  /**
   * @param {HTMLElement} element
   * @param {string} key
   * @param {string} value
   */
  setStyleValue = (element, key, value) => {
    const styles = this.getStyleObject(element.getAttribute('style') || '');
    if (!value) {
      delete styles[key];
    } else {
      styles[key] = value;
    }
    element.setAttribute('style', this.serializeStyleObject(styles));
  };

  /**
   * @param {*} styleObject
   * @returns {string}
   */
  serializeStyleObject = (styleObject) => {
    const styles = [];
    Object.keys(styleObject).forEach((key) => {
      if (key && styleObject[key]) {
        styles.push(`${key}: ${styleObject[key]}`);
      }
    });

    return styles.join(';');
  };

  /**
   * @param {HTMLElement|Element|Node} element
   * @param {string} key
   * @param {string} defaultValue
   * @returns {string | string}
   */
  getAttribute = (element, key, defaultValue = '') => {
    return element.getAttribute(key) || defaultValue;
  };

  /**
   * @param {HTMLElement|Element|Node} element
   * @returns {{}}
   */
  getAttributes = (element) => {
    const attribs = {};
    Array.prototype.slice.call(element.attributes).forEach((cur) => {
      attribs[cur.name] = cur.value;
    });

    return attribs;
  };

  /**
   * @param {string} style
   * @returns {null|string}
   */
  extractStyleURL = (style) => {
    const matches = style.match(/url\("?(.*?)"?\)/);
    if (matches && matches[1] !== undefined) {
      return matches[1];
    }

    return null;
  };

  /**
   * Removes elements in the document between <!-- block-hide --> and <!-- end-block-hide -->
   *
   * @param {string} html
   */
  removeBlockHide = (html) => {
    let changed = html.replace('<!-- block-hide --> id=', '<!-- block-hide -->\n<tr id=');
    changed = changed.replace(/<!-- block-hide -->tr/g, '<!-- block-hide -->\n<tr');
    changed = changed.replace(/<!-- block-hide -->"HUB/g, '<!-- block-hide -->\n<tr id="HUB');
    changed = changed.replace(/<!-- block-hide -->d=/g, '<!-- block-hide -->\n<tr id=');
    // changed = changed.replace('<!-- block-hideHUB', '<!-- block-hide -->\n<tr id="HUB');
    // changed = changed.replace('<!-- block-hider id="HUB', '<!-- block-hide -->\n<tr id="HUB');
    if (html !== changed) {
      changed = changed.replace('<!-- end-block-hide -->tr>', '<!-- end-block-hide -->\n<tr>');
    }
    html = changed;

    html = html.replace(/<!-- block-hide -->/g, '<!-- block-hide-hidden ');
    html = html.replace(/<!-- end-block-hide -->/g, ' hidden-end-block-hide -->');

    return html;

    const len  = html.length;
    const lStart = '<!-- block-hide -->'.length;
    const lEnd = '<!-- end-block-hide -->'.length;
    let buffer = '';
    for (let i = 0; i < len; i++) {
      const char = html[i];
      if (html.substr(i, lStart) === '<!-- block-hide -->') {
        buffer += '<!-- block-hide';
        i += lStart;
      } else if (html.substr(i, lEnd) === '<!-- end-block-hide -->') {
          buffer += 'end-block-hide -->';
          i += lEnd;
      } else {
        buffer += char;
      }
    }

    return buffer;
  }

  /**
   * @param {string} html
   * @returns {string}
   */
  restoreBlockHide = (html) => {
    html = html.replace(/<!-- block-hide-hidden /g, '<!-- block-hide -->');
    html = html.replace(/ hidden-end-block-hide -->/g, '<!-- end-block-hide -->');

    return html;

    const len  = html.length;
    let buffer = '';
    for (let i = 0; i < len; i++) {
      const char = html[i];
      if (html.substr(i, 15) === '<!-- block-hide') {
        buffer += '<!-- block-hide -->';
        i += 15;
      } else if (html.substr(i, 18) === 'end-block-hide -->') {
        buffer += '<!-- end-block-hide -->';
        i += 18;
      } else {
        buffer += char;
      }
    }

    return buffer;
  };

  /**
   * @param element
   * @param dataBlock
   * @param color
   * @param iframe
   */
  replaceBackgroundColor = (element, dataBlock, color, iframe) => {
    this.traverseTree(browser.iFrameDocument(iframe).body, (node) => {
      if (node.nodeType === 8 && node.nodeValue) {
        const tag = document.createElement('div');
        tag.innerHTML = trim(node.nodeValue);
        const found = tag.querySelector(`[data-block="${dataBlock}"]`);
        if (found) {
          const orig     = found.getAttribute('bgcolor');
          node.nodeValue = node.nodeValue.replace(orig, color);
          return false;
        }
      }

      return undefined;
    });
  };

  /**
   * @param {HTMLElement} element
   * @param {string} src
   * @param {string} original
   * @param {HTMLIFrameElement} iframe
   */
  replaceBackgroundImage = (element, src, original, iframe) => {
    let oldBkg     = '';
    const html     = element.innerHTML;
    const variable = element.getAttribute('data-variable') || element.getAttribute('data-group') || element.getAttribute('data-block');
    const bkgAttr  = element.getAttribute('background');

    // <div background="foo.jpg" />
    if (bkgAttr !== null) {
      oldBkg = bkgAttr;
      element.setAttribute('background', src);
      if (oldBkg) {
        element.innerHTML = replaceAll(html, oldBkg, src);
      }
    }

    // <div style="background: url(foo.jpg)" />
    const bkgStyle = this.getAttribute(element, 'style');
    if (bkgStyle.indexOf('background:') !== -1) {
      const style = element.style.background;
      oldBkg      = this.extractStyleURL(style);
      element.setAttribute('original-bg-link', src);
      element.style.background = style.replace(/url\(.*?\)/, `url(${src})`);
      element.innerHTML        = replaceAll(html, oldBkg, src);
    }

    // <div style="background-image: url(foo.jpg)" />
    if (bkgStyle.indexOf('background-image:') !== -1) {
      const style = element.style.backgroundImage;
      oldBkg      = this.extractStyleURL(style);
      element.setAttribute('original-bg-link', src);
      element.style.backgroundImage = style.replace(/url\(.*?\)/, `url(${src})`);
      element.innerHTML             = replaceAll(html, oldBkg, src);
    }

    // Handle v:fill background image hack.
    // @see https://3.basecamp.com/3140512/buckets/8093355/todos/1865520252
    // @see https://www.emailonacid.com/blog/article/email-development/emailology_vector_markup_language_and_backgrounds/
    if (variable) {
      this.traverseTree(browser.iFrameDocument(iframe).body, (node) => {
        try {
          if (node.nodeType === 8 && node.nodeValue) {
            let tag = document.createElement('div');
            tag.innerHTML = trim(node.nodeValue);
            let found = tag.querySelector('v\\:fill');
            if (found) {
              const orig     = found.getAttribute('src');
              node.nodeValue = node.nodeValue.replace(orig, src);
            }

            tag = document.createElement('div');
            tag.innerHTML = trim(node.nodeValue);
            found = tag.querySelector('v\\:fill');
            if (found) {
              const orig     = found.getAttribute('src');
              node.nodeValue = node.nodeValue.replace(orig, src);
            }
          }
        } catch (err) {
          console.warn(err);
        }
      });
    }

    element.setAttribute('original-bg', original);
  };

  /**
   * @param element
   * @param dataBlock
   * @param href
   * @param iframe
   */
  replaceHrefComment = (element, dataBlock, href, iframe) => {
    this.traverseTree(browser.iFrameDocument(iframe).body, (node) => {
      if (node.nodeType === 8 && node.nodeValue) {
        const tag = document.createElement('div');
        tag.innerHTML = trim(node.nodeValue);
        const found = tag.querySelector(`[data-block="${dataBlock}"]`);
        if (found) {
          const orig     = found.getAttribute('href');
          node.nodeValue = node.nodeValue.replace(orig, href);
          return false;
        }
      }

      return undefined;
    });
  };

  /**
   * @param {HTMLElement} element
   * @returns {string}
   */
  getBackgroundImage = (element) => {
    const bkgAttr = element.getAttribute('background');
    if (bkgAttr !== null) {
      return bkgAttr;
    }

    // <div style="background: url(foo.jpg)" />
    const bkgStyle = this.getAttribute(element, 'style');
    if (bkgStyle.indexOf('background:') !== -1) {
      const style = element.style.background;
      return this.extractStyleURL(style);
    }

    // <div style="background-image: url(foo.jpg)" />
    if (bkgStyle.indexOf('background-image:') !== -1) {
      const style = element.style.backgroundImage;
      return this.extractStyleURL(style);
    }

    return '';
  };

  /**
   * @param {HTMLElement} node
   * @param {Function} cb
   */
  traverseTree = (node, cb) => {
    if (node) {
      const { childNodes } = node;

      for (let i = 0; i < childNodes.length; i++) {
        const childNode = childNodes[i];
        if (cb(childNode) === false) {
          break;
        }
        this.traverseTree(childNode, cb);
      }
    }
  };

  /**
   * @param {string} html
   * @param {Document|null} doc
   * @returns {Element|HTMLElement}
   */
  createElement = (html, doc = null) => {
    if (doc === null) {
      doc = document;
    }
    if (this.supportsTemplates) {
      const template = doc.createElement('template');
      template.innerHTML = html.trim();
      return template.content.firstChild;
    }

    const fragment = doc.createElement('div');
    fragment.innerHTML = html.trim();

    return fragment.firstChild;
  };

  /**
   * @param {Element} referenceNode
   * @param {Element} el
   */
  insertAfter = (referenceNode, el) => {
    referenceNode.parentNode.insertBefore(el, referenceNode.nextSibling);
  };

  /**
   * @param {Element} referenceNode
   * @param {Element} el
   */
  insertBefore = (referenceNode, el) => {
    referenceNode.parentNode.insertBefore(el, referenceNode);
  };

  /**
   * Inlines -block-* selectors from inside of <style> tags into the html elements
   *
   * @param {Document} doc
   */
  inlineStylesheetBEStyles = (doc) => {
    const beDeclarations = Object.keys(inlineStyles).map(v => `-block-${v}`);
    doc.querySelectorAll('style').forEach((style) => {
      const parsed  = css.parse(style.innerText, {
        silent: true
      });

      if (parsed) {
        parsed.stylesheet.rules.forEach((rule) => {
          if (rule.declarations) {
            rule.declarations.forEach((d) => {
              if (beDeclarations.indexOf(d.property) !== -1) {
                rule.selectors.forEach((selector) => {
                  const elements = doc.querySelectorAll(selector);
                  elements.forEach((element) => {
                    let s = element.getAttribute('style');
                    if (s) {
                      s = `${s};${d.property}: ${d.value};`;
                    } else {
                      s = `${d.property}: ${d.value};`;
                    }
                    element.setAttribute('style', s);
                  });
                });
              }
            });
          }
        });
      }
    });
  };

  /**
   * @param {Document} doc
   * @param {string} scheme
   * @returns {boolean}
   */
  switchPreferredColorScheme = (doc, scheme) => {
    const meta = doc.querySelector('meta[name="color-scheme"]');
    if (!meta) {
      return false;
    }
    if (meta.getAttribute('content').indexOf(scheme) === -1) {
      console.error(`Color scheme ${scheme} invalid.`);
      return false;
    }
    if (meta.getAttribute('content') === 'light only') {
      return false;
    }

    this.removePreferredColorScheme(doc);

    const changed = [];
    doc.querySelectorAll('style').forEach((style) => {
      let completed = '';
      const text    = style.innerText;
      const parsed  = css.parse(text, {
        silent: true
      });
      if (parsed) {
        parsed.stylesheet.rules.forEach((rule) => {
          if (rule.type === 'media') {
            const matches = rule.media.match(/\(prefers-color-scheme:\s*([\w-]+)\)/);
            if (matches) {
              if (matches[1] === scheme) {
                rule.rules.forEach((r) => {
                  const selectors    = r.selectors.join(', ');
                  const declarations = [];
                  r.declarations.forEach((d) => {
                    declarations.push(`${d.property}: ${d.value};`);
                  });
                  completed = `${completed}\n${selectors} { ${declarations.join(' ')} }`;
                });
              } else if (scheme === 'light') {
                style.innerHTML = style.innerHTML.replace(
                  '(prefers-color-scheme: dark)',
                  '(prefers-color-scheme: dark-be)'
                );
              } else {
                style.innerHTML = style.innerHTML.replace(
                  '(prefers-color-scheme: light)',
                  '(prefers-color-scheme: light-be)'
                );
              }
            }
          }
        });

        if (completed !== '') {
          changed.push(completed);
        }
      }
    });

    if (changed.length > 0) {
      const style = doc.createElement('style');
      style.setAttribute('id', 'be-color-scheme-styles');
      style.innerHTML = changed.join('\n');
      doc.body.appendChild(style);
    }

    return true;
  };

  /**
   * @param {Document} doc
   */
  removePreferredColorScheme = (doc) => {
    const s = doc.getElementById('be-color-scheme-styles');
    if (s) {
      s.remove();
    }

    doc.querySelectorAll('style').forEach((style) => {
      style.innerHTML = style.innerHTML.replace('(prefers-color-scheme: dark-be)', '(prefers-color-scheme: dark)');
      style.innerHTML = style.innerHTML.replace('(prefers-color-scheme: white-be)', '(prefers-color-scheme: white)');
    });
  };

  /**
   * @param {Node|Element} element
   */
  findRepeatCount = (element) => {
    const { tagName } = element;

    let total = 1;

    /**
     * @param {Node|Element} node
     */
    const countPrevious = (node) => {
      const { previousElementSibling: prev } = node;

      if (prev && prev.getAttribute('class') === element.getAttribute('class') && prev.tagName === tagName) {
        total += 1;
        countPrevious(prev);
      }
    };

    /**
     * @param {Node|Element} node
     */
    const countNext = (node) => {
      const { nextElementSibling: next } = node;

      if (next && next.getAttribute('class') === element.getAttribute('class') && next.tagName === tagName) {
        total += 1;
        countNext(next);
      }
    };

    countPrevious(element);
    countNext(element);

    return total;
  };
}

export default new HTMLUtils();
