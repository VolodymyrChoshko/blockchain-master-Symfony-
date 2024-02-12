import * as constants from './constants';
import HTMLUtils from './HTMLUtils';
import Data from './Data';

const maxCharRegex = new RegExp('block-maxchar-([\\d]+)');
const minCharRegex = new RegExp('block-minchar-([\\d]+)');
const repeatRegex  = new RegExp('block-repeat-([\\d]+)');

/**
 *
 */
class Rules {
  /**
   * @type {Element|HTMLElement}
   * @private
   */
  element = undefined;

  /**
   * @type {*}
   * @private
   */
  styleRules = [];

  /**
   * @type {boolean}
   * @private
   */
  _canLink = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canBold = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canItalic = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canText = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canAnchorEdit = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canRepeat = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canRemove = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canResize = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canSuperscript = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canSubscript = undefined;

  /**
   * @type {number}
   * @private
   */
  _minChars  = undefined;

  /**
   * @type {number}
   * @private
   */
  _maxChars  = undefined;

  /**
   * @type {number}
   * @private
   */
  _maxRepeat = undefined;

  /**
   * @type {number}
   * @private
   */
  _maxWidth = undefined;

  /**
   * @type {number}
   * @private
   */
  _maxHeight = undefined;

  /**
   * @type {number}
   * @private
   */
  _minWidth = undefined;

  /**
   * @type {number}
   * @private
   */
  _minHeight = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canChangeImg = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _canDropAround = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _isAutoHeight = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _isAutoWidth =  undefined;

  /**
   * @type {boolean}
   * @private
   */
  _movesUp = undefined;

  /**
   * @type {boolean}
   * @private
   */
  _movesDown = undefined;

  /**
   * @param {HTMLElement|Node} element
   */
  constructor(element) {
    this.element    = element;
    this.styleRules = Object.assign({}, element.beInline);
    this.styles     = HTMLUtils.getStyleObject(HTMLUtils.getAttribute(element, 'style'));
    this.classes    = HTMLUtils.getAttribute(element, 'class');
  }

  /**
   * @returns {boolean}
   */
  get canLink() {
    if (this._canLink === undefined) {
      if (this.contains(constants.CLASS_BLOCK_NO_LINK)) {
        this._canLink = false;
      } else {
        this._canLink = this.styleRule('canLink');
      }
    }

    return this._canLink;
  }

  /**
   * @param {boolean} val
   */
  set canLink(val) {
    this._canLink = val;
    this.toggleClass(!val, constants.CLASS_BLOCK_NO_LINK);
  }

  /**
   * @returns {boolean}
   */
  get canBold() {
    if (this._canBold === undefined) {
      if (this.contains(constants.CLASS_BLOCK_NO_BOLD)) {
        this._canBold = false;
      } else {
        this._canBold = this.styleRule('canBold');
      }
    }

    return this._canBold;
  }

  /**
   * @param {boolean} val
   */
  set canBold(val) {
    this._canBold = val;
    this.toggleClass(!val, constants.CLASS_BLOCK_NO_BOLD);
  }

  /**
   * @returns {boolean}
   */
  get canItalic() {
    if (this._canItalic === undefined) {
      if (this.contains(constants.CLASS_BLOCK_NO_ITALIC)) {
        this._canItalic = false;
      } else {
        this._canItalic = this.styleRule('canItalic');
      }
    }

    return this._canItalic;
  }

  /**
   * @param {boolean} val
   */
  set canItalic(val) {
    this._canItalic = val;
    this.toggleClass(!val, constants.CLASS_BLOCK_NO_ITALIC);
  }

  /**
   * @returns {boolean}
   */
  get canText() {
    if (this._canText === undefined) {
      if (this.contains(constants.CLASS_BLOCK_NO_TEXT)) {
        this._canText = false;
      } else {
        this._canText = this.styleRule('canText');
      }
    }

    return this._canText;
  }

  /**
   * @param {boolean} val
   */
  set canText(val) {
    this._canText = val;
    this.toggleClass(!val, constants.CLASS_BLOCK_NO_TEXT);
  }

  /**
   * @returns {boolean}
   */
  get canRepeat() {
    if (this._canRepeat === undefined) {
      if (this.contains(constants.CLASS_BLOCK_REPEAT) || this.classes.match(repeatRegex)) {
        this._canRepeat = true;
      } else {
        this._canRepeat = this.styleRule('canRepeat');
      }
    }

    return this._canRepeat;
  }

  /**
   * @param {boolean} val
   */
  set canRepeat(val) {
    this._canRepeat = val;
    this.toggleClass(val, constants.CLASS_BLOCK_REPEAT);
  }

