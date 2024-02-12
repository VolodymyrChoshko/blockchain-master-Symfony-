import { builder } from 'lib/styles';
import * as constants from './constants';
import HTMLUtils from './HTMLUtils';

/**
 * Creates and manipulates block-script blocks.
 */
class CodeBlocks {
  /**
   * @param {string} code
   * @param {string} type
   * @param {boolean} isHead
   * @param {boolean} isDroppable
   * @returns {Element}
   */
  create = (code, type = constants.BLOCK_SECTION, isHead = false, isDroppable = true) => {
    const className = (type === constants.BLOCK_SECTION)
      ? constants.CLASS_BLOCK_SECTION
      : constants.CLASS_BLOCK_COMPONENT;

    const el = HTMLUtils.createElement(`
        <div
          class="${className} ${constants.CLASS_BLOCK_SCRIPT_SEC}"
          style="font-family:monospace; font-size:14px; color:#000; width:100%; text-align: left;"
          data-be-droppable="${isDroppable ? 1 : 0}"
          data-area="${isHead ? 'head' : ''}"
        >
            <div
            class="${constants.CLASS_BLOCK_EDIT} ${constants.CLASS_BLOCK_SCRIPT}"
            style="padding:0; overflow:hidden;;cursor:pointer;"
            >${code.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
        </div>
      `);
    this.collapse(el);

    return el;
  };

  /**
   * @param {HTMLElement|Element} element
   */
  collapse = (element) => {
    const div = element.querySelector('div');
    element.style.backgroundColor = builder.colorSection;
    div.style.color               = builder.colorSection;
    div.style.height              = '10px';
    div.style.overflow            = 'hidden';
    div.style.whiteSpace          = 'normal';
    div.style.cursor              = 'pointer';
    div.style.padding             = '2px';
  };

  /**
   * @param {HTMLElement} element
   */
  expand = (element) => {
    const div = element.querySelector('div');
    element.style.backgroundColor = '#FFF';
    div.style.cursor              = 'default';
    div.style.color               = '#999';
    div.style.height              = 'auto';
    div.style.overflow            = 'visible';
    div.style.whiteSpace          = 'pre-wrap';
    div.style.padding             = '10px';
  };
}

export default new CodeBlocks();
