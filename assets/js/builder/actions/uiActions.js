import { useMemo } from 'react';
import { bindActionCreators } from 'redux';
import { useDispatch } from 'react-redux';
import { uniqueID } from 'utils';
import { builderDeselectAll } from './builderActions';
import { actions as commentActions } from './commentActions';

export const UI_TOAST            = 'UI_TOAST';
export const UI_TOAST_REMOVE     = 'UI_TOAST_REMOVE';
export const UI_SIDEBAR_SECTION  = 'UI_SIDEBAR_SECTION';
export const UI_PREVIEW_DEVICE   = 'UI_PREVIEW_DEVICE';
export const UI_MODAL            = 'UI_MODAL';
export const UI_CONFIRM          = 'UI_CONFIRM';
export const UI_CONFIRM_CLOSE    = 'UI_CONFIRM_CLOSE';
export const UI_TOGGLE_ACTIVITY  = 'UI_TOGGLE_ACTIVITY';
export const UI_TOGGLE_CHECKLIST = 'UI_TOGGLE_CHECKLIST';
export const UI_TOGGLE_UPGRADING = 'UI_TOGGLE_UPGRADING';

/**
 * @param {string} message
 * @param {number} timeout
 * @returns {{type: string, message: *}}
 */
export const uiToast = (message, timeout = 2000) => {
  return (dispatch) => {
    const id = uniqueID();
    dispatch({
      type: UI_TOAST,
      message,
      id
    });

    setTimeout(() => {
      dispatch({
        type: UI_TOAST_REMOVE,
        id
      });
    }, timeout);
  };
};

/**
 * @param {string} sidebarSection
 * @returns {{type: string, sidebarSection: *}}
 */
export const uiSidebarSection = (sidebarSection) => {
  return {
    type: UI_SIDEBAR_SECTION,
    sidebarSection
  };
};

/**
 * @param {string} previewDevice
 * @returns {{type: string, previewDevice: *}}
 */
export const uiPreviewDevice = (previewDevice) => {
  return (dispatch) => {
    dispatch(builderDeselectAll());
    dispatch({
      type: UI_PREVIEW_DEVICE,
      previewDevice
    });
  };
};

/**
 * @param {string} name
 * @param {boolean} open
 * @param {*} meta
 * @returns {{meta: *, name: *, type: string, open: *}}
 */
export const uiModal = (name, open, meta = {}) => {
  return (dispatch) => {
    dispatch({
      name,
      open,
      meta,
      type:  UI_MODAL,
      close: () => {
        dispatch(uiModal(name, false));
      }
    });
  };
};

/**
 * @param isActivityOpen
 * @return {{type: string, isCommentsOpen: null}}
 */
export const uiToggleActivity = (isActivityOpen = null) => {
  return (dispatch, getState) => {
    if (isActivityOpen === false || getState().ui.isActivityOpen) {
      dispatch(commentActions.attachBlock(null));
    }

    dispatch({
      type: UI_TOGGLE_ACTIVITY,
      isActivityOpen,
    });
  };
};

/**
 * @param isChecklistOpen
 * @return {(function(*): void)|*}
 */
export const uiToggleChecklist = (isChecklistOpen = null) => {
  return (dispatch) => {
    dispatch({
      type: UI_TOGGLE_CHECKLIST,
      isChecklistOpen,
    });
  };
};

/**
 * @param isUpgrading
 * @returns {{isUpgrading: null, type: string}}
 */
export const uiToggleUpgrading = (isUpgrading = null) => {
  return {
    type: UI_TOGGLE_UPGRADING,
    isUpgrading,
  };
};

/**
 * @param index
 * @returns {{index, type: string}}
 */
export const uiConfirmClose = (index = -1) => {
  return async (dispatch) => {
    dispatch({
      type:  UI_CONFIRM_CLOSE,
      index: index === -1 ? 0 : index,
    });

    return index;
  };
};

/**
 * @param title
 * @param content
 * @param buttons
 * @param options
 * @param index
 * @param isNotice
 * @returns {{buttons, type: string, title, content}}
 */
export const uiConfirm = (title, content = '', buttons = undefined, options = {}, index = -1, isNotice = false) => {
  return async (dispatch, getState) => {
    if (title === false) {
      return dispatch(uiConfirmClose(index));
    }

    let next = index;
    const { confirms } = getState().ui;
    if (index === -1) {
      next = confirms.length;
    }

    dispatch({
      type: UI_CONFIRM,
      isNotice,
      index,
      title,
      content,
      buttons,
      options,
    });

    return next;
  };
};

/**
 * @param content
 * @param status
 * @param index
 * @returns {{buttons: null, type: string, title, content: string, status: string}}
 */
export const uiConfirmLoading = (content, status = '', index = -1) => {
  return async (dispatch) => {
    if (content === false) {
      return dispatch(uiConfirmClose(index));
    }

    return dispatch(uiConfirm('', content, null, {
      status,
      loading:   true,
      animation: 'zoomIn',
    }, index));
  };
};

/**
 * @param theme
 * @param content
 * @returns {{buttons: null, isNotice: boolean, options: {theme}, type: string, title: string, content}}
 */
export const uiNotice = (theme, content) => {
  return async (dispatch) => {
    return dispatch(uiConfirm('', content, null, {
      theme,
      animation: 'zoomIn',
    }, -1, true));
  };
};

/**
 * @param title
 * @param content
 * @param variant
 * @param index
 * @param onConfirm
 * @returns {{buttons: [{variant: string, action: action, text: string}], isNotice: boolean, options: {animation: string}, type: string, title, content: string}}
 */
export const uiAlert = (title, content = '', variant = 'main', index = -1, onConfirm = undefined) => {
  return async (dispatch) => {
    if (title === false) {
      return dispatch(uiConfirmClose(index));
    }

    const buttons = [
      {
        text:   'Okay',
        action: () => {},
        variant,
      }
    ];

    return dispatch(uiConfirm(title, content, buttons, {
      onConfirm,
      animation: 'zoomIn',
    }, index));
  };
};

/**
 * @param title
 * @param value
 * @param placeholder
 * @param onConfirm
 * @param buttons
 * @param options
 * @returns {(function(*): Promise<void>)|*}
 */
export const uiPrompt = (
  title,
  value = '',
  placeholder = '',
  onConfirm = () => {},
  buttons = undefined,
  options = {}
) => {
  return async (dispatch) => {
    if (title === false) {
      return dispatch(uiConfirmClose());
    }

    return dispatch(uiConfirm(title, '', buttons, {
      prompt:    value,
      animation: 'zoomIn',
      placeholder,
      onConfirm,
      ...options
    }));
  };
};

export const actions = {
  uiToast,
  uiSidebarSection,
  uiPreviewDevice,
  uiModal,
  modal:           uiModal,
  toggleActivity:  uiToggleActivity,
  toggleChecklist: uiToggleChecklist,
  toggleUpgrading: uiToggleUpgrading,
  confirm:         uiConfirm,
  confirmClose:    uiConfirmClose,
  confirmLoading:  uiConfirmLoading,
  notice:          uiNotice,
  alert:           uiAlert,
  prompt:          uiPrompt
};

/**
 * @returns {{}}
 */
export const useUIActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
