import { createReducer } from 'utils';
import objects from 'utils/objects';
import arrays from 'utils/arrays';
import browser from 'utils/browser';
import * as types from '../actions/sourceActions';

const initialState = {
  wdir:           '',
  depth:          0,
  files:          [],
  sources:        [],
  folder:         '',
  preview:        null,
  imported:       null,
  selectedPath:   '',
  transferCount:  0,
  activeSourceID: browser.storage.getItem('source.activeSourceID', -1),
  activeImageSID: 0
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSources = (state, action) => {
  let { activeSourceID } = state;
  const sources = objects.clone(action.sources);

  if (activeSourceID === -1 && sources.length > 0) {
    activeSourceID = sources[0].id;
  } else if (activeSourceID !== -1) {
    const source = arrays.findByID(sources, activeSourceID);
    if (!source) {
      activeSourceID = -1;
    }
  }

  return {
    ...state,
    sources,
    activeSourceID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onActiveSourceID = (state, action) => {
  const { folder, wdir, selectedPath } = state;
  const { activeSourceID, reset } = action;

  browser.storage.setItem('source.activeSourceID', activeSourceID);

  return {
    ...state,
    wdir:         reset ? '' : wdir,
    depth:        0,
    files:        [],
    folder:       reset ? '' : folder,
    preview:      null,
    imported:     null,
    selectedPath: reset ? '' : selectedPath,
    activeSourceID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onActiveImageSID = (state, action) => {
  const { activeImageSID } = action;

  return {
    ...state,
    activeImageSID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onListFiles = (state, action) => {
  const files = objects.clone(state.files);
  const newFiles = objects.clone(action.files);
  const { wdir, depth } = action;

  if (!newFiles) {
    return {
      ...state,
      preview: null,
      files:   []
    };
  }

  files.splice(depth, files.length - depth);
  files.push({
    depth,
    dir:  wdir,
    list: newFiles
  });

  return {
    ...state,
    preview: null,
    wdir,
    depth,
    files
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onDownload = (state, action) => {
  const preview = objects.clone(action.preview);
  const { selectedPath } = action;

  return {
    ...state,
    preview,
    selectedPath
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onImportSelectedPath = (state, action) => {
  const imported = objects.clone(action.imported);

  return {
    ...state,
    imported
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSelectFolder = (state, action) => {
  const { folder } = action;

  return {
    ...state,
    folder
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onTransferCount = (state, action) => {
  const { transferCount } = action;

  return {
    ...state,
    transferCount
  };
};

const handlers = {
  [types.SOURCE_SOURCES]:              onSources,
  [types.SOURCE_LIST_FILES]:           onListFiles,
  [types.SOURCE_DOWNLOAD]:             onDownload,
  [types.SOURCE_ACTIVE_SOURCE_ID]:     onActiveSourceID,
  [types.SOURCE_SELECT_FOLDER]:        onSelectFolder,
  [types.SOURCE_TRANSFER_COUNT]:       onTransferCount,
  [types.SOURCE_ACTIVE_IMAGE_SID]:     onActiveImageSID,
  [types.SOURCE_IMPORT_SELECTED_PATH]: onImportSelectedPath
};

export default createReducer(initialState, handlers);
