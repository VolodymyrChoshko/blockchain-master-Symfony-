import { uniqueID } from 'utils';
import browser from 'utils/browser';
import objects from 'utils/objects';
import BlockCollection from './BlockCollection';
import ContentEditable from './ContentEditable';
import Block from './Block';
import Zone from './Zone';
import Data from './Data';
import Rules from './Rules';
import HTMLUtils from './HTMLUtils';
import CodeBlocks from './CodeBlocks';
import Debugger from './Debugger';
import * as constants from './constants';

/**
 *
 */
class BlockEngine {
  /**
   *
   */
  constructor() {
    this.id                  = -1;
    this.regex               = new RegExp('-block-([^:]+):([^;]+)', 'g');
    this.updateCallback      = () => {};
    this.mediaUploadCallback = () => {};
    this.loadedImages        = {};
    this.contentEditable     = null;
    this.groups              = {};
    this.linkStyles          = [];
    this.blockGroups         = {};
    this.updatedGroupNames   = [];
    this.lastVariantElement  = null;
    this.emptyRect           = null;
    this.prevBlocks          = new BlockCollection();
  }

  /**
   * @param {HTMLIFrameElement} iframe
   * @param {boolean} refresh
   * @returns {{ blocks: BlockCollection, zones: Array, anchorStyles: Array }}
   */
  setIFrame = (iframe, refresh = true) => {
    this.iframe = iframe;
    this.style  = null;

    if (refresh) {
      return this.refreshBlocks();
    }

    return { blocks: null, zones: null, anchorStyles: null };
  };

  /**
   * @param {Function} updateCallback
   */
  setUpdateCallback = (updateCallback) => {
    this.updateCallback = updateCallback;
  };

  /**
   * @param mediaUploadCallback
   */
  setMediaUploadCallback = (mediaUploadCallback) => {
    this.mediaUploadCallback = mediaUploadCallback;
  };

  /**
   * @returns {Promise}
   */
  getHTML = () => {
    return new Promise((resolve) => {
      const html    = browser.iFrameSrc(this.iframe);
      const titleEl = browser.iFrameDocument(this.iframe).querySelector('title');

      let title = '';
      if (titleEl) {
        title = titleEl.innerText;
      }

      /** @type HTMLIFrameElement */
      const newIframe = document.createElement('IFRAME');
      newIframe.src = 'about:blank';
      newIframe.style.display = 'none';
      document.body.appendChild(newIframe);

      browser.iFrameSrc(newIframe, html);
      setTimeout(() => {
        const engine = new BlockEngine();
        engine.blockGroups = this.blockGroups;
        const { blocks } = engine.setIFrame(newIframe);
        Data.removeBlockAttributes(blocks);

        HTMLUtils.removePreferredColorScheme(browser.iFrameDocument(newIframe));

        // Ensures BE related content editable stuff gets removed.
        const doc = browser.iFrameDocument(newIframe);
        doc.body.querySelectorAll('*[contenteditable="true"]').forEach((element) => {
          element.removeAttribute('contenteditable');
        });
        doc.body.querySelectorAll('style').forEach((element) => {
          if (element.innerHTML.indexOf('[contenteditable=true]:focus') !== -1) {
            element.remove();
          }
        });

        resolve({
          html: browser.iFrameSrc(newIframe),
          title
        });
        newIframe.remove();
      }, 500);
    });
  };

  /**
   * Makes editable page elements which don't explicitly have a .block-edit
   * class.
   *
   * @param {string} html
   * @param {*} groups
   * @param {*} linkStyles
   * @returns {Promise<any>}
   */
  openHTML = (html, groups, linkStyles) => {
    if (groups) {
      this.groups = groups;
    }
    if (linkStyles) {
      this.linkStyles = linkStyles;
    }

    return new Promise((resolve) => {
      /** @type HTMLIFrameElement */
      const newIframe         = document.createElement('IFRAME');
      newIframe.src           = 'about:blank';
      newIframe.style.display = 'none';
      document.body.appendChild(newIframe);

      browser.iFrameSrc(newIframe, HTMLUtils.removeBlockHide(html));

      // Add data-be-id attributes. Needed for undo to work correctly.
      const prev = this.iframe;
      this.iframe = newIframe;
      // this.findBlocks();
      this.iframe = prev;

      setTimeout(() => {
        // Old emails may have stray .block-section-spacer elements which prevents the reordering
        // of sections.
        const doc     = browser.iFrameDocument(newIframe);
        const spacers = doc.querySelectorAll('.block-section-spacer');
        spacers.forEach((spacer) => {
          spacer.remove();
        });

        const codeEdits = doc.querySelectorAll(`.${constants.CLASS_BLOCK_CODE_EDIT}`);
        codeEdits.forEach((el) => {
          const isDroppable = el.classList.contains(constants.CLASS_BLOCK_SECTION)
            || el.classList.contains(constants.CLASS_BLOCK_COMPONENT);
          if ((el.getAttribute('style') || '').indexOf('display: none;') === -1) {
            const codeBlock = CodeBlocks.create(
              el.innerHTML.trim(),
              constants.BLOCK_SECTION,
              el.getAttribute('data-area') === 'head',
              isDroppable
            );
            el.parentNode.replaceChild(codeBlock, el);
          }
        });

        const codeScr = doc.querySelectorAll(`.${constants.CLASS_BLOCK_SCRIPT_SEC}`);
        codeScr.forEach((el) => {
          if (el.getAttribute(constants.DATA_COMPONENT_HIDDEN)) {
            el.setAttribute('style', el.getAttribute('style').replace(/display: none;/g, ''));
            el.removeAttribute(constants.DATA_COMPONENT_HIDDEN);
            CodeBlocks.collapse(el);
          }
        });

        // HTMLUtils.inlineStylesheetBEStyles(doc);
        const hasColorScheme = HTMLUtils.switchPreferredColorScheme(doc, 'light');

        doc
          .querySelectorAll('*')
          .forEach((element) => {
            if (browser.hasParentAttribute(element, 'data-be-clone')) {
              return;
            }

            const id = Data.getBlockID(element);
            if (id > this.id) {
              this.id = id;
            }
          });

        resolve({
          html: browser.iFrameSrc(newIframe),
          hasColorScheme
        });
        newIframe.remove();
      }, 250);
    });
  };

