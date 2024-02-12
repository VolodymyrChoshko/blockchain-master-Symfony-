import { createReducer } from 'utils';
import browser from 'utils/browser';
import objects from 'utils/objects';
import HTMLUtils from 'builder/engine/HTMLUtils';
import { DATA_BE_STYLE_ORIG, DATA_BE_STYLE_INDEX, DATA_BE_STYLE_DEFAULT } from 'builder/engine/constants';
import * as types from '../actions/editableActions';

export const anchorStyleIndexNone    = -2;
export const anchorStyleIndexDefault = -1;

const initialState = {
  editingBlock:     null,
  editingElement:   null,
  contentDocument:  null,
  contentWindow:    null,
  linkValue:        '',
  linkAlias:        '',
  anchorStyleIndex: anchorStyleIndexNone,
  activeTools:      {
    bold:        false,
    italic:      false,
    underline:   false,
    link:        false,
    superscript: false,
    subscript:   false
  }
};

/**
 * @param {Document} contentDocument
 * @param {Window} contentWindow
 * @param {*} activeTools
 * @param {Element} node
 */
const queryForActiveTools = (contentDocument, contentWindow, activeTools, node = null) => {
  const newActiveTools = objects.clone(activeTools);
  Object.keys(activeTools).forEach((key) => {
    newActiveTools[key] = contentDocument.queryCommandState(key);
  });
  if (!node) {
    node = browser.getSelectedNode(contentDocument, contentWindow);
  }
  if (browser.hasParentTag(node, 'A')) {
    newActiveTools.link = true;
  }

  return newActiveTools;
};

/**
 * @param {*} state
 * @returns {*}
 */
