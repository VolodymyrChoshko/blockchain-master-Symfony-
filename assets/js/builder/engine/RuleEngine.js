import {
  CLASS_BLOCK_EDIT,
  BLOCK_DATA_ELEMENT_ID,
  BLOCK_DATA_ELEMENT_IGNORE,
  CLASS_BLOCK_SCRIPT,
  CLASS_BLOCK_SCRIPT_SEC,
  CLASS_BLOCK_SECTION,
  CLASS_BLOCK_REGION,
  CLASS_BLOCK_COMPONENT,
  BLOCK_DATA_IMG_ID,
  CLASS_BLOCK_CODE_EDIT,
} from 'builder/engine/constants';
import Data from 'builder/engine/Data';
import browser from 'utils/browser';

let currentId = 1;
const ignoreClasses = [CLASS_BLOCK_SCRIPT_SEC, CLASS_BLOCK_SCRIPT, CLASS_BLOCK_CODE_EDIT];
const ignoredTags = ['B', 'STRONG', 'EM', 'I', 'U', 'BR', 'HR'];
const textTags = ['A', 'BR', 'SPAN', 'B', 'STRONG', 'SUP'];
const sectionTags = ['DIV', 'TABLE', 'TR'];
const componentTags = ['DIV', 'TABLE', 'H1', 'H2', 'H3', 'H4', 'H5', 'P', 'IMG', 'A'];
const regionTags = ['TABLE', 'DIV', 'TR', 'TD'];

/**
 * @param element
 * @returns {boolean}
 */
const hasIgnoredClass = (element) => {
  if (element && element.classList) {
    return ignoreClasses.some((className) => element.classList.contains(className));
  }

  return false;
};

/**
 * @param element
 * @param classes
 * @returns {boolean}
 */
const hasClasses = (element, classes) => {
  if (element && element.classList) {
    for (let i = 0; i < classes.length; i++) {
      if (element.classList.contains(classes[i])) {
        return true;
      }
    }
  }

  return false;
};

class RuleEngine {
  /**
   *
   */
  constructor() {
    this.zones = {};
    this.mode = 'editable';
  }

  /**
   * @param iframe
   */
  setIframe = (iframe) => {
    this.iframe = iframe;
  };

  /**
   * @param mode
   */
  setMode = (mode) => {
    this.mode = mode;
  }

  /**
   *
   */
  findBlocks = () => {
    let imgId = 1;
    currentId = 1;
    this.zones = [];
    this.restoreElements();

    let matcher;
    if (this.mode === 'editable') {
      matcher = this.isEditable;
    } else if (this.mode === 'sections') {
      matcher = this.isSectionable;
    } else if (this.mode === 'components') {
      matcher = this.isComponentable;
    } else if (this.mode === 'regions') {
      matcher = this.isRegionable;
    } else {
      throw new Error(`Invalid rules mode ${this.mode}.`);
    }

    browser.iFrameDocument(this.iframe)
      .querySelectorAll('*')
      .forEach((element) => {
        if (
          ignoredTags.indexOf(element.tagName) === -1
          && !Data.get(element, BLOCK_DATA_ELEMENT_ID)
          && !Data.get(element, BLOCK_DATA_ELEMENT_IGNORE)
          && !hasIgnoredClass(element)
        ) {
          const match = matcher(element);
          if (match) {
            const id = currentId++;
            Data.set(match, BLOCK_DATA_ELEMENT_ID, id);
            if (match.tagName === 'IMG') {
              Data.set(match, BLOCK_DATA_IMG_ID, imgId++);
            }

            match.setAttribute('data-be-orig-cursor', match.style.cursor);
            match.removeAttribute('contenteditable');
            match.style.cursor = 'pointer';

            const rect = match.getBoundingClientRect();
            this.zones[id] = {
              top:         rect.top - 1,
              left:        rect.left,
              width:       rect.width + 1,
              height:      rect.height + 1,
              isEditable:  match.classList.contains(CLASS_BLOCK_EDIT),
              isSection:   match.classList.contains(CLASS_BLOCK_SECTION),
              isComponent: match.classList.contains(CLASS_BLOCK_COMPONENT),
              isRegion:    match.classList.contains(CLASS_BLOCK_REGION),
            };
          }
        }
      });

    return this.zones;
  };