  /**
   * @returns {number}
   */
  genID = () => {
    this.id += 1;
    return this.id;
  };

  /**
   * @param {Draggable|Block} draggable
   * @param {number} dropZoneID
   * @returns {{blocks: BlockCollection, zones: Zone[]}}
   */
  addBlock = (draggable, dropZoneID) => {
    let draggableElement;
    if (draggable.element) {
      draggableElement = draggable.element.cloneNode(true);
      Data.removeBlockID(draggableElement);
    } else {
      draggableElement = HTMLUtils.createElement(draggable.html);
    }
    if (draggableElement.classList.contains(constants.CLASS_BLOCK_CODE_EDIT)) {
      draggableElement = CodeBlocks.create(draggableElement.innerText.trim(), draggable.type);
    }

    // Sections may be <tr> elements. Those have been wrapped in <table> tags which need
    // to be removed.
    if (draggableElement.tagName === 'TABLE' && draggableElement.querySelector(`.${constants.CLASS_BLOCK_SECTION}`)) {
      draggableElement = draggableElement.querySelector(`.${constants.CLASS_BLOCK_SECTION}`);
    }

    Data.set(draggableElement, constants.BLOCK_DATA_CAN_REMOVE, true);
    draggableElement.setAttribute(constants.DATA_BE_KEEP, 'true');

    if (draggable.element) {
      if (draggable.sectionId) {
        draggableElement.setAttribute('data-be-section-id', draggable.sectionId);
      } else if (draggable.componentId) {
        draggableElement.setAttribute('data-be-component-id', draggable.componentId);
      }
    } else if (draggable.type === 'section') {
      draggableElement.setAttribute('data-be-section-id', draggable.id.toString());
    } else if (draggable.type === 'component') {
      draggableElement.setAttribute('data-be-component-id', draggable.id.toString());
    }

    // Components are hidden in the emails. We tag them data-be-component-hidden so we can find
    // them on export and remove them.
    if (draggableElement.getAttribute(constants.DATA_COMPONENT_HIDDEN)) {
      draggableElement.setAttribute('style', draggableElement.getAttribute('style').replace(/display: none;/g, ''));
      draggableElement.removeAttribute(constants.DATA_COMPONENT_HIDDEN);
    }

    // eslint-disable-next-line max-len
    draggableElement.querySelectorAll(`.${constants.CLASS_BLOCK_COMPONENT}.${constants.CLASS_BLOCK_CODE_EDIT}`).forEach((el) => {
      el.remove();
    });

    // Hides variations. Ensures the first copy of the variation is visible.
    const foundGroups = [];
    const groups      = draggableElement.querySelectorAll(`*[${constants.DATA_GROUP}]`);
    for (let i = 0; i < groups.length; i++) {
      if (groups[i].getAttribute(constants.DATA_BE_KEEP)) {
        // eslint-disable-next-line no-continue
        continue;
      }
      groups[i].setAttribute(constants.DATA_BE_KEEP, 'true');
      const groupName = groups[i].getAttribute(constants.DATA_GROUP);
      if (foundGroups.indexOf(groupName) !== -1) {
        groups[i].remove();
      } else {
        foundGroups.push(groupName);
      }
    }

    // -2 means drop the block at after the last block.
    if (dropZoneID === -2 && this.blocks.length > 0) {
      const block = this.blocks.getLast();
      HTMLUtils.insertAfter(block.element, draggableElement);
    } else if (dropZoneID === -3) {
      const body = browser.iFrameDocument(this.iframe).querySelector('body');
      if (body) {
        body.prepend(draggableElement);
      }
    } else if (dropZoneID > constants.DROP_ZONE_BOTTOM_ID_MAGIC_NUM) {
      const block = this.blocks.getByID(dropZoneID - constants.DROP_ZONE_BOTTOM_ID_MAGIC_NUM);

      // Components dropped before/after images might end up inside a wrapped
      // <a> tag. Make sure the dropped components comes before/after the <a> tag.
      if (block.element.tagName === 'IMG'
        && block.element.parentElement
        && block.element.parentElement.tagName === 'A'
        && !browser.hasParentClass(block.element, constants.CLASS_BLOCK_COMPONENT)
      ) {
        HTMLUtils.insertAfter(block.element.parentElement, draggableElement);

      // Ensure components can't be dropped inside themselves.
      } else if (browser.hasParentClass(block.element, constants.CLASS_BLOCK_COMPONENT)) {
        const outer = browser.hasParentClass(block.element, constants.CLASS_BLOCK_COMPONENT, true);
        HTMLUtils.insertAfter(outer, draggableElement);
      } else {
        HTMLUtils.insertAfter(block.element, draggableElement);
      }
    } else {
      const block = this.blocks.getByID(dropZoneID);
      // Components dropped before/after images might end up inside a wrapped
      // <a> tag. Make sure the dropped components comes before/after the <a> tag.
      if (block.element.tagName === 'IMG'
        && block.element.parentElement
        && block.element.parentElement.tagName === 'A'
        && !browser.hasParentClass(block.element, constants.CLASS_BLOCK_COMPONENT)
      ) {
        HTMLUtils.insertBefore(block.element.parentElement, draggableElement);

      // Ensure components can't be dropped inside themselves.
      } else if (browser.hasParentClass(block.element, constants.CLASS_BLOCK_COMPONENT)) {
        const outer = browser.hasParentClass(block.element, constants.CLASS_BLOCK_COMPONENT, true);
        HTMLUtils.insertBefore(outer, draggableElement);
      } else {
        HTMLUtils.insertBefore(block.element, draggableElement);
      }
    }

    if (draggable.isLibrary) {
      const images = draggableElement.querySelectorAll('img');
      images.forEach((img) => {
        if (img.getAttribute('src').indexOf('/imagify/') === 0) {
          this.mediaUploadCallback(img.getAttribute('src'), img);
        } else if (img.getAttribute(constants.DATA_HOSTED)) {
          this.mediaUploadCallback(img.getAttribute('src'), img);
        }
      });
    }

    // Sections that come directly after each other with data groups. The second group
    // gets removed if data-be-keep is not present.
    draggableElement.querySelectorAll(`*[${constants.DATA_GROUP}]`).forEach((el) => {
      el.setAttribute('data-be-keep', 'true');
    });

    const doc   = browser.iFrameDocument(this.iframe);
    const found = doc.querySelector('.block-section-empty');
    if (found) {
      found.remove();
    }

    // @block-query
    if (draggableElement.parentElement) {
      const empty = draggableElement.parentElement.querySelector(`:scope > .${constants.CLASS_BLOCK_EDIT_EMPTY}`);
      if (empty) {
        empty.setAttribute('style', '');
        empty.innerText = '';
      }
    }

    if (draggable.element) {
      draggable.element.remove();
    }

    return {
      blocks: this.blocks,
      zones:  this.zones
    };
  };

