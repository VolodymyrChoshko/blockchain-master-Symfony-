import { anchorStyleIndexDefault, anchorStyleIndexNone } from 'builder/reducers/editableReducer';
import ContentEditable from 'builder/engine/ContentEditable';

export const EDITABLE_RESET             = 'EDITABLE_RESET';
export const EDITABLE_INIT              = 'EDITABLE_INIT';
export const EDITABLE_TOOLS_QUERY       = 'EDITABLE_TOOLS_QUERY';
export const EDITABLE_EXEC              = 'EDITABLE_EXEC';
export const EDITABLE_UPDATE_LINK       = 'EDITABLE_UPDATE_LINK';
export const EDITABLE_UPDATE_LINK_VALUE = 'EDITABLE_UPDATE_LINK_VALUE';

/**
 * @returns {{type: string}}
 */
export const editableReset = () => {
  return {
    type: EDITABLE_RESET
  };
};

/**
 * @returns {{block: *, type: string}}
 */
export const editableInit = () => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { iframe, blocks, editingID } = builder;

    dispatch({
      type:         EDITABLE_INIT,
      editingBlock: blocks.getByID(editingID),
      iframe
    });
  };
};

/**
 * @param {Event} e
 * @param {Element} element
 * @returns {{type: string}}
 */
export const editableToolsQuery = (e, element = null) => {
  return {
    type: EDITABLE_TOOLS_QUERY,
    element
  };
};

/**
 * @param {string} cmd
 * @param {string} value
 * @param {*} attributes
 * @param {*} element
 * @param {number} blockID
 * @returns {{cmd: *, type: string}}
 */
export const editableExec = (cmd, value, attributes = {}, element = null, blockID = -1) => {
  return (dispatch) => {
    if (blockID !== -1) {
      ContentEditable.emit();
    }

    dispatch({
      type: EDITABLE_EXEC,
      attributes,
      element,
      value,
      cmd
    });
    if (blockID !== -1) {
      // dispatch(builderUpdateBlock(blockID, '', null));
      ContentEditable.emit();
    }
  };
};

/**
 * @param {string} linkValue
 * @param {string} linkAlias
 * @param {Element} element
 * @returns {{linkValue: *, type: string}}
 */
export const editableUpdateLink = (linkValue, linkAlias, element) => {
  return (dispatch, getState) => {
    const { editable } = getState();
    const { anchorStyleIndex } = editable;

    if (element) {
      ContentEditable.emit();
    }

    dispatch({
      type: EDITABLE_UPDATE_LINK,
      linkValue,
      linkAlias,
      element
    });

    // Make sure the default style is always selected.
    if (element && anchorStyleIndex === anchorStyleIndexNone) {
      dispatch(editableExec('createLink', linkValue, {
        style: '',
        index: anchorStyleIndexDefault
      }, element));
    }

    ContentEditable.emit();
  };
};

/**
 * @returns {{type: string}}
 */
export const editableUpdateLinkValue = () => {
  return {
    type: EDITABLE_UPDATE_LINK_VALUE
  };
};
