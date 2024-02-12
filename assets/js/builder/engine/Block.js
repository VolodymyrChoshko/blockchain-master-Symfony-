import BlockCollection from './BlockCollection';
import * as constants from './constants';
import Data from './Data';

/**
 *
 */
class Block {
  /**
   * @param {number} id
   * @param {string} type
   * @param {Element|HTMLElement} element
   * @param {*} inline
   * @param {*} data
   */
  constructor(id, type, element, inline, data) {
    this.id   = id;
    this.type = type;
    this.tag  = data.tag;
    this.update(element, inline, data);
  }

  /**
   * @param element
   * @param inline
   * @param data
   */
  update = (element, inline, data) => {
    this.element           = element;
    this.inline            = inline;

    /** @type {Rules} */
    this.rules             = data.rules || {};
    this.parentSectionID   = data.parentSectionID || -1;
    this.parentRegionID    = data.parentRegionID || -1;
    this.parentComponentID = data.parentComponentID || -1;
    this.children          = data.children || [];
    this.styles            = data.styles || {};
    this.variations        = data.variations || new BlockCollection();
    this.activeVariationID = data.activeVariationID || -1;
    this.origStyles        = data.origStyles || { display: '' };
    this.sectionId         = Number(data.sectionId || 0);
    this.componentId       = Number(data.componentId || 0);
    this.rect              = element.getBoundingClientRect();
    this.empty             = element.classList.contains('block-section-empty');
    this.title             = element.getAttribute(constants.DATA_TITLE) || '';
    this.anchorStyles      = [];
    this.links             = [];
    this.groupName         = '';
    this.group             = null;
    this._isCode           = data.isCode || false;
    this._hasBackground    = false;

    element.querySelectorAll('a').forEach((el) => {
      this.links.push(el.getAttribute('href'));
    });
  };

  /**
   * @returns {boolean}
   */
  hasVariations = () => {
    return this.groupName !== '';
  };

  /**
   * @returns {boolean}
   */
  isImage = () => {
    return this.tag === 'img' || this.type === constants.BLOCK_BACKGROUND;
  };

  /**
   * @returns {boolean}
   */
  isText = () => {
    return this.rules.isEditable;
  };

  /**
   * @returns {boolean}
   */
  isEdit = () => {
    return this.type === constants.BLOCK_EDIT;
  };

  /**
   * @returns {boolean}
   */
  isSection = () => {
    return this.type === constants.BLOCK_SECTION;
  };

  /**
   * @returns {boolean}
   */
  isComponent = () => {
    return this.type === constants.BLOCK_COMPONENT;
  };

  /**
   * @returns {boolean}
   */
  isRegion = () => {
    return this.type === constants.BLOCK_REGION;
  };

  /**
   * @returns {boolean}
   */
  isBackgroundColor = () => {
    return this.type === constants.BLOCK_BG_COLOR;
  };

  /**
   * @returns {boolean}
   */
  isBackground = () => {
    return this.type === constants.BLOCK_BACKGROUND;
  };

  /**
   * @returns {boolean}
   */
  hasBackground = () => {
    return this._hasBackground;
  };

  /**
   * @param {boolean} hasBackground
   */
  setBackground = (hasBackground) => {
    this._hasBackground = hasBackground;
  };

  /**
   * @returns {boolean}
   */
  isCode = () => {
    return this._isCode;
  };

  /**
   *
   */
  show = () => {
    this.element.style.display = this.origStyles.display;
    this.rect = this.element.getBoundingClientRect();
  };

  /**
   *
   */
  hide = () => {
    this.rect = this.element.getBoundingClientRect();
    this.element.style.display = 'none';
  };

  /**
   * @param {string} key
   * @param {*} defaultValue
   * @returns {*}
   */
  data = (key = '', defaultValue = null) => {
    return Data.get(this.element, key, defaultValue);
  };
}

export default Block;