  /**
   * @param {number} blockID
   * @returns {{blocks: BlockCollection, zones: Zone[]}}
   */
  removeBlock = (blockID) => {
    const block = this.blocks.getByID(blockID);

    // Add the block-section-empty when all sections have been removed.
    if (block.isSection()) {
      const sections = this.blocks.filter((b) => {
        return b.isSection();
      });

      // Empty block needs to be inserted before the block that's about to
      // be removed.
      if (sections.length === 1) {
        const rect  = block.element.getBoundingClientRect();
        const empty = HTMLUtils.createElement('<div />');
        empty.setAttribute('class', `${constants.CLASS_BLOCK_SECTION} ${constants.CLASS_BLOCK_SECTION_EMPTY}`);
        empty.setAttribute('style', `width: 100%; height: 250px; top: ${rect.top}px; left: ${rect.left}px;`);
        HTMLUtils.insertBefore(block.element, empty);
      }

      block.element.remove();
    } else if (block.isEdit() || block.isComponent()) {
      // @block-query
      let countEdits = 0;
      let countComponents = 0;
      const element = block.element;
      const parent = element.closest('.block-section');
      const groupName = element.getAttribute(constants.DATA_GROUP);
      const blockName = element.getAttribute(constants.DATA_BLOCK);
      element.remove();

      if (parent) {
        countEdits = parent.querySelectorAll(
          `.${constants.CLASS_BLOCK_EDIT}:not(.${constants.CLASS_BLOCK_EDIT_EMPTY})`
        ).length;
        countComponents = parent.querySelectorAll(
          `.${constants.CLASS_BLOCK_COMPONENT}`
        ).length;
      }

      if ((countEdits + countComponents) === 0 || countEdits === 0) {
        let empty = parent.querySelector(`.${constants.CLASS_BLOCK_EDIT}.${constants.CLASS_BLOCK_EDIT_EMPTY}`);
        if (!empty) {
          empty = HTMLUtils.createElement('<div />');
        }

        if (groupName) {
          empty.setAttribute(constants.DATA_GROUP, groupName);
        }
        if (blockName) {
          empty.setAttribute(constants.DATA_BLOCK, blockName);
        }
        empty.setAttribute('class', `${constants.CLASS_BLOCK_EDIT} ${constants.CLASS_BLOCK_EDIT_EMPTY}`);
        if (countComponents === 0) {
          empty.setAttribute(
            'style',
            'height: 50px; display: flex; align-items: center; justify-content: center;border: 1px dashed grey'
          );
          empty.innerText = 'Drag and drop component here';
        }
        parent.appendChild(empty);
      }
    } else {
      block.element.remove();
    }

    return this.findBlocks();
  };

  /**
   * @param {number} blockID
   * @returns {{blocks: BlockCollection, zones: Zone[]}}
   */
  cloneBlock = (blockID) => {
    const block = this.blocks.getByID(blockID);
    const { element } = block;

    const clonedElement = element.cloneNode(true);
    clonedElement.setAttribute(constants.DATA_BE_KEEP, 'true');
    Data.removeBlockID(clonedElement);
    Data.setBlockID(clonedElement, this.genID());
    Data.set(element, constants.BLOCK_DATA_HAS_CLONES, true);
    Data.set(clonedElement, constants.BLOCK_DATA_IS_CLONE, true);
    Data.set(clonedElement, constants.BLOCK_DATA_CAN_REMOVE, true);
    HTMLUtils.insertAfter(element, clonedElement);

    const all = clonedElement.getElementsByTagName('*');
    for (let i = 0; i < all.length; i++) {
      Data.removeBlockID(all[i]);
    }

    return {
      blocks: this.blocks,
      zones:  this.zones,
      clonedElement
    };
  };