  /**
   * @returns {boolean}
   */
  get canRemove() {
    if (this._canRemove === undefined) {
      if (this.contains(constants.CLASS_BLOCK_BACKGROUND)) {
        this._canRemove = false;
      } else {
        this._canRemove = this.styleRule('canRemove');
      }

      if (this.contains(constants.CLASS_BLOCK_REMOVE)
        || this.contains(constants.CLASS_BLOCK_SECTION)
        || Data.get(this.element, constants.BLOCK_DATA_CAN_REMOVE)
      ) {
        this._canRemove = true;
      }

      if (!this._canRemove && this.canRepeat) {
        const { previousSibling } = this.element;

        if (previousSibling && previousSibling.nodeType !== 3 && previousSibling.nodeType !== 8) {
          const pc = HTMLUtils.getAttribute(previousSibling, 'class');
          if (pc !== '') {
            if (previousSibling.classList.contains(constants.CLASS_BLOCK_REPEAT) || pc.match(repeatRegex)) {
              this._canRemove = true;
            }
          }
        }
      }
    }

    return this._canRemove;
  }

  /**
   * @param {boolean} val
   */
  set canRemove(val) {
    this._canRemove = val;
    this.toggleClass(val, constants.CLASS_BLOCK_REMOVE);
  }

  /**
   * @returns {boolean}
   */
  get canChangeImg() {
    if (this._canChangeImg === undefined) {
      this._canChangeImg = !this.contains(constants.CLASS_BLOCK_NO_IMAGE);
    }

    return this._canChangeImg;
  }

  /**
   * @param {boolean} val
   */
  set canChangeImg(val) {
    this._canChangeImg = val;
    this.toggleClass(!val, constants.CLASS_BLOCK_NO_IMAGE);
  }

  /**
   * @returns {boolean}
   */
  get canDropAround() {
    if (this._canDropAround === undefined) {
      if (this.element.getAttribute(constants.DATA_DROPPABLE) === '0') {
        this._canDropAround = false;
      } else {
        this._canDropAround = this.element.getAttribute(constants.DATA_DROPPABLE) !== '0';
      }
    }

    return this._canDropAround;
  }

  /**
   * @param {boolean} val
   */
  set canDropAround(val) {
    this._canDropAround = val;
  }

  /**
   * @returns {boolean}
   */
  get canAnchorEdit() {
    if (this._canAnchorEdit === undefined) {
      this._canAnchorEdit = this.contains(constants.CLASS_BLOCK_ANCHOR);
    }

    return this._canAnchorEdit;
  }

  /**
   * @param {boolean} val
   */
  set canAnchorEdit(val) {
    this._canAnchorEdit = val;
    this.toggleClass(val, constants.CLASS_BLOCK_ANCHOR);
  }

  /**
   * @returns {boolean}
   */
  get canResize() {
    if (this._canResize === undefined) {
      this._canResize = this.contains(constants.CLASS_BLOCK_RESIZE);
    }

    return this._canResize;
  }

  /**
   * @param {boolean} val
   */
  set canResize(val) {
    this._canResize = val;
    this.toggleClass(val, constants.CLASS_BLOCK_RESIZE);
  }

  /**
   * @returns {boolean}
   */
  get canSuperscript() {
    if (this._canSuperscript === undefined) {
      this._canSuperscript = !this.contains(constants.CLASS_BLOCK_NO_SUPERSCRIPT);
    }

    return this._canSuperscript;
  }

  /**
   * @param {boolean} val
   */
  set canSuperscript(val) {
    this._canSuperscript = val;
    this.toggleClass(!val, constants.CLASS_BLOCK_NO_SUPERSCRIPT);
  }

  /**
   * @returns {boolean}
   */
  get canSubscript() {
    if (this._canSubscript === undefined) {
      this._canSubscript = !this.contains(constants.CLASS_BLOCK_NO_SUBSCRIPT);
    }

    return this._canSubscript;
  }

  /**
   * @param {boolean} val
   */
  set canSubscript(val) {
    this._canSubscript = val;
    this.toggleClass(!val, constants.CLASS_BLOCK_NO_SUBSCRIPT);
  }

  /**
   * @returns {number}
   */
  get minChars() {
    if (this._minChars === undefined) {
      this._minChars = 0;
      const match = this.classes.match(minCharRegex);
      if (match) {
        this._minChars = parseInt(match[1], 10);
      }
    }

    return this._minChars;
  }

