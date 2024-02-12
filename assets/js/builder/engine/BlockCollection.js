/**
 *
 */
class BlockCollection {
  /**
   * @type {Block[]}
   */
  blocks = [];

  /**
   * @type {{}}
   */
  idMap = {};

  /**
   * @type {number}
   */
  length = 0;

  /**
   * @param {Block[]} blocks
   */
  constructor(blocks = []) {
    blocks.forEach((b) => {
      this.add(b);
    });
  }

  /**
   * @param {Block} block
   * @returns {number}
   */
  add = (block) => {
    this.length = this.blocks.push(block);
    this.idMap[block.id] = this.length - 1;

    return this.length;
  };

  /**
   * @param {number} id
   * @returns {Block|null}
   */
  getByID = (id) => {
    const index = this.getIndexByID(id);
    if (index === -1) {
      return null;
    }

    return this.blocks[index];
  };

  /**
   * @param {number} id
   * @param {Block} block
   * @returns {boolean}
   */
  setByID = (id, block) => {
    const index = this.getIndexByID(id);
    if (index === -1) {
      return false;
    }

    this.blocks[index] = block;
    return true;
  };

  /**
   * @param {number} id
   * @returns {number}
   */
  getIndexByID = (id) => {
    if (this.idMap[id] === undefined) {
      return -1;
    }
    return this.idMap[id];
  };

  /**
   * @param {number} id
   * @returns {number}
   */
  removeByID = (id) => {
    const index = this.getIndexByID(id);
    this.blocks.splice(index, 1);
    this.length -= 1;

    return this.length;
  };

  /**
   * @returns {Block}
   */
  getLast = () => {
    if (this.length > 0) {
      for (let i = this.length - 1; i >= 0; i--) {
        if (this.blocks[i].rules.canDropAround) {
          return this.blocks[i];
        }
      }
    }
    return null;
  };

  /**
   * @param {number} id
   * @returns {Block|null}
   */
  getPrevByID = (id) => {
    for (let i = 0; i < this.length; i++) {
      if (this.blocks[i].id === id && this.blocks[i - 1]) {
        return this.blocks[i - 1];
      }
    }

    return null;
  };

  /**
   * @param {number} id
   * @returns {Block|null}
   */
  getNextByID = (id) => {
    for (let i = 0; i < this.length; i++) {
      if (this.blocks[i].id === id && this.blocks[i + 1]) {
        return this.blocks[i + 1];
      }
    }

    return null;
  };

  /**
   * @param {number} id
   * @param {string} field
   * @param {*} value
   * @returns {Block|null}
   */
  updateFieldByID = (id, field, value) => {
    const block = this.getByID(id);
    if (!block) {
      return null;
    }
    block[field] = value;

    return block;
  };

  /**
   * @param {Function} cb
   * @returns {null|*}
   */
  find = (cb) => {
    for (let i = 0; i < this.length; i++) {
      const result = cb(this.blocks[i]);
      if (result) {
        return result;
      }
    }

    return null;
  };

  /**
   * @param {Function} cb
   * @returns {Block[]}
   */
  filter = (cb) => {
    const found = [];
    for (let i = 0; i < this.length; i++) {
      if (cb(this.blocks[i])) {
        found.push(this.blocks[i]);
      }
    }

    return found;
  };

  /**
   * @param {Function} cb
   * @returns {any[]}
   */
  map = (cb) => {
    return this.blocks.map(cb);
  };

  /**
   * @param {Function} cb
   */
  forEach = (cb) => {
    this.blocks.forEach(cb);
  };

  /**
   * @param {number} offsetPageX
   * @param {number} offsetPageY
   * @param {number} grace
   * @returns {Block}
   */
  findIntersecting = (offsetPageX, offsetPageY, grace = 0) => {
    for (let i = 0, l = this.blocks.length; i < l; i++) {
      const { left, right, bottom, top } = this.blocks[i].rect;

      const isOverBlock = (offsetPageX > (left - grace) && offsetPageX < (right + grace))
        && (offsetPageY < (bottom + grace) && offsetPageY > (top - grace));
      if (isOverBlock) {
        return this.blocks[i];
      }
    }

    return null;
  };

  /**
   * @param {number} offsetPageX
   * @param {number} offsetPageY
   * @param {Function} filter
   * @returns {Block}
   */
  filterIntersecting = (offsetPageX, offsetPageY, filter = (i => i)) => {
    const blocks = this.filter(filter);
    let matched = null;
    let leastDistance = 1000000;
    for (let i = 0, l = blocks.length; i < l; i++) {
      const { left, right, bottom, top } = blocks[i].rect;
      const isOverBlock = (offsetPageX > left && offsetPageX < right)
        && (offsetPageY < bottom && offsetPageY > top); // -40 includes the block menu in the calculation

      if (isOverBlock) {
        return blocks[i];
        /* const distance = (offsetPageX - left) + (offsetPageY - top);
        if (distance < leastDistance) {
          leastDistance = distance;
          matched = blocks[i];
        } */
      }
    }

    return matched;
  };
}

export default BlockCollection;