  /**
   * @param {number} blockID
   * @param {string} direction
   * @returns {{blocks: BlockCollection, zones: Zone[]}}
   */
  moveBlock = (blockID, direction) => {
    const block = this.blocks.getByID(blockID);
    if (direction === 'up') {
      const prevBlock = this.blocks.getPrevByID(blockID);
      HTMLUtils.insertBefore(prevBlock.element, block.element);
    } else if (direction === 'down') {
      const nextBlock = this.blocks.getNextByID(blockID);
      HTMLUtils.insertAfter(nextBlock.element, block.element);
    }

    return {
      blocks: this.blocks,
      zones:  this.zones
    };
  };

  /**
   * @param {number} blockID
   * @param {string} field
   * @param {*} value
   * @returns {{blocks: BlockCollection, zones: Zone[]}}
   */
  updateBlock = (blockID, field, value) => {
    if (field === 'image') {
      const { element } = this.blocks.getByID(blockID);

      element.setAttribute('src', value.src);
      element.setAttribute('original', value.original);
    } else if (field === 'background') {
      const { element } = this.blocks.getByID(blockID);

      HTMLUtils.replaceBackgroundImage(
        element,
        value.src,
        value.original,
        this.iframe
      );
    } else {
      this.blocks.updateFieldByID(blockID, field, value);
    }

    return {
      blocks: this.blocks,
      zones:  this.zones
    };
  };

  /**
   * @param {number} blockID
   * @param {number} variationIndex
   * @returns {{blocks: BlockCollection, zones: Zone[]}}
   */
  changeBlockVariation = (blockID, variationIndex) => {
    const block = this.blocks.getByID(blockID);
    const group = this.groups[block.groupName];
    const vid   = block.data(constants.BLOCK_DATA_VARIANT_ID);

    // Creates a new DOM element using the html stored in the group items. It will be
    // the new version of the block and the old version will be removed from the doc.
    if (this.blockGroups[vid]) {
      block.element.insertAdjacentHTML('beforeBegin', this.blockGroups[vid].items[variationIndex]);
    } else {
      block.element.insertAdjacentHTML('beforeBegin', group.items[variationIndex]);
    }

    // Gets a reference to the newly created element. data-be-keep tells the editor
    // this element was added by the user and not a part of the original template.
    const newElement = block.element.previousSibling;
    newElement.setAttribute(constants.DATA_BE_KEEP, 'true');
    newElement.querySelectorAll(`*[${constants.DATA_GROUP}]`).forEach((el) => {
      el.setAttribute(constants.DATA_BE_KEEP, 'true');
    });
    this.lastVariantElement = newElement;

    // Shows the components found inside the new element which were previously hidden when the
    // template was imported.
    if (newElement.getAttribute(constants.DATA_COMPONENT_HIDDEN)) {
      newElement.setAttribute('style', newElement.getAttribute('style').replace(/display: none;/g, ''));
      newElement.removeAttribute(constants.DATA_COMPONENT_HIDDEN);
    }
    if (block.data(constants.BLOCK_DATA_CAN_REMOVE)) {
      Data.set(newElement, constants.BLOCK_DATA_CAN_REMOVE, true);
    }

    // Did we keep track of a sub-variant selection?
    let subVariantIndex = block.data(constants.BLOCK_DATA_SUB_VARIANT_INDEX);
    if (subVariantIndex) {
      Data.set(newElement, constants.BLOCK_DATA_SUB_VARIANT_INDEX, subVariantIndex);
    }
    Data.set(newElement, constants.BLOCK_DATA_VARIANT_ID, vid);

    // Removes the old DOM element from the document and assigns the new DOM element
    // to the block.
    block.element.parentElement.removeChild(block.element);
    block.element = newElement;
    block.element.setAttribute(constants.DATA_VARIATION_INDEX, variationIndex.toString());

    // Retrieve the sub-variant selection.
    subVariantIndex   = Data.get(newElement, constants.BLOCK_DATA_SUB_VARIANT_INDEX, 0);
    const parentGroup = browser.getParentDataGroup(newElement);
    if (parentGroup) {
      Data.set(parentGroup, constants.BLOCK_DATA_SUB_VARIANT_INDEX, variationIndex);
    }

    const subGroups = newElement.querySelectorAll('*[data-group]');
    for (let i = 0; i < subGroups.length; i++) {
      if (i !== subVariantIndex) {
        subGroups[i].remove();
      } else {
        subGroups[i].setAttribute(constants.DATA_VARIATION_INDEX, subVariantIndex);
      }
    }

    return {
      blocks: this.blocks,
      zones:  this.zones
    };
  };