  /**
   * @param {number} val
   */
  set minChars(val) {
    const match = this.classes.match(minCharRegex);
    if (match) {
      this.element.classList.remove(match[0]);
    }

    this._minChars = parseInt(val.toString(), 10);
    if (val) {
      this.element.classList.add(`block-minchar-${val}`);
    }

    this.classes = HTMLUtils.getAttribute(this.element, 'class');
  }

  /**
   * @returns {number}
   */
  get maxChars() {
    if (this._maxChars === undefined) {
      this._maxChars = 0;
      const match = this.classes.match(maxCharRegex);
      if (match) {
        this._maxChars = parseInt(match[1], 10);
      }
    }

    return this._maxChars;
  }

  /**
   * @param {number} val
   */
  set maxChars(val) {
    const match = this.classes.match(maxCharRegex);
    if (match) {
      this.element.classList.remove(match[0]);
    }

    this._maxChars = parseInt(val.toString(), 10);
    if (val) {
      this.element.classList.add(`block-maxchar-${val}`);
    }

    this.classes = HTMLUtils.getAttribute(this.element, 'class');
  }

  /**
   * @returns {number}
   */
  get maxHeight() {
    if (this._maxHeight === undefined) {
      if (this.styles['max-height']) {
        this._maxHeight = parseInt(this.styles['max-height'], 10);
      } else {
        this._maxHeight = 0;
      }
    }

    return this._maxHeight;
  }

  /**
   * @param {number} val
   */
  set maxHeight(val) {
    this._maxHeight = parseInt(val.toString(), 10);
    this.setStyle('max-height', val ? `${val}px` : '');
  }

  /**
   * @returns {number}
   */
  get maxWidth() {
    if (this._maxWidth === undefined) {
      if (this.styles['max-width']) {
        this._maxWidth = parseInt(this.styles['max-width'], 10);
      } else {
        this._maxWidth = 0;
      }
    }

    return this._maxWidth;
  }

  /**
   * @param {number} val
   */
  set maxWidth(val) {
    this._maxWidth = parseInt(val.toString(), 10);
    this.setStyle('max-width', val ? `${val}px` : '');
  }

  /**
   * @returns {number}
   */
  get minHeight() {
    if (this._minHeight === undefined) {
      if (this.styles['min-height']) {
        this._minHeight = parseInt(this.styles['min-height'], 10);
      } else {
        this._minHeight = 0;
      }
    }

    return this._minHeight;
  }

  /**
   * @param {number} val
   */
  set minHeight(val) {
    this._minHeight = parseInt(val.toString(), 10);
    this.setStyle('min-height', val ? `${val}px` : '');
  }

  /**
   * @returns {number}
   */
  get minWidth() {
    if (this._minWidth === undefined) {
      if (this.styles['min-width']) {
        this._minWidth = parseInt(this.styles['min-width'], 10);
      } else {
        this._minWidth = 0;
      }
    }

    return this._minWidth;
  }

  /**
   * @param {number} val
   */
  set minWidth(val) {
    this._minWidth = parseInt(val.toString(), 10);
    this.setStyle('min-width', val ? `${val}px` : '');
  }

  /**
   * @returns {number}
   */
  get maxRepeat() {
    if (this._maxRepeat === undefined) {
      this._maxRepeat = 0;
      const match = this.classes.match(repeatRegex);
      if (match) {
        this._maxRepeat = parseInt(match[1], 10);
      }
    }

    return this._maxRepeat;
  }

  /**
   * @param {number} val
   */
  set maxRepeat(val) {
    const match = this.classes.match(repeatRegex);
    if (match) {
      this.element.classList.remove(match[0]);
    }

    this._maxRepeat = parseInt(val.toString(), 10);
    if (val) {
      this.element.classList.add(`block-repeat-${val}`);
    }

    this.classes = HTMLUtils.getAttribute(this.element, 'class');
  }

  /**
   * @returns {boolean}
   */
  get isAutoHeight() {
    if (this._isAutoHeight === undefined) {
      this._isAutoHeight = false;
      if (this.element.tagName === 'IMG') {
        const style    = HTMLUtils.getAttribute(this.element, 'style');
        const styleObj = HTMLUtils.getStyleObject(style);
        if (styleObj.height && styleObj.height === 'auto') {
          this._isAutoHeight = true;
        }
      } else if (this.classes.indexOf(constants.CLASS_BLOCK_BACKGROUND) !== -1) {
        this._isAutoHeight = true;
      }
    }

    return this._isAutoHeight;
  }

