import { BlockEngine, RuleEngine, BlockCollection, CodeBlocks, Data, UndoController } from 'builder/engine';
import { DATA_HOSTED, DATA_IMG_ID } from 'builder/engine/constants';
import cloneDeep from 'clone-deep';
import objects from 'utils/objects';
import arrays from 'utils/arrays';
import browser, { iFrameSrc } from 'utils/browser';
import * as types from '../actions/builderActions';

const initialState = {
  id:                  0,
  tid:                 0,
  version:             0,
  emailVersion:        0,
  templateVersion:     0,
  isCurrentVersion:    true,
  tmhEnabled:          false,
  people:              [],
  mode:                '',
  html:                '',
  origHtml:            '',
  title:               '',
  token:               '',
  previewUrl:          '',
  previewToken:        '',
  grant:               {},
  groups:              {},
  groupIndexes:        {},
  linkStyles:          [],
  layouts:             [],
  libraries:           [],
  pinGroups:           [],
  sections:            [],
  components:          [],
  zones:               [],
  anchorStyles:        [],
  blocks:              new BlockCollection([]),
  activeID:            -1,
  hoverID:             -1,
  hoverSectionID:      -1,
  activeSectionID:     -1,
  hoverRegionID:       -1,
  hoverComponentID:    -1,
  hoverBGColorID:      -1,
  editingID:           -1,
  draggingBlockID:     -1,
  dropZoneID:          -1,
  draggableID:         -1,
  draggable:           null,
  draggableRect:       {},
  droppedPosition:     { pageX: 0, pageY: 0 },
  draggingPosition:    { pageX: 0, pageY: 0 },
  hoverMenus:          {},
  editing:             false,
  isEmpty:             false,
  contentEditableRect: null,
  gridVisible:         false,
  isChanged:           false,
  canvas:              null,
  canvasHeight:        0,
  iframe:              null,
  iframeRect:          {},
  iframeReady:         false,
  changedImages:       [],
  expandedBlocks:      [],
  imageDims:           {},
  templateLinkParams:  [],
  emailLinkParams:     {},
  tpaEnabled:          false,
  epaEnabled:          false,
  tmpAliasEnabled:     false,
  emaAliasEnabled:     false,
  scrollTop:           0,
  openCount:           0,
  colorScheme:         'light',
  hasColorScheme:      false,
  room:                [],
  isLoaded:            false,
  uploadingStatus:     null,
  history:             [],
  future:              [],
  isOwner:             false,
  isAdmin:             false,
  isEditor:            false,
  isFirstRulesEdit:    false,
  upgrading:           [],
  upgradePercent:      0,
  scrollToBlock:       0,
};

const defaultState = objects.clone(initialState);
let stateSnapshot = {};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onInitialState = (state, action) => {
  const { initialState: is } = action;

  return {
    ...state,
    ...is,
  };
};

/**
 *
 */
const onClearState = () => {
  return objects.clone(defaultState);
};

/**
 * @param {*} state
 * @param {*} action
 */