  /**
   * @param {number} blockID
   * @param element
   */
  restoreStoredContent = (blockID, element) => {
    const block = this.blocks.getByID(blockID);
    const group = this.groups[block.groupName];
    const vid   = block.data(constants.BLOCK_DATA_VARIANT_ID);

    // Copy attributes from the updated block to variations of the block.
    const db = element.getAttribute(constants.DATA_BLOCK);
    if (db && vid) {
      if (!this.blockGroups[vid]) {
        this.blockGroups[vid] = objects.clone(group);
      }

      if (this.blockGroups[vid]) {
        this.blockGroups[vid].items.forEach((item, i) => {
          const $item = $(item);
          const $b    = $item.find(`[${constants.DATA_BLOCK}="${db}"]`);
          if ($b.length > 0) {
            if ($b.prop('tagName') === 'IMG') {
              // $b[0].outerHTML = element.outerHTML;
              $b[0].src = element.src;
              // $b[0].setAttribute('class', element.getAttribute('class'));
              // $b[0].setAttribute('style', element.getAttribute('style'));
            } else {
              $b[0].innerHTML = element.innerHTML;
              if ($b.prop('tagName') === 'A') {
                $b.attr('href', element.getAttribute('href'));
              }
            }
          }
          this.blockGroups[vid].items[i] = $item[0].outerHTML;
        });
      }
    }
  };

  /**
   * @param {number} editingID
   * @param {function} cloneCallback
   * @returns {Block}
   */
  startContentEditing = (editingID, cloneCallback) => {
    const block = this.blocks.getByID(editingID);
    this.contentEditable = ContentEditable.createInstance(this.iframe, block);
    this.contentEditable.startEditing((shouldCreate) => {
      if (shouldCreate) {
        this.finishContentEditing(editingID);
        const { clonedElement } = this.cloneBlock(editingID);
        clonedElement.innerHTML = '';
        cloneCallback(clonedElement);
      } else {
        const removeBlock = this.blocks.getByID(editingID);
        if (removeBlock.element.previousSibling && removeBlock.element.previousSibling.tagName === 'LI') {
          this.finishContentEditing(editingID);
          cloneCallback(removeBlock.element.previousSibling);
          this.removeBlock(editingID);
        }
      }
    });

    return block;
  };

  /**
   * @param {number} editingID
   * @returns {Block}
   */
  finishContentEditing = (editingID) => {
    const block = this.blocks.getByID(editingID);
    if (this.contentEditable) {
      this.contentEditable.finishEditing();
      this.contentEditable = null;
    }

    return block;
  };

  /**
   *
   */
  rollbackContentEditing = () => {
    if (this.contentEditable) {
      this.contentEditable.rollbackEditing();
    }
  };

  /**
   * @returns {{ blocks: BlockCollection, zones: Array, anchorStyles: Array }}
   */
  refreshBlocks = () => {
    const { blocks, zones, anchorStyles } = this.findBlocks();

    return { blocks, zones, anchorStyles };
  };

