import { createReducer } from 'utils';
import { types } from '../actions/ruleActions';

const initialState = {
  isEditing:        false,
  isSaving:         false,
  isCancelling:     false,
  isEditingHtml:    false,
  isFilteringHtml:  false,
  isExpandedHtml:   false,
  mode:             'editable',
  elements:         [],
  activeEdits:      [],
  hoverEdits:       [],
  activeSections:   [],
  activeComponents: [],
  hoverBGColors:    [],
  zones:            {},
  braceHtml:        '',
  isChanged:        false,
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetEditing = (state, action) => {
  return {
    ...state,
    isEditing: action.isEditing,
    zones:     action.zones,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{isEditing: (boolean|*)}}
 */
const onCancelEditing = (state, action) => {
  return {
    ...state,
    isEditing:    false,
    isCancelling: action.isCancelling,
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetSaving = (state, action) => {
  return {
    ...state,
    isSaving: action.isSaving,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{isSaving: (boolean|*)}}
 */
const onFilteringHtml = (state, action) => {
  return {
    ...state,
    isFilteringHtml: action.isFilteringHtml,
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetActiveEdit = (state, action) => {
  return {
    ...state,
    activeEdits:    action.activeEdits,
    activeSections: [],
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetActiveSections = (state, action) => {
  return {
    ...state,
    activeSections: action.activeSections,
    activeEdits:    [],
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetHoverEdits = (state, action) => {
  return {
    ...state,
    hoverEdits: action.hoverEdits,
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetEditingHtml = (state, action) => {
  return {
    ...state,
    isEditingHtml: action.isEditingHtml,
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetExpandedHtml = (state, action) => {
  return {
    ...state,
    isExpandedHtml: action.isExpandedHtml === -1 ? !state.isExpandedHtml : action.isExpandedHtml,
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onUpdateBraceHtml = (state, action) => {
  return {
    ...state,
    braceHtml: action.braceHtml,
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetZones = (state, action) => {
  return {
    ...state,
    zones: { ...action.zones },
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{mode}}
 */
const onSetMode = (state, action) => {
  return {
    ...state,
    mode: action.mode,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{mode}}
 */
const onSetChanged = (state, action) => {
  return {
    ...state,
    isChanged: action.isChanged,
  };
};

const handlers = {
  [types.CANCEL_EDITING]:      onCancelEditing,
  [types.SET_EDITING]:         onSetEditing,
  [types.SET_SAVING]:          onSetSaving,
  [types.SET_ZONES]:           onSetZones,
  [types.SET_FILTERING_HTML]:  onFilteringHtml,
  [types.SET_ACTIVE_EDIT]:     onSetActiveEdit,
  [types.SET_ACTIVE_SECTIONS]: onSetActiveSections,
  [types.SET_HOVER_EDITS]:     onSetHoverEdits,
  [types.SET_EDITING_HTML]:    onSetEditingHtml,
  [types.SET_EXPANDED_HTML]:   onSetExpandedHtml,
  [types.UPDATE_BRACE_HTML]:   onUpdateBraceHtml,
  [types.SET_MODE]:            onSetMode,
  [types.SET_CHANGED]:         onSetChanged,
};

export default createReducer(initialState, handlers);