  /**
   * @param {HTMLElement} element
   * @returns {HTMLElement}
   */
  isEditable = (element) => {
    if (hasClasses(element, [CLASS_BLOCK_EDIT])) {
      return element;
    }

    const { childNodes } = element;

    if (element.tagName === 'TD' && childNodes.length === 0) {
      Data.set(element, BLOCK_DATA_ELEMENT_IGNORE, '1');
      return null;
    }

    if (childNodes.length === 1) {
      if (childNodes[0].nodeName === 'A') {
        let el;
        if (childNodes[0].childNodes.length === 1 && childNodes[0].childNodes[0].nodeName === 'IMG') {
          el = childNodes[0].childNodes[0];
          Data.set(childNodes[0], BLOCK_DATA_ELEMENT_IGNORE, '1');
        } else {
          el = childNodes[0];
        }
        return el;
      }

      if (textTags.indexOf(childNodes[0].nodeName) !== -1) {
        return element;
      }
      if (childNodes[0].nodeType === Node.COMMENT_NODE || childNodes[0].nodeType === Node.ELEMENT_NODE) {
        return null;
      }
      if (childNodes[0].nodeType === Node.TEXT_NODE && childNodes[0].nodeValue.length === 1) {
        return null;
      }

      return element;
    }

    for (let i = 0; i < childNodes.length; i++) {
      const node = childNodes[i];
      if (node.nodeType === Node.ELEMENT_NODE && node.nodeName === 'TD') {
        if (node.childNodes.length === 0) {
          Data.set(node, BLOCK_DATA_ELEMENT_IGNORE, '111');
          return null;
        }

        if (node.childNodes.length === 1) {
          if (node.childNodes[0].nodeType === Node.TEXT_NODE) {
            if (node.childNodes[0].nodeValue === ' ' || node.childNodes[0].nodeValue === '\u00a0') {
              Data.set(node, BLOCK_DATA_ELEMENT_IGNORE, '222');
              return null;
            }
          }
        }

        for (let j = 0; j < node.childNodes.length; j++) {
          if (node.childNodes[j].nodeName === 'A') {
            Data.set(node.childNodes[j], BLOCK_DATA_ELEMENT_IGNORE, '222');
          }
        }
      } else {
        if (node.nodeType === Node.ELEMENT_NODE && textTags.indexOf(node.nodeName) === -1) {
          return null;
        }
        if (node.nodeType !== Node.TEXT_NODE && textTags.indexOf(node.nodeName) === -1) {
          return null;
        }
        if (node.nodeType === Node.ELEMENT_NODE && (node.nodeName === 'A' || node.nodeName === 'IMG')) {
          Data.set(node, BLOCK_DATA_ELEMENT_IGNORE, '444');
        }
      }
    }

    if (element.tagName === 'TR') {
      return null;
    }

    return element;
  };

  /**
   * @param {HTMLElement} element
   * @returns {HTMLElement}
   */
  isSectionable = (element) => {
    if (hasClasses(element, [CLASS_BLOCK_SECTION])) {
      return element;
    }
    if (sectionTags.indexOf(element.tagName) !== -1
      && !hasClasses(element, [CLASS_BLOCK_REGION, CLASS_BLOCK_COMPONENT])) {
      return element;
    }

    return null;
  };

  /**
   * @param {HTMLElement} element
   * @returns {HTMLElement}
   */
  isComponentable = (element) => {
    if (hasClasses(element, [CLASS_BLOCK_COMPONENT])) {
      return element;
    }
    if (componentTags.indexOf(element.tagName) !== -1
      && !hasClasses(element, [CLASS_BLOCK_REGION, CLASS_BLOCK_SECTION])) {
      return element;
    }

    return null;
  }

  /**
   * @param {HTMLElement} element
   * @returns {HTMLElement}
   */
  isRegionable = (element) => {
    if (hasClasses(element, [CLASS_BLOCK_REGION])) {
      return element;
    }

    const isEl = regionTags.indexOf(element.tagName) !== -1
      && !hasClasses(element, [CLASS_BLOCK_COMPONENT, CLASS_BLOCK_SECTION]);

    return isEl ? element : null;
  };

  /**
   *
   */
  restoreElements = () => {
    browser.iFrameDocument(this.iframe)
      .querySelectorAll('*')
      .forEach((element) => {
        Data.removeBlockData(element);
        const cursor = element.getAttribute('data-be-orig-cursor');
        if (cursor !== null) {
          element.style.cursor = cursor;
          element.removeAttribute('data-be-orig-cursor');
        }
        if (element.hasAttribute('style') && !element.getAttribute('style')) {
          element.removeAttribute('style');
        }
      });
  };
}

export default new RuleEngine();