  /**
   * @returns {{ blocks: BlockCollection, zones: Zone[] }}
   */
  findBlocks = () => {
    this.prevBlocks         = this.blocks;
    this.blocks             = new BlockCollection();
    this.zones              = [];
    const blockEdits        = [];
    const blockBackgrounds  = [];
    const blockBGColors     = [];
    const blockSections     = [];
    const blockRegions      = [];
    const blockComponents   = [];
    const anchorStyles      = [];

    // Finds all the elements with block-* classes and -block* inline styles.
    // We're not querying directly for .block-edit and .block-section classes
    // because we have to traverse the entire DOM to find the -block* style
    // attributes. Since we're already traversing the DOM we might as well
    // skip the query selecting.
    browser.iFrameDocument(this.iframe)
      .querySelectorAll('*')
      .forEach((element) => {
        if (browser.hasParentAttribute(element, 'data-be-clone')) {
          return;
        }
        let found = false;
        const { classList } = element;

        if (classList.contains(constants.CLASS_BLOCK_EDIT)) {
          blockEdits.push(element);
          found = true;
        } else if (classList.contains(constants.CLASS_BLOCK_SECTION)) {
          blockSections.push(element);
          found = true;
        } else if (classList.contains(constants.CLASS_BLOCK_REGION)) {
          blockRegions.push(element);
          found = true;
        } else if (classList.contains(constants.CLASS_BLOCK_COMPONENT)) {
          blockComponents.push(element);
          found = true;
        } else if (classList.contains(constants.CLASS_BLOCK_BACKGROUND)) {
          blockBackgrounds.push(element);
          found = true;
        } else if (classList.contains(constants.CLASS_BLOCK_BG_COLOR)) {
          blockBGColors.push(element);
          found = true;
        }

        // i.e. style="-block-edit: true"
        element.beInline = this.findBlockStyles(element);
        if (!found) {
          objects.forEach(element.beInline, (value, key) => {
            if (key === constants.BLOCK_BACKGROUND) {
              blockBackgrounds.push(element);
            } else if (key === constants.BLOCK_EDIT) {
              blockEdits.push(element);
            } else if (key === constants.BLOCK_SECTION) {
              this.sectionTotal += 1;
              blockSections.push(element);
            } else if (key === constants.BLOCK_REGION) {
              element.classList.add(constants.CLASS_BLOCK_REGION);
              blockRegions.push(element);
            } else if (key === constants.BLOCK_COMPONENT) {
              blockComponents.push(element);
            }
          });
        }
      });

    // The order the blocks are ID'd the order they appear on the
    // canvas. Important to know because blocks can stack on top of each
    // other, for instance background should get rendered on the canvas
    // before other blocks.
    blockEdits.forEach((element) => {
      if (!Data.getBlockID(element)) {
        Data.setBlockID(element, this.genID());
      }
    });
    blockBGColors.forEach((element) => {
      if (!Data.getBlockID(element)) {
        Data.setBlockID(element, this.genID());
      }
    });
    blockBackgrounds.forEach((element) => {
      if (!Data.getBlockID(element)) {
        Data.setBlockID(element, this.genID());
      }
    });
    blockComponents.forEach((element) => {
      if (!Data.getBlockID(element)) {
        Data.setBlockID(element, this.genID());
      }
    });
    blockRegions.forEach((element) => {
      if (!Data.getBlockID(element)) {
        Data.setBlockID(element, this.genID());
      }
    });
    blockSections.forEach((element) => {
      if (!Data.getBlockID(element)) {
        Data.setBlockID(element, this.genID());
      }
    });

    blockEdits.forEach(this.createBlockEdit);
    blockBGColors.forEach(this.createBlockBGColor);
    blockBackgrounds.forEach(this.createBlockBackground);
    blockComponents.forEach(this.createBlockComponent);
    blockRegions.forEach(this.createBlockRegion);
    blockSections.forEach(this.createBlockSection);

    blockSections.forEach((element) => {
      const groupName = element.getAttribute(constants.DATA_GROUP);
      if (groupName) {
        // Why are we removing the parent ID?
        element.parentNode.removeAttribute(constants.DATA_BE_ID);
        const vid = Data.get(element, constants.BLOCK_DATA_VARIANT_ID);
        if (!vid) {
          Data.set(element, constants.BLOCK_DATA_VARIANT_ID, uniqueID());
        }
      }
    });

    blockRegions.forEach((element) => {
      const groupName = element.getAttribute(constants.DATA_GROUP);
      if (groupName) {
        // Why are we removing the parent ID?
        // element.parentNode.removeAttribute(constants.DATA_BE_ID);
        const vid = Data.get(element, constants.BLOCK_DATA_VARIANT_ID);
        if (!vid) {
          Data.set(element, constants.BLOCK_DATA_VARIANT_ID, uniqueID());
        }
      }
    });

    blockComponents.forEach((element) => {
      const groupName = element.getAttribute(constants.DATA_GROUP);
      if (groupName) {
        // Why are we removing the parent ID?
        element.parentNode.removeAttribute(constants.DATA_BE_ID);
        const vid = Data.get(element, constants.BLOCK_DATA_VARIANT_ID);
        if (!vid) {
          Data.set(element, constants.BLOCK_DATA_VARIANT_ID, uniqueID());
        }
      }
    });

    this.addZoneToCollection(-3, null);
    this.blocks.forEach((/** @type Block */ b) => {
      b.anchorStyles = this.linkStyles;

      if (b.isRegion()) {
        // Copy values (images and text) from active variation to alt variations.
        const groupName = b.element.getAttribute(constants.DATA_GROUP);
        if (groupName) {
          this.updatedGroupNames.push(groupName);
          b.children.forEach((id) => {
            const bb = this.blocks.getByID(id);
            this.restoreStoredContent(b.id, bb.element);
          });
        }
      }

      if (b.isSection()) {
        // Copy values (images and text) from active variation to alt variations.
        const groupName = b.element.getAttribute(constants.DATA_GROUP);
        if (groupName) {
          this.updatedGroupNames.push(groupName);
          b.children.forEach((id) => {
            const bb = this.blocks.getByID(id);
            this.restoreStoredContent(b.id, bb.element);
          });
        }

        if (b.rules.canDropAround) {
          this.addZoneToCollection(b.id, b);
        }
      } else if ((b.isEdit() || b.isComponent())) {
        // Copy values (images and text) from active variation to alt variations.
        const groupName = b.element.getAttribute(constants.DATA_GROUP);
        if (groupName) {
          this.updatedGroupNames.push(groupName);
          b.children.forEach((id) => {
            const bb = this.blocks.getByID(id);
            this.restoreStoredContent(b.id, bb.element);
          });
        }

        if (b.rules.canDropAround
          && b.element.parentElement
          && !browser.hasParentClass(b.element.parentElement, constants.CLASS_BLOCK_COMPONENT)
        ) {
          this.addZoneToCollection(b.id, b);
        }
      }
    });

    // Add a drop zone that comes at the bottom of the page.
    if (this.blocks.length > 0) {
      const lastBlock = this.blocks.getLast();
      if (!lastBlock.empty) {
        this.addZoneToCollection(-2, lastBlock);
      }
    }

    return {
      blocks: this.blocks,
      zones:  this.zones,
      anchorStyles
    };
  };

  /**
   * @returns {{ blocks: BlockCollection, zones: Zone[] }}
   */
  refreshRects = () => {
    this.blocks.forEach((b) => {
      b.rect = b.element.getBoundingClientRect();
    });

    return {
      blocks: this.blocks,
      zones:  this.zones
    };
  };

  /**
   * @param {Block} block
   */
  addBlockToCollection = (block) => {
    let groupName = block.element.getAttribute(constants.DATA_GROUP);
    if (!groupName) {
      groupName = block.element.getAttribute(constants.DATA_STYLE);
    }
    if (groupName) {
      block.groupName = groupName;
    }

    this.blocks.add(block);
  };

