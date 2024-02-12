import { uniqueID } from 'utils';

/**
 *
 */
class Styler {
  /**
   * @param {Document} doc
   */
  constructor(doc) {
    this.doc = doc;
  }

  /**
   * @param {string} styles
   * @param {string} cn
   * @returns {string}
   */
  create = (styles, cn = '') => {
    const className      = cn || uniqueID('be-styler-');
    this.style           = this.doc.createElement('style');
    this.style.innerHTML = `.${className} { ${styles} }`;
    this.doc.querySelector('head').appendChild(this.style);
    // this.doc.body.appendChild(this.style);

    return className;
  };

  /**
   * @param {string} selector
   * @param {string} cn
   * @returns {string}
   */
  createFromWindowCSS = (selector, cn = '') => {
    const styles = this.getWindowCSSStyle(selector);

    return this.create(styles, cn);
  };

  /**
   *
   */
  destroy = () => {
    if (this.style) {
      this.style.remove();
      this.style = null;
    }
  };

  /**
   * @param {string} className
   * @returns {string}
   */
  getWindowCSSStyle = (className) => {
    const { styleSheets } = window.document;
    const styleSheetsLength = styleSheets.length;
    for (let i = 0; i < styleSheetsLength; i++) {
      /** @type CSSStyleSheet */
      const styleSheet = styleSheets[i];

      /** @type CSSRuleList */
      let classes;
      try {
        if (styleSheet.rules) {
          classes = styleSheet.rules;
        } else {
          classes = styleSheet.cssRules;
        }
        // eslint-disable-next-line no-empty
      } catch (error) {}
      if (!classes) {
        // eslint-disable-next-line no-continue
        continue;
      }

      const classesLength = classes.length;
      for (let x = 0; x < classesLength; x++) {
        /** @type CSSStyleRule */
        const rule = classes[x];
        const regex = new RegExp(`${className.replace('.', '\\.')}\\b`);
        if (rule.selectorText && rule.selectorText.match(regex)) {
          return rule.style.cssText;
        }
      }
    }

    return '';
  };
}

export default Styler;