const onReset = (state) => {
  return {
    editingBlock:     null,
    editingElement:   null,
    linkValue:        '',
    linkAlias:        '',
    contentDocument:  state.contentDocument,
    contentWindow:    state.contentWindow,
    anchorStyleIndex: anchorStyleIndexNone,
    activeTools:      {
      bold:      false,
      italic:    false,
      underline: false,
      link:      false
    }
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onInit = (state, action) => {
  const { activeTools } = state;
  const { iframe, editingBlock } = action;
  const { contentWindow } = iframe;

  const editingElement  = editingBlock.element;
  const contentDocument = browser.iFrameDocument(iframe);

  return {
    ...state,
    contentDocument,
    contentWindow,
    editingBlock,
    editingElement,
    activeTools: queryForActiveTools(contentDocument, contentWindow, activeTools)
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onToolsQuery = (state, action) => {
  const { contentDocument, contentWindow, activeTools } = state;
  let { linkValue, linkAlias } = state;
  const { element } = action;

  if (!contentDocument) {
    return state;
  }

  let anchorStyleIndex = anchorStyleIndexNone;
  let node;
  if (element) {
    node = element;
  } else {
    node = browser.getSelectedNode(contentDocument, contentWindow);
  }

  if (node && node.tagName === 'A') {
    anchorStyleIndex = parseInt(node.getAttribute(DATA_BE_STYLE_INDEX) || anchorStyleIndexNone, 10);
    linkValue = node.getAttribute('href') || '';
    linkAlias = node.getAttribute('alias') || '';
  }

  return {
    ...state,
    linkValue,
    linkAlias,
    anchorStyleIndex,
    activeTools: queryForActiveTools(contentDocument, contentWindow, activeTools, node)
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onExec = (state, action) => {
  const { contentDocument, contentWindow, activeTools } = state;
  const { cmd, value, attributes, element } = action;
  let { anchorStyleIndex, linkValue, linkAlias } = state;
  let { style, alias, index } = attributes; // eslint-disable-line

  let range;
  let selection;
  try {
    selection = contentWindow.getSelection();
    range     = selection.getRangeAt(0);
  } catch (error) {
    return state;
  }

  let node = browser.getSelectedNode(contentDocument, contentWindow);
  if (node && node.tagName !== 'A') {
    node = null;
  }

  if (cmd === 'createLink') {
    if (range.startOffset === 0 && range.endOffset === 0) {
      // The entire block is selected. We can work directly on the block element.
      node = element;
    } else if (!node && range.startOffset !== range.endOffset) {
      // Using BE_LINK_TO_CHANGE lets us get a reference to the newly created anchor.
      contentWindow.getSelection().addRange(range);
      contentDocument.execCommand('createLink', false, 'BE_LINK_TO_CHANGE');
      node = contentDocument.querySelector('a[href="BE_LINK_TO_CHANGE"]');
      if (selection.toString() === 'BE_LINK_TO_CHANGE') {
        node.innerText = value;
        index = anchorStyleIndex;
      }
    } else if (!node && value) {
      // No text is selected. We create a new anchor with the url value.
      node = contentDocument.createElement('A');
      node.innerText = value;
      index = anchorStyleIndexDefault;
      range.insertNode(node);
    }

    if (node) {
      node.setAttribute('href', value || '');
      node.setAttribute('alias', alias || '');
      node.setAttribute('data-be-anchor', 'true');
      linkValue = value || '';
      linkAlias = alias || '';

      if (index === anchorStyleIndex || index === anchorStyleIndexNone) {
        index = anchorStyleIndexDefault;
      }
      if (index !== undefined) {
        node.setAttribute(DATA_BE_STYLE_INDEX, index);
      }
      if (node.getAttribute(DATA_BE_STYLE_ORIG) === null) {
        // node.setAttribute(DATA_BE_STYLE_ORIG, node.getAttribute('style') || '');
      }

      if (index === anchorStyleIndexDefault) {
        const styles = HTMLUtils.getStyleObject(node.getAttribute('style') || '');
        const elStyles = HTMLUtils.getStyleObject(element.getAttribute('style') || '');

        let color = 'inherit';
        if (elStyles.color) {
          ({ color } = elStyles);
        }
        node.setAttribute(DATA_BE_STYLE_DEFAULT, styles['text-decoration'] || 'true');
        node.setAttribute('style', `text-decoration: underline; color: ${color};`);
      } else if (style) {
        node.setAttribute('style', style);
      }
    }
  } else if (cmd === 'unlink') {
    if (range.startOffset === 0 && range.endOffset === 0) {
      // The entire block is selected. We can work directly on the block element.
      node = element;
    } else {
      node = browser.getSelectedNode(contentDocument, contentWindow);
    }

    let dd;
    if (node) {
      dd = node.getAttribute(DATA_BE_STYLE_DEFAULT);
      if (dd) {
        if (node.getAttribute(DATA_BE_STYLE_ORIG) !== null) {
          node.setAttribute('style', node.getAttribute(DATA_BE_STYLE_ORIG));
        }

        const styles = HTMLUtils.getStyleObject(node.getAttribute('style') || '');
        if (dd === 'true') {
          delete styles['text-decoration'];
        } else {
          styles['text-decoration'] = dd;
        }

        node.removeAttribute(DATA_BE_STYLE_DEFAULT);
        node.removeAttribute(DATA_BE_STYLE_ORIG);
        node.removeAttribute('data-be-anchor');
        node.setAttribute('style', HTMLUtils.serializeStyleObject(styles));
      }
    }

    contentDocument.execCommand(cmd, false, value);

    node = browser.getSelectedNode(contentDocument, contentWindow);
    if (!dd || dd === 'none') {
      // node.removeAttribute('style');
      if (node.parentElement && node.parentElement.tagName === 'FONT') {
        const newNode = node.cloneNode(true);
        node.parentElement.parentElement.replaceChild(newNode, node.parentElement);
      }
    } else if (node.tagName === 'FONT') {
      node.removeAttribute('color');
    }
  } else {
    contentDocument.execCommand(cmd, false, value);
  }

  if (index !== undefined && index !== anchorStyleIndexDefault) {
    anchorStyleIndex = index;
  } else {
    anchorStyleIndex = anchorStyleIndexDefault;
  }

  return {
    ...state,
    linkValue,
    linkAlias,
    anchorStyleIndex,
    activeTools: queryForActiveTools(contentDocument, contentWindow, activeTools)
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUpdateLink = (state, action) => {
  let { anchorStyleIndex } = state;
  const { linkValue, linkAlias, element } = action;

  if (element && anchorStyleIndex === anchorStyleIndexNone) {
    anchorStyleIndex = anchorStyleIndexDefault;
  }

  return {
    ...state,
    linkAlias,
    linkValue,
    anchorStyleIndex
  };
};

/**
 * @param {*} state
 */
const onUpdateLinkValue = (state) => {
  const { contentDocument, contentWindow } = state;
  let { linkValue, linkAlias } = state;

  const node = browser.getSelectedNode(contentDocument, contentWindow);
  if (node && node.tagName === 'A') {
    linkValue = node.getAttribute('href') || '';
    linkAlias = node.getAttribute('alias') || '';
  }

  return {
    ...state,
    linkValue,
    linkAlias
  };
};

const handlers = {
  [types.EDITABLE_RESET]:             onReset,
  [types.EDITABLE_INIT]:              onInit,
  [types.EDITABLE_EXEC]:              onExec,
  [types.EDITABLE_UPDATE_LINK]:       onUpdateLink,
  [types.EDITABLE_TOOLS_QUERY]:       onToolsQuery,
  [types.EDITABLE_UPDATE_LINK_VALUE]: onUpdateLinkValue
};

export default createReducer(initialState, handlers);
