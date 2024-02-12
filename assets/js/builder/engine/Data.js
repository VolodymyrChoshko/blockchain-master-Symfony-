import { DATA_BE_ID, DATA_BE_DATA } from './constants';

/**
 *
 */
class Data {
  /**
   * @param {Node|Element} element
   * @param {number} id
   */
  setBlockID = (element, id) => {
    element.setAttribute(DATA_BE_ID, id.toString());
  };

  /**
   * @param {Node|Element} element
   * @param {number} id
   */
  resetBlockID = (element, id) => {
    if (element.getAttribute(DATA_BE_ID) === null) {
      this.setBlockID(element, id);
    }
  };

  /**
   * @param {Node|Element} element
   * @returns {number}
   */
  getBlockID = (element) => {
    const id = element.getAttribute(DATA_BE_ID);
    if (id) {
      return parseInt(id, 10);
    }
    return 0;
  };

  /**
   * @param {Node|Element} element
   */
  removeBlockID = (element) => {
    element.removeAttribute(DATA_BE_ID);
  };

  /**
   * @param {Node|Element} element
   * @param {string} key
   * @param {*} defaultValue
   * @returns {*}
   */
  get = (element, key = '', defaultValue = null) => {
    let data = element.getAttribute(DATA_BE_DATA);
    if (data) {
      data = JSON.parse(decodeURIComponent(data));
    } else {
      data = {};
    }

    if (key !== '') {
      return data[key] || defaultValue;
    }
    return data;
  };

  /**
   * @param {Node|Element} element
   * @param {string} key
   * @param {*} value
   * @returns {*}
   */
  set = (element, key, value) => {
    const data = this.get(element);
    data[key]  = value;
    element.setAttribute(DATA_BE_DATA, encodeURIComponent(JSON.stringify(data)));

    return data;
  };

  /**
   * @param {Node|Element} element
   */
  removeBlockData = (element) => {
    element.removeAttribute(DATA_BE_DATA);
  };

  /**
   * @param {BlockCollection} blocks
   */
  removeBlockAttributes = (blocks) => {
    blocks.forEach((block) => {
      block.element.removeAttribute(DATA_BE_DATA);
    });
  };
}

export default new Data();
