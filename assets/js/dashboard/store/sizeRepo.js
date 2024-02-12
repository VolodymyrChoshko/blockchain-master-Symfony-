/**
 * @typedef Size
 * @property {string} type
 * @property {number} x
 * @property {number} y
 * @property {number} width
 * @property {number} height
 */

/**
 *
 */
class SizeRepo {
  sizes = {};

  /**
   * @param {number} id
   * @param {Size} size
   */
  setSize = (id, size) => {
    this.sizes[id] = size;
  };

  /**
   * @param id
   * @returns {*}
   */
  getSize = (id) => {
    return this.sizes[id];
  };

  /**
   * @returns {{}}
   */
  getSizes = () => {
    return this.sizes;
  };

  /**
   * @returns {{}}
   */
  getFolderSizes = () => {
    const sizes = {};
    Object.keys(this.sizes).forEach((key) => {
      if (this.sizes[key].type === 'folder') {
        sizes[key] = this.sizes[key];
      }
    });

    return sizes;
  };
}

export default new SizeRepo();