  /**
   * @param {number} id
   * @param {Block} block
   */
  addZoneToCollection = (id, block) => {
    if (id === -2) {
      const { rect } = block;

      this.zones.push(new Zone(
        -2,
        constants.BLOCK_SECTION,
        {
          top:    rect.bottom,
          left:   rect.left,
          right:  rect.right,
          bottom: rect.bottom + 5,
          height: 5,
          width:  rect.width
        }
      ));
    } else if (id === -3) {
      this.zones.push(new Zone(
        -3,
        constants.BLOCK_SECTION,
        {
          top:    1,
          left:   0,
          bottom: 6,
          height: 5,
          right:  this.iframe.getBoundingClientRect().width,
          width:  this.iframe.getBoundingClientRect().width
        },
        {
          isCode: true
        }
      ));
    } else {
      const { rect } = block;

      if (block.isSection()) {
        this.zones.push(new Zone(
          id,
          constants.BLOCK_SECTION,
          {
            top:    rect.top,
            left:   rect.left,
            right:  rect.right,
            bottom: rect.top + 5,
            height: 5,
            width:  rect.width
          },
          {
            empty: block.empty
          }
        ));
      } else {
        this.zones.push(new Zone(
          id,
          constants.BLOCK_COMPONENT,
          {
            top:    rect.top,
            left:   rect.left,
            right:  rect.right,
            bottom: rect.top + 50,
            height: 50,
            width:  rect.width
          }
        ));

        this.zones.push(new Zone(
          id + constants.DROP_ZONE_BOTTOM_ID_MAGIC_NUM,
          constants.BLOCK_COMPONENT,
          {
            top:    rect.top + rect.height,
            left:   rect.left,
            right:  rect.right,
            bottom: rect.top + rect.height + 50,
            height: 50,
            width:  rect.width
          }
        ));
      }
    }
  };

  /**
   * Group of elements and Components that can be customized.
   * Regions cannot contain nested Regions
   * A dashed red outline is shown when mousing over a Region along with its options
   *
   * @param {Element} element
   */
  createBlockRegion = (element) => {
    let parentSectionID = -1;
    const section = element.closest(`.${constants.CLASS_BLOCK_SECTION}`);
    if (section && section !== element) {
      parentSectionID = Data.getBlockID(section);
    }

    const tag   = element.tagName.toLowerCase();
    const rules = new Rules(element);

    const children = [];
    element.querySelectorAll(`.${constants.CLASS_BLOCK_EDIT}`).forEach((child) => {
      children.push(Data.getBlockID(child));
    });

    this.wireupElementImages(element);
    const block = this.createBlock(Data.getBlockID(element), constants.BLOCK_REGION, element, {
      tag,
      rules,
      element,
      children,
      parentSectionID,
    });
    delete element.beInline;
    this.addBlockToCollection(block);
  };

  /**
   * Background images:
   * Works for CSS background/background-image property as well as background attribute.
   * Takes into account Outlook workaround and accordingly matches the image URL.
   * If there is an additional background property that is outside the container that has the 'block-background'.
   * tag, you will need to also use the 'data-block' attribute with a variable name on both the main container
   * and the container with outside property.
   *
   * @param {Element} element
   */
  createBlockBackground = (element) => {
    let parent = -1;
    const section = element.closest('.block-section');
    if (section && section !== element) {
      parent = Data.getBlockID(section);
    }

    const tag        = element.tagName.toLowerCase();
    const rules      = new Rules(element);
    rules.isLinkable = element.parentNode.tagName === 'A';

    this.wireupElementImages(element);
    const block = this.createBlock(Data.getBlockID(element), constants.BLOCK_BACKGROUND, element, {
      tag,
      rules,
      element,
      parent,
    });
    delete element.beInline;
    this.addBlockToCollection(block);
  };

  /**
   * Background color
   *
   * @param element
   */
  createBlockBGColor = (element) => {
    if (!element.classList.contains(constants.CLASS_BLOCK_BACKGROUND)) {
      const tag   = element.tagName.toLowerCase();
      const rules = new Rules(element);

      this.wireupElementImages(element);
      const block = this.createBlock(Data.getBlockID(element), constants.BLOCK_BG_COLOR, element, {
        tag,
        rules,
        element,
      });
      delete element.beInline;
      this.addBlockToCollection(block);
    }
  };

  /**
   * Use on 'img' elements to allow for uploading a new image.
   * Cannot have nested elements that are also editable.
   *
   * Images:
   * When an image in a template is uploaded in place of a current one, an option to crop the image is given
   * with the same proportions as the original source image.
   * If there is a 'height: auto' CSS style for the image, there is no cropping option; the image is scaled to
   * the width of the source image with a proportional height of the new image.
   *
   * @param {Element} element
   */
  createBlockEdit = (element) => {
    let parentSectionID   = -1;
    let parentRegionID    = -1;
    let parentComponentID = -1;
    let section = element.closest(`.${constants.CLASS_BLOCK_SECTION}`);
    if (section && section !== element) {
      parentSectionID = Data.getBlockID(section);
    }
    section = element.closest(`.${constants.CLASS_BLOCK_COMPONENT}`);
    if (section && section !== element) {
      parentComponentID = Data.getBlockID(section);
    }
    section = element.closest(`.${constants.CLASS_BLOCK_REGION}`);
    if (section && section !== element) {
      parentRegionID = Data.getBlockID(section);
    }

    const tag        = element.tagName.toLowerCase();
    const rules      = new Rules(element);
    const isCode     = element.classList.contains(constants.CLASS_BLOCK_SCRIPT);
    rules.isLinkable = tag === 'img' && element.parentNode.tagName === 'A';
    rules.isEditable = tag !== 'img';

    this.wireupElementImages(element);
    const block = this.createBlock(Data.getBlockID(element), constants.BLOCK_EDIT, element, {
      tag,
      rules,
      element,
      isCode,
      parentComponentID,
      parentSectionID,
      parentRegionID
    });
    delete element.beInline;
    this.addBlockToCollection(block);
  };