  /**
   * @param {boolean} val
   */
  set isAutoHeight(val) {
    this._isAutoHeight = val;

    let style    = HTMLUtils.getAttribute(this.element, 'style');
    const styleObj = HTMLUtils.getStyleObject(style);
    if (val) {
      styleObj.height = 'auto';
      this.element.setAttribute('height', 'auto');
    } else {
      delete styleObj.height;
      this.element.removeAttribute('height');
    }
    style = HTMLUtils.serializeStyleObject(styleObj);
    this.element.setAttribute('style', style);
  }

  /**
   * @returns {boolean}
   */
  get isAutoWidth() {
    if (this._isAutoWidth === undefined) {
      this._isAutoWidth = false;
      if (this.element.tagName === 'IMG') {
        const style    = HTMLUtils.getAttribute(this.element, 'style');
        const styleObj = HTMLUtils.getStyleObject(style);
        if (styleObj.width && styleObj.width === 'auto') {
          this._isAutoWidth = true;
        }
      }
    }

    return this._isAutoWidth;
  }

  /**
   * @param {boolean} val
   */
  set isAutoWidth(val) {
    this._isAutoWidth = val;
  }

  /**
   * @returns {boolean}
   */
  get movesUp() {
    if (this._movesUp === undefined) {
      const { previousSibling, previousElementSibling } = this.element;
      this._movesUp = false;

      if (this.canRepeat) {
        if (previousSibling && previousSibling.nodeType !== 3 && previousSibling.nodeType !== 8) {
          const pc = HTMLUtils.getAttribute(previousSibling, 'class');
          if (pc !== '') {
            if (previousSibling.classList.contains(constants.CLASS_BLOCK_REPEAT) || pc.match(repeatRegex)) {
              this._movesUp   = true;
            }
          }
        }
      } else if (
        previousElementSibling
        && previousElementSibling.nodeType !== 3
        && previousElementSibling.nodeType !== 8
        && previousElementSibling.classList.contains(constants.CLASS_BLOCK_SECTION)
        && !this.element.classList.contains(constants.CLASS_BLOCK_REGION)
      ) {
        this._movesUp = true;
      }
    }

    return this._movesUp;
  }

  /**
   * @param {boolean} val
   */
  set movesUp(val) {
    this._movesUp = val;
  }

  /**
   * @returns {boolean}
   */
  get movesDown() {
    if (this._movesDown === undefined) {
      const { nextSibling, nextElementSibling } = this.element;

      if (this.canRepeat) {
        if (nextSibling && nextSibling.nodeType !== 3 && nextSibling.nodeType !== 8) {
          const nc = HTMLUtils.getAttribute(nextSibling, 'class');
          if (nc !== '') {
            if (nextSibling.classList.contains(constants.CLASS_BLOCK_REPEAT) || nc.match(repeatRegex)) {
              this._movesDown = true;
            }
          }
        }
      } else if (
        nextElementSibling
        && nextElementSibling.nodeType !== 3
        && nextElementSibling.nodeType !== 8
        && nextElementSibling.classList.contains(constants.CLASS_BLOCK_SECTION)
        && !nextElementSibling.classList.contains(constants.CLASS_BLOCK_SCRIPT_SEC)
        && !this.element.classList.contains(constants.CLASS_BLOCK_REGION)
      ) {
        this._movesDown = true;
      }
    }

    return this._movesDown;
  }

  /**
   * @param {boolean} val
   */
  set movesDown(val) {
    this._movesDown = val;
  }

  /**
   * @param {string} className
   * @returns {boolean}
   * @private
   */
  contains = (className) => {
    return this.element.classList.contains(className);
  };

  /**
   * @param val
   * @param className
   * @private
   */
  toggleClass = (val, className) => {
    if (val) {
      this.element.classList.add(className);
    } else {
      this.element.classList.remove(className);
    }

    this.classes = HTMLUtils.getAttribute(this.element, 'class');
  };

  /**
   * @param {string} key
   * @returns {*}
   */
  styleRule = (key) => {
    const name = key.substr(3).toLowerCase();

    return this.styleRules[name] !== undefined ? this.styleRules[name] : constants.defaultRules[key];
  };

  /**
   * @param key
   * @param value
   */
  setStyle = (key, value) => {
    let style      = HTMLUtils.getAttribute(this.element, 'style');
    const styleObj = HTMLUtils.getStyleObject(style);
    if (value) {
      styleObj[key] = value;
    } else {
      delete styleObj[key];
    }
    style = HTMLUtils.serializeStyleObject(styleObj);
    this.element.setAttribute('style', style);
  };
}

export default Rules;
