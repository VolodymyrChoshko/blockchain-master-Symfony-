/**
 * Randomizes the given array
 *
 * @param {Array} array
 * @returns {Array}
 */
export function shuffle(array) {
  const newArray = array.slice(0);
  for (let i = newArray.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
  }

  return newArray;
}

/**
 * @param {Array} array
 * @returns {*}
 */
export function randomItem(array) {
  return array[Math.floor(Math.random() * array.length)];
}

/**
 * @param {Array} items
 * @param {number} id
 * @returns {null|*}
 */
const findByID = (items, id) => {
  for (let i = 0, l = items.length; i < l; i++) {
    if (items[i].id === id) {
      return items[i];
    }
  }

  return null;
};

/**
 * @param {Array} items
 * @param {number|string} id
 * @param {string} idProp
 * @returns {number}
 */
export const findIndexByID = (items, id, idProp = 'id') => {
  for (let i = 0, l = items.length; i < l; i++) {
    if (items[i][idProp] === id) {
      return i;
    }
  }

  return -1;
};

/**
 * @param {number} start
 * @param {number} size
 * @returns {number[]}
 */
const range = (start, size) => {
  return [...Array(size - start + 1).keys()].map(i => i + start);
};

export default {
  range,
  shuffle,
  findByID,
  findIndexByID,
  randomItem
};
