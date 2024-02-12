import * as constants from './constants';

/**
 *
 */
class Zone {
  /**
   * @type {number}
   */
  id = 0;

  /**
   * @type {string}
   */
  type = '';

  /**
   * @type {{}}
   */
  styles = {};

  /**
   * @type {boolean}
   */
  empty = false;

  /**
   * @type {boolean}
   * @private
   */
  _isCode = false;

  /**
   * @param {number} id
   * @param {string} type
   * @param {*} styles
   * @param {*} data
   */
  constructor(id, type, styles, data = {}) {
    this.id      = id;
    this.type    = type;
    this.styles  = styles;
    this.empty   = data.empty || false;
    this._isCode = data.isCode || false;
  }

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
  isCode = () => {
    return this._isCode;
  };
}

export default Zone;
