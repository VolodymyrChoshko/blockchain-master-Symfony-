import { createReducer } from 'utils';
import arrays from 'utils/arrays';
import objects from 'utils/objects';
import * as types from '../actions/uiActions';

const initialState = {
  sidebarSection:    '',
  previewDevice:     'desktop',
  modals:            {},
  confirms:          [],
  toastMessages:     [],
  promptPlaceholder: '',
  promptCallback:    () => {},
  isActivityOpen:    false,
  isChecklistOpen:   false,
  isUpgrading:       false,
  zIndexHistory:     3,
  zIndexActivity:    3,
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSidebarSection = (state, action) => {
  const { sidebarSection } = action;

  return {
    ...state,
    sidebarSection
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onPreviewDevice = (state, action) => {
  const { previewDevice } = action;

  return {
    ...state,
    previewDevice
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onToast = (state, action) => {
  const toastMessages = objects.clone(state.toastMessages);
  const { id, message } = action;

  toastMessages.push({ id, message });

  return {
    ...state,
    toastMessages
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onToastRemove = (state, action) => {
  const toastMessages = objects.clone(state.toastMessages);
  const { id } = action;

  const index = arrays.findIndexByID(toastMessages, id);
  if (index !== -1) {
    toastMessages.splice(index, 1);
  }

  return {
    ...state,
    toastMessages
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onModal = (state, action) => {
  const modals = objects.clone(state.modals);
  const { name, open, meta, close } = action;

  modals[name] = { open, meta, close };

  return {
    ...state,
    modals
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onConfirm = (state, action) => {
  const confirms = objects.clone(state.confirms);
  const { title, content, options, buttons, isNotice, index } = action;

  if (index !== -1) {
    if (confirms[index] !== undefined) {
      confirms[index] = { title, content, options, buttons, isNotice };
    }
  } else {
    confirms.push({ title, content, options, buttons, isNotice });
  }

  return {
    ...state,
    confirms,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onConfirmClose = (state, action) => {
  const confirms = objects.clone(state.confirms);
  const { index } = action;

  if (confirms[index] !== undefined) {
    confirms.splice(index, 1);
  }

  return {
    ...state,
    confirms: confirms.filter(v => v),
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onToggleActivity = (state, action) => {
  let { isActivityOpen } = action;

  if (isActivityOpen === null) {
    isActivityOpen = !state.isActivityOpen;
  }

  return {
    ...state,
    isActivityOpen,
    isChecklistOpen: false,
    zIndexActivity: state.zIndexActivity + 1,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onToggleChecklist = (state, action) => {
  let { isChecklistOpen } = action;

  if (isChecklistOpen === null) {
    isChecklistOpen = !state.isChecklistOpen;
  }

  return {
    ...state,
    isChecklistOpen,
    isActivityOpen: false,
    zIndexActivity: state.zIndexActivity + 1,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onToggleUpgrading = (state, action) => {
  let { isUpgrading } = action;

  if (isUpgrading === null) {
    isUpgrading = !state.isUpgrading;
  }

  return {
    ...state,
    isUpgrading,
  };
};

const handlers = {
  [types.UI_TOAST]:            onToast,
  [types.UI_MODAL]:            onModal,
  [types.UI_CONFIRM]:          onConfirm,
  [types.UI_CONFIRM_CLOSE]:    onConfirmClose,
  [types.UI_TOAST_REMOVE]:     onToastRemove,
  [types.UI_SIDEBAR_SECTION]:  onSidebarSection,
  [types.UI_PREVIEW_DEVICE]:   onPreviewDevice,
  [types.UI_TOGGLE_ACTIVITY]:  onToggleActivity,
  [types.UI_TOGGLE_CHECKLIST]: onToggleChecklist,
  [types.UI_TOGGLE_UPGRADING]: onToggleUpgrading,
};

export default createReducer(initialState, handlers);