  /**
   * Stand-alone piece that can be added throughout a template.
   * A Component can always be removed
   * A dashed red outline is shown when mousing over a Component along with its options
   *
   * @param {Element} element
   */
  createBlockComponent = (element) => {
    const tag       = element.tagName.toLowerCase();
    const rules     = new Rules(element);
    rules.canRemove = true;

    const children = [];
    element.querySelectorAll(`.${constants.CLASS_BLOCK_EDIT}`).forEach((child) => {
      children.push(Data.getBlockID(child));
    });

    const isCode = element.classList.contains(constants.CLASS_BLOCK_SCRIPT_SEC);

    this.wireupElementImages(element);
    const block = this.createBlock(Data.getBlockID(element), constants.BLOCK_COMPONENT, element, {
      tag,
      rules,
      isCode,
      element,
      children,
      componentId: element.getAttribute('data-be-component-id'),
    });
    delete element.beInline;
    this.addBlockToCollection(block);
  };

  /**
   * Areas of content stacked on top of each other that can be added in as part of a template's layout.
   * Sections can be added in multiple times, reordered, and removed as needed.
   *
   * @param {Element} element
   */
  createBlockSection = (element) => {
    const children = [];
    element.querySelectorAll(`.${constants.CLASS_BLOCK_EDIT}`).forEach((child) => {
      children.push(Data.getBlockID(child));
    });
    element.querySelectorAll(`.${constants.CLASS_BLOCK_COMPONENT}`).forEach((child) => {
      children.push(Data.getBlockID(child));
    });

    const id     = Data.getBlockID(element);
    const tag    = element.tagName.toLowerCase();
    const rules  = new Rules(element);
    const isCode = element.classList.contains(constants.CLASS_BLOCK_SCRIPT_SEC);

    this.wireupElementImages(element);
    const block = this.createBlock(id, constants.BLOCK_SECTION, element, {
      tag,
      rules,
      isCode,
      element,
      children,
      sectionId: element.getAttribute('data-be-section-id'),
    });

    delete element.beInline;
    this.addBlockToCollection(block);
  };

  /**
   * @param {number} id
   * @param {Node|HTMLElement} element
   * @param {string} type
   * @param {*} data
   * @returns {Block}
   */
  createBlock = (id, type, element, data) => {
    const block = new Block(id, type, element, element.beInline, data);
    if (element.classList.contains(constants.CLASS_BLOCK_BACKGROUND)) {
      block.setBackground(true);
    }

    return block;
  };

  /**
   * Crawls all elements in $content to find inline styles like -block-edit and adds the corresponding class
   * name to the elements
   *
   * @param {HTMLElement} element
   * @returns {*}
   */
  findBlockStyles = (element) => {
    const inline  = {};
    const style   = HTMLUtils.getAttribute(element, 'style');
    const matches = this.findMatches(this.regex, style);

    if (matches.length) {
      for (let i = 0; i < matches.length; i++) {
        const match = matches[i];
        const rule  = constants.inlineStyles[match[1]];
        if (!rule) {
          Debugger.warning(`[Syntax Error] Invalid inline style ${match[0]}`);
        } else {
          const value = this.strToReal(match[2]);
          const id    = value ? rule[0] : rule[1];
          if (id !== '') {
            inline[id] = value;
          }
        }
      }
    }

    return inline;
  };

  /**
   * @param {RegExp} regex
   * @param {string} str
   * @param {Array} matches
   * @returns {*[]}
   */
  findMatches = (regex, str, matches = []) => {
    const res = regex.exec(str);
    // eslint-disable-next-line no-unused-expressions
    res && matches.push(res) && this.findMatches(regex, str, matches);

    return matches;
  };

  /**
   * @param {Element|HTMLElement} element
   */
  wireupElementImages = (element) => {
    if (element.tagName === 'IMG') {
      const src = this.setImageSource(element);
      if (!this.loadedImages[src]) {
        element.addEventListener('load', this.updateCallback);
        this.loadedImages[src] = true;
      }
    } else if (element.style.background) {
      const src = browser.cssExtractURL(element.style.background);
      if (src && !this.loadedImages[src]) {
        const img = new Image();
        img.addEventListener('load', this.updateCallback);
        img.setAttribute('src', src);
        this.loadedImages[src] = true;
      }
    } else if (element.style.backgroundImage) {
      const src = browser.cssExtractURL(element.style.backgroundImage);
      if (src && !this.loadedImages[src]) {
        const img = new Image();
        img.addEventListener('load', this.updateCallback);
        img.setAttribute('src', src);
        this.loadedImages[src] = true;
      }
    } else {
      element.querySelectorAll('img').forEach((img) => {
        const src = this.setImageSource(img);
        if (!this.loadedImages[src]) {
          img.addEventListener('load', this.updateCallback);
          this.loadedImages[src] = true;
        }
      });
    }
  };

  /**
   * @param {Element|HTMLElement} element
   * @returns {string}
   */
  setImageSource = (element) => {
    const source = element.getAttribute('src') || element.getAttribute('data-src');
    if (source && element.getAttribute('data-src')) {
      element.setAttribute('src', element.getAttribute('data-src'));
      element.removeAttribute('data-src');
    }

    return source;
  };

  /**
   * @param {string} attrib
   * @returns {boolean|number}
   */
  strToReal = (attrib) => {
    attrib = attrib.trim().toLowerCase();
    if (attrib === 'true') {
      return true;
    }
    if (attrib === 'false') {
      return false;
    }
    return parseInt(attrib, 10);
  };
}

export default new BlockEngine();