const onOpen = (state, action) => {
  const { openCount } = state;

  return {
    ...state,
    ...action,
    openCount: openCount + 1,
    isLoaded:  true,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUpdateHTML = (state, action) => {
  const { html } = action;

  return {
    ...state,
    html
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSetHTML = (state, action) => {
  const { html, origHtml, blockID } = action;

  if (blockID === -1) {
    const { iframe } = state;
    browser.iFrameSrc(iframe, html);
    const { blocks, zones, anchorStyles } = BlockEngine.findBlocks();

    return {
      ...state,
      openCount: state.openCount + 1,
      origHtml:  origHtml || state.origHtml,
      anchorStyles,
      blocks,
      zones,
      // html
    };
  }

  /* const { blocks } = state;
  const block = blocks.getByID(blockID);
  console.log(blocks); */
  // block.element.innerHTML = html;

  return state;
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSaveTemplate = (state, action) => {
  const { html, origHtml, components, sections, groups, templateVersion } = action;

  const { iframe } = state;
  browser.iFrameSrc(iframe, html);
  const { blocks, zones, anchorStyles } = BlockEngine.findBlocks();

  return {
    ...state,
    openCount: state.openCount + 1,
    templateVersion,
    components,
    sections,
    origHtml,
    anchorStyles,
    groups,
    blocks,
    zones,
    html,
  };
};

/**
 * @param {*} state
 */
const onOpened = (state) => {
  stateSnapshot = state;

  return {
    ...state
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onIFrameMounted = (state, action) => {
  const { iframe, isRulesEditing } = action;

  if (isRulesEditing) {
    BlockEngine.setIFrame(iframe, false);
    RuleEngine.setIframe(iframe);
    const iframeRect = iframe.getBoundingClientRect();

    return {
      ...state,
      iframe,
      iframeRect,
    };
  }

  const { blocks, zones, anchorStyles } = BlockEngine.setIFrame(iframe);
  const iframeRect = iframe.getBoundingClientRect();

  return {
    ...state,
    iframe,
    blocks,
    zones,
    iframeRect,
    anchorStyles
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onIFrameRefresh = (state, action) => {
  const { html } = action;
  const { iframe } = state;

  if (html) {
    iFrameSrc(iframe, html);
  }

  const { blocks, zones, anchorStyles } = BlockEngine.setIFrame(iframe);
  const iframeRect = iframe.getBoundingClientRect();

  return {
    ...state,
    blocks,
    zones,
    anchorStyles,
    iframeRect,
    iframeReady: true
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onVariation = (state, action) => {
  const { groupIndexes, iframe } = state;
  const { blockID, variationIndex } = action;

  const { blocks, zones } = BlockEngine.changeBlockVariation(blockID, variationIndex);
  groupIndexes[blockID]   = variationIndex;

  let lastID = Data.getBlockID(BlockEngine.lastVariantElement);
  if (!lastID) {
    lastID = -1;
  }

  const html = browser.iFrameSrc(iframe);
  // socketHTML(html);

  return {
    ...state,
    html,
    blocks,
    zones,
    groupIndexes,
    isChanged:       true,
    hoverSectionID:  lastID,
    activeSectionID: lastID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onDrop = (state, action) => {
  const { iframe, draggable } = state;
  const { dropZoneID } = action;

  if (draggable.type === 'component' && state.isEmpty) {
    return state;
  }

  const { blocks, zones } = BlockEngine.addBlock(draggable, dropZoneID);
  const html = browser.iFrameSrc(iframe);
  // socketHTML(html);

  return {
    ...state,
    blocks,
    zones,
    isChanged:   true,
    isEmpty:     false,
    dropZoneID:  -1,
    draggableID: -1,
    html
  };
};

/**
 * @param {*} state
 * @param {*} action
 * @returns {*}
 */
const onMove = (state, action) => {
  const { iframe } = state;
  const { blockID, direction } = action;

  const { blocks, zones } = BlockEngine.moveBlock(blockID, direction);

  let activeID = -1;
  if (direction === 'up') {
    activeID = blockID - 1;
  } else if (direction === 'down') {
    activeID = blockID + 1;
  }

  const html = browser.iFrameSrc(iframe);
  // socketHTML(html);

  return {
    ...state,
    blocks,
    zones,
    activeID,
    hoverID:   -1,
    isChanged: true,
    html
  };
};

/**
 * @param {*} state
 */
const onRefreshRects = (state) => {
  const { blocks, zones } = BlockEngine.refreshRects();

  return {
    ...state,
    blocks,
    zones
  };
};

/**
 * @param {*} state
 */
const onUpdateBlocks = (state) => {
  const { blocks, zones } = BlockEngine.refreshBlocks();

  return {
    ...state,
    blocks: objects.clone(blocks),
    zones
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUpdateBlock = (state, action) => {
  // eslint-disable-next-line prefer-const
  const { iframe } = state;
  let { activeID, blocks, zones } = state;
  const { blockID, field, value, alt } = action;

  if (field !== '') {
    ({ blocks, zones } = BlockEngine.updateBlock(blockID, field, value));
  }

  // Update variations of the block.
  const block = blocks.getByID(blockID);
  if (block) {
    if (block.parentSectionID !== -1) {
      const section = blocks.getByID(block.parentSectionID);
      if (section && section.hasVariations()) {
        BlockEngine.restoreStoredContent(block.parentSectionID, block.element);
      }
    }
    if (block.parentRegionID !== -1) {
      const section = blocks.getByID(block.parentRegionID);
      if (section && section.hasVariations()) {
        BlockEngine.restoreStoredContent(block.parentRegionID, block.element);
      }
    }
  }

  if (field === 'image') {
    if (value.id) {
      block.element.setAttribute(DATA_IMG_ID, value.id);
      block.element.setAttribute(DATA_HOSTED, '1');
    }
    // changedImages.push(value.original);
    activeID = -1;
  } else if (field === 'background') {
    if (value.id) {
      block.element.setAttribute(DATA_IMG_ID, value.id);
      block.element.setAttribute(DATA_HOSTED, '1');
    }
    // changedImages.push(value.original);
    activeID = -1;
  } else if (field === 'href' && value !== '' && block && block.element.tagName === 'IMG') {
    const anchor = browser.iFrameDocument(iframe).createElement('a');
    anchor.setAttribute('href', value);
    anchor.setAttribute('alias', alt);
    anchor.appendChild(block.element.cloneNode());
    block.element.parentNode.replaceChild(anchor, block.element);
    block.element = anchor;
    ({ blocks } = BlockEngine.findBlocks());
  } else if (field === 'href' && value === '' && block && block.element.tagName === 'IMG') {
    const img = block.element.parentNode.querySelector('img');
    block.element.parentNode.replaceWith(img);
    ({ blocks } = BlockEngine.findBlocks());
  }

  const html = browser.iFrameSrc(iframe);
  // socketHTML(html);

  return {
    ...state,
    blocks,
    zones,
    activeID,
    // changedImages,
    html
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onRemoveBlock = (state, action) => {
  const { iframe } = state;
  const { blockID } = action;

  const { blocks, zones } = BlockEngine.removeBlock(blockID);
  const html = browser.iFrameSrc(iframe);
  // socketHTML(html);

  return {
    ...state,
    blocks,
    zones,
    activeID:  -1,
    hoverID:   -1,
    editingID: -1,
    isChanged: true,
    html
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onCloneBlock = (state, action) => {
  const { iframe } = state;
  const { blockID } = action;

  const { blocks, zones } = BlockEngine.cloneBlock(blockID);
  const html = browser.iFrameSrc(iframe);
  // socketHTML(html);

  return {
    ...state,
    blocks,
    zones,
    activeID:  -1,
    hoverID:   -1,
    editingID: -1,
    isChanged: true,
    html
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onExpandBlock = (state, action) => {
  const { iframe } = state;
  const { blockID } = action;
  const expandedBlocks = Array.from(state.expandedBlocks);

  const el = browser.iFrameDocument(iframe).querySelector(`[data-be-id="${blockID}"]`);
  if (!el) {
    return state;
  }

  const index = expandedBlocks.indexOf(blockID);
  if (index === -1) {
    CodeBlocks.expand(el);
    expandedBlocks.push(blockID);
  } else {
    CodeBlocks.collapse(el);
    expandedBlocks.splice(index, 1);
  }

  return {
    ...state,
    expandedBlocks,
    hoverSectionID: -1
  };
};

/**
 * @param {*} state
 * @param {*} action
 * @returns {*}
 */
const onContentEditing = (state, action) => {
  // eslint-disable-next-line prefer-const
  let { activeID, hoverID, contentEditableRect } = state;
  const { editingID, cloneCallback } = action;

  if (editingID !== -1) {
    const block = BlockEngine.startContentEditing(editingID, cloneCallback);

    contentEditableRect = block.element.getBoundingClientRect();
  } else if (editingID === -1) {
    BlockEngine.finishContentEditing(state.editingID);

    activeID = -1;
    hoverID  = -1;
  }

  return {
    ...state,
    activeID,
    hoverID,
    editingID,
    contentEditableRect,
    isChanged: true
  };
};

/**
 * @param {*} state
 */
const onRollbackEditing = (state) => {
  BlockEngine.rollbackContentEditing();

  return state;
};

/**
 * @param {*} state
 * @param {*} action
 */
const onEditing = (state, action) => {
  const { editing } = action;

  return {
    ...state,
    editing
  };
};

/**
 * @param {*} state
 */
const onCancelEditing = (state) => {
  const { iframe, mode, layouts } = state;

  browser.iFrameSrc(iframe, stateSnapshot.html);

  return {
    ...stateSnapshot,
    layouts,
    upgrading: [],
    editing:   mode.indexOf('template') === 0,
    isChanged: false,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onHoverID = (state, action) => {
  const { hoverID } = action;

  return {
    ...state,
    hoverID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onHoverSectionID = (state, action) => {
  const { hoverSectionID } = action;

  return {
    ...state,
    hoverSectionID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onHoverRegionID = (state, action) => {
  const { hoverRegionID } = action;

  return {
    ...state,
    hoverRegionID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onHoverComponentID = (state, action) => {
  const { hoverComponentID } = action;

  return {
    ...state,
    hoverComponentID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onHoverBGColorID = (state, action) => {
  const { hoverBGColorID } = action;

  return {
    ...state,
    hoverBGColorID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onDraggingBlock = (state, action) => {
  const { draggingBlockID, pageX, pageY } = action;

  return {
    ...state,
    draggingBlockID,
    draggingPosition: { pageX, pageY }
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onActiveSectionID = (state, action) => {
  const { activeSectionID } = action;

  return {
    ...state,
    activeSectionID,
    hoverSectionID: -1
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onDropZoneID = (state, action) => {
  const { dropZoneID } = action;

  return {
    ...state,
    dropZoneID
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onActiveID = (state, action) => {
  const { activeID } = action;

  return {
    ...state,
    activeID
  };
};

/**
 * @param {*} state
 */
const onDeselectAll = (state) => {
  return {
    ...state,
    activeID:        -1,
    hoverID:         -1,
    editingID:       -1,
    hoverSectionID:  -1,
    hoverRegionID:   -1,
    activeSectionID: -1,
    hoverMenus:      {}
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onDragStart = (state, action) => {
  const { draggable, draggableClone } = action;

  const draggableID   = draggable.id;
  const draggableRect = draggableClone.getBoundingClientRect();

  return {
    ...state,
    draggable,
    draggableID,
    draggableRect
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onDragEnd = (state, action) => {
  const { pageX, pageY } = action;

  return {
    ...state,
    draggableID:     -1,
    droppedPosition: { pageX, pageY }
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onToggleGrid = (state, action) => {
  let { gridVisible } = action;

  if (typeof gridVisible !== 'boolean') {
    gridVisible = !state.gridVisible;
  }

  return {
    ...state,
    gridVisible
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSave = (state, action) => {
  const { version } = action;
  stateSnapshot = state;

  return {
    ...state,
    editing:        false,
    emailVersion:   version,
    expandedBlocks: []
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onScrollTop = (state, action) => {
  const { scrollTop } = action;

  return {
    ...state,
    scrollTop
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onCanvasMounted = (state, action) => {
  const { canvas } = action;

  return {
    ...state,
    canvas
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onLayoutLoad = (state, action) => {
  return {
    ...state,
    ...action,
    isChanged: false
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onLayoutSave = (state, action) => {
  const layouts = objects.clone(state.layouts);
  const { id, title, screenshotDesktop, screenshotMobile } = action;

  layouts.push({
    id,
    title,
    screenshotDesktop,
    screenshotMobile
  });

  return {
    ...state,
    layouts
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onLayoutSettings = (state, action) => {
  const layouts = objects.clone(state.layouts);
  const { id, title } = action;

  const index = arrays.findIndexByID(layouts, id);
  if (index !== -1) {
    layouts[index].title = title;
  }

  return {
    ...state,
    layouts
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUpdateLayouts = (state, action) => {
  const { layouts } = action;

  return {
    ...state,
    layouts
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onLayoutDelete = (state, action) => {
  const layouts = objects.clone(state.layouts);
  const { id } = action;

  const index = arrays.findIndexByID(layouts, id);
  if (index !== -1) {
    layouts.splice(index, 1);
  }

  return {
    ...state,
    layouts
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onHoverMenus = (state, action) => {
  const { hoverMenus } = state;
  const { menuName, adding } = action;

  hoverMenus[menuName] = adding;

  return {
    ...state,
    hoverMenus
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onImageDim = (state, action) => {
  const imageDims = objects.clone(state.imageDims);
  const { src, width, height } = action;

  imageDims[src] = { width, height };

  return {
    ...state,
    imageDims
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onCanvasHeight = (state, action) => {
  const { canvasHeight } = action;

  return {
    ...state,
    canvasHeight
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSetColorScheme = (state, action) => {
  const { colorScheme } = action;

  return {
    ...state,
    colorScheme
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUpdateLayout = (state, action) => {
  const layouts    = objects.clone(state.layouts);
  const { layout } = action;

  const index = arrays.findIndexByID(layouts, layout.id);
  if (index !== -1) {
    layouts[index] = layout;
  }

  return {
    ...state,
    layouts
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSetGrant = (state, action) => {
  const { grant } = action;

  return {
    ...state,
    grant
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSetState = (state, action) => {
  const { key, value } = action;

  return {
    ...state,
    [key]: value
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUpdateRoom = (state, action) => {
  const { room } = action;

  return {
    ...state,
    room
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUpdateLibraries = (state, action) => {
  const libraries = Array.from(action.libraries);

  return {
    ...state,
    libraries
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSetEmailVersion = (state, action) => {
  const { emailVersion, previewUrl } = action;

  return {
    ...state,
    emailVersion,
    version:    emailVersion,
    previewUrl: previewUrl || state.previewUrl,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUploadingStatus = (state, action) => {
  const { uploadingStatus } = action;

  return {
    ...state,
    uploadingStatus,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSetMode = (state, action) => {
  return {
    ...state,
    mode: action.mode,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onToggleVariantVisibility = (state, action) => {
  const { visible } = action;

  if (visible) {
    const { iframe } = state;
    RuleEngine.setIframe(iframe);
    RuleEngine.findBlocks();
    // browser.iFrameSrc(iframe, RuleEngine.findBlocks());

    return state;
  }

  const { iframe, html } = state;
  browser.iFrameSrc(iframe, html);

  const { blocks, zones, anchorStyles } = BlockEngine.setIFrame(iframe);
  const iframeRect = iframe.getBoundingClientRect();

  return {
    ...state,
    blocks,
    zones,
    anchorStyles,
    iframeRect,
    iframeReady: true
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSetFirstRulesEdit = (state, action) => {
  return {
    ...state,
    isFirstRulesEdit: action.isFirstRulesEdit,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onAddUpgrading = (state, action) => {
  const upgrading = Array.from(state.upgrading);
  const { item } = action;

  const index = upgrading.indexOf(item);
  if (index === -1) {
    upgrading.push(item);
  }

  return {
    ...state,
    upgrading,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onRemoveUpgrading = (state, action) => {
  const upgrading = Array.from(state.upgrading);
  const { item } = action;

  const index = upgrading.indexOf(item);
  if (index !== -1) {
    upgrading.splice(index, 1);
  }

  return {
    ...state,
    upgrading,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUpgradePercent = (state, action) => {
  return {
    ...state,
    upgradePercent: action.percent <= 100 ? action.percent : 100,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onPinGroupSave = (state, action) => {
  const pinGroups = cloneDeep(state.pinGroups);

  pinGroups.push(action.pinGroup);

  return {
    ...state,
    pinGroups,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onPinGroupUpdate = (state, action) => {
  const pinGroups = Array.from(state.pinGroups);
  const { pinGroup } = action;

  const index = pinGroups.findIndex(p => p.id === pinGroup.id);
  if (index !== -1) {
    pinGroups[index] = pinGroup;
  }

  return {
    ...state,
    pinGroups,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onPinGroupDelete = (state, action) => {
  const libraries = Array.from(state.libraries);
  const pinGroups = Array.from(state.pinGroups);
  const { id } = action;

  const index = pinGroups.findIndex(p => p.id === id);
  if (index !== -1) {
    pinGroups.splice(index, 1);
  }

  libraries.forEach((library) => {
    if (library.pinGroup && library.pinGroup === id) {
      library.pinGroup = null;
    }
  });

  return {
    ...state,
    pinGroups,
    libraries,
  };
};

/**
 * @param state
 * @param action
 * @return {*&{scrollToBlock: (number|number|number|*)}}
 */
const onScrollToBlock = (state, action) => {
  return {
    ...state,
    scrollToBlock: action.blockId,
  };
};

const handlers = {
  [types.BUILDER_INITIAL_STATE]:             onInitialState,
  [types.BUILDER_CLEAR_STATE]:               onClearState,
  [types.BUILDER_SET_STATE]:                 onSetState,
  [types.BUILDER_OPEN]:                      onOpen,
  [types.BUILDER_UPDATE_HTML]:               onUpdateHTML,
  [types.BUILDER_SET_HTML]:                  onSetHTML,
  [types.BUILDER_OPENED]:                    onOpened,
  [types.BUILDER_SAVE]:                      onSave,
  [types.BUILDER_DROP]:                      onDrop,
  [types.BUILDER_MOVE]:                      onMove,
  [types.BUILDER_LAYOUT_LOAD]:               onLayoutLoad,
  [types.BUILDER_LAYOUT_SAVE]:               onLayoutSave,
  [types.BUILDER_LAYOUT_DELETE]:             onLayoutDelete,
  [types.BUILDER_LAYOUT_SETTINGS]:           onLayoutSettings,
  [types.BUILDER_UPDATE_LAYOUTS]:            onUpdateLayouts,
  [types.BUILDER_EDITING]:                   onEditing,
  [types.BUILDER_CANCEL_EDITING]:            onCancelEditing,
  [types.BUILDER_HOVER_MENUS]:               onHoverMenus,
  [types.BUILDER_HOVER_ID]:                  onHoverID,
  [types.BUILDER_HOVER_SECTION_ID]:          onHoverSectionID,
  [types.BUILDER_ACTIVE_SECTION_ID]:         onActiveSectionID,
  [types.BUILDER_HOVER_REGION_ID]:           onHoverRegionID,
  [types.BUILDER_HOVER_COMPONENT_ID]:        onHoverComponentID,
  [types.BUILDER_HOVER_BG_COLOR_ID]:         onHoverBGColorID,
  [types.BUILDER_DRAGGING_BLOCK]:            onDraggingBlock,
  [types.BUILDER_DROP_ZONE_ID]:              onDropZoneID,
  [types.BUILDER_ACTIVE_ID]:                 onActiveID,
  [types.BUILDER_DESELECT_ALL]:              onDeselectAll,
  [types.BUILDER_CANVAS_MOUNTED]:            onCanvasMounted,
  [types.BUILDER_IFRAME_MOUNTED]:            onIFrameMounted,
  [types.BUILDER_IFRAME_REFRESH]:            onIFrameRefresh,
  [types.BUILDER_UPDATE_BLOCKS]:             onUpdateBlocks,
  [types.BUILDER_UPDATE_BLOCK]:              onUpdateBlock,
  [types.BUILDER_REMOVE_BLOCK]:              onRemoveBlock,
  [types.BUILDER_CLONE_BLOCK]:               onCloneBlock,
  [types.BUILDER_EXPAND_BLOCK]:              onExpandBlock,
  [types.BUILDER_TOGGLE_GRID]:               onToggleGrid,
  [types.BUILDER_DRAG_START]:                onDragStart,
  [types.BUILDER_DRAG_END]:                  onDragEnd,
  [types.BUILDER_SCROLL_TOP]:                onScrollTop,
  [types.BUILDER_CONTENT_EDITING]:           onContentEditing,
  [types.BUILDER_VARIATION]:                 onVariation,
  [types.BUILDER_REFRESH_RECTS]:             onRefreshRects,
  [types.BUILDER_IMAGE_DIM]:                 onImageDim,
  [types.BUILDER_CANVAS_HEIGHT]:             onCanvasHeight,
  [types.BUILDER_ROLLBACK_EDITING]:          onRollbackEditing,
  [types.BUILDER_SET_COLOR_SCHEME]:          onSetColorScheme,
  [types.BUILDER_UPDATE_LAYOUT]:             onUpdateLayout,
  [types.BUILDER_SET_GRANT]:                 onSetGrant,
  [types.BUILDER_UPDATE_LIBRARIES]:          onUpdateLibraries,
  [types.BUILDER_UPDATE_ROOM]:               onUpdateRoom,
  [types.BUILDER_SET_EMAIL_VERSION]:         onSetEmailVersion,
  [types.BUILDER_UPLOADING_STATUS]:          onUploadingStatus,
  [types.BUILDER_SET_MODE]:                  onSetMode,
  [types.BUILDER_TOGGLE_VARIANT_VISIBILITY]: onToggleVariantVisibility,
  [types.BUILDER_SAVE_TEMPLATE]:             onSaveTemplate,
  [types.BUILDER_SET_FIRST_RULES_EDIT]:      onSetFirstRulesEdit,
  [types.BUILDER_ADD_UPGRADING]:             onAddUpgrading,
  [types.BUILDER_REMOVE_UPGRADING]:          onRemoveUpgrading,
  [types.BUILDER_UPGRADE_PERCENT]:           onUpgradePercent,
  [types.BUILDER_PIN_GROUP_SAVE]:            onPinGroupSave,
  [types.BUILDER_PIN_GROUP_UPDATE]:          onPinGroupUpdate,
  [types.BUILDER_PIN_GROUP_DELETE]:          onPinGroupDelete,
  [types.BUILDER_SCROLL_TO_BLOCK]:           onScrollToBlock,
};

export default UndoController.createUndoReducer(initialState, handlers, {
  undoAction:  types.BUILDER_UNDO,
  redoAction:  types.BUILDER_REDO,
  clearAction: types.BUILDER_CLEAR_UNDO,
  htmlAction:  types.BUILDER_PUSH_HTML,
  include:     [
    types.BUILDER_DROP,
    types.BUILDER_REMOVE_BLOCK,
    types.BUILDER_MOVE,
    types.BUILDER_VARIATION,
    types.BUILDER_UPDATE_BLOCK,
    types.BUILDER_CLONE_BLOCK,
    types.BUILDER_VARIATION,
  ],
  filter: (action) => {
    if (action.type === types.BUILDER_OPEN) {
      return false;
    }
    if (action.type === types.BUILDER_UPDATE_BLOCK && action.value) {
      if (action.value.tagName && action.value.tagName === 'A') {
        return false;
      }
    }

    return !(action.type === types.BUILDER_UPDATE_BLOCK && (action.blockID === -1 || action.field === ''));
  },
});
