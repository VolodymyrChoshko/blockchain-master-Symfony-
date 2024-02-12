import * as constants from 'builder/engine/constants';
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import browser from 'utils/browser';
import { builder as builderStyles } from 'lib/styles';
import { builderActions, uiActions } from 'builder/actions';
import { BlockCollection } from 'builder/engine';
import {
  BLOCK_EDIT,
  BLOCK_SECTION,
  BLOCK_BACKGROUND,
  BLOCK_REGION,
  BLOCK_COMPONENT,
  BLOCK_BG_COLOR
} from 'builder/engine/constants';

const hoverBuffer         = 0;
const mouseOutDelay       = 5;
let mouseOutTimeout       = 0;
let mouseRegionTimeout    = 0;
let mouseSectionTimeout   = 0;
let mouseComponentTimeout = 0;
let mouseBGColorTimeout   = 0;

const mapStateToProps = state => ({
  openCount:        state.builder.openCount,
  iframe:           state.builder.iframe,
  blocks:           state.builder.blocks,
  zones:            state.builder.zones,
  activeID:         state.builder.activeID,
  editingID:        state.builder.editingID,
  history:          state.builder.history,
  future:           state.builder.future,
  hoverID:          state.builder.hoverID,
  hoverSectionID:   state.builder.hoverSectionID,
  activeSectionID:  state.builder.activeSectionID,
  hoverRegionID:    state.builder.hoverRegionID,
  hoverComponentID: state.builder.hoverComponentID,
  hoverBGColorID:   state.builder.hoverBGColorID,
  expandedBlocks:   state.builder.expandedBlocks,
  dropZoneID:       state.builder.dropZoneID,
  draggable:        state.builder.draggable,
  draggableID:      state.builder.draggableID,
  draggableRect:    state.builder.draggableRect,
  droppedPosition:  state.builder.droppedPosition,
  hoverMenus:       state.builder.hoverMenus,
  scrollTop:        state.builder.scrollTop,
  editing:          state.builder.editing,
  isRulesEditing:   state.rules.isEditing,
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions, uiActions)
)
export default class WatchMouse extends React.PureComponent {
  static propTypes = {
    iframe:                  PropTypes.object,
    editing:                 PropTypes.bool.isRequired,
    isRulesEditing:          PropTypes.bool.isRequired,
    openCount:               PropTypes.number.isRequired,
    blocks:                  PropTypes.instanceOf(BlockCollection).isRequired,
    zones:                   PropTypes.array.isRequired,
    activeID:                PropTypes.number.isRequired,
    editingID:               PropTypes.number.isRequired,
    hoverID:                 PropTypes.number.isRequired,
    hoverSectionID:          PropTypes.number.isRequired,
    activeSectionID:         PropTypes.number.isRequired,
    scrollTop:               PropTypes.number.isRequired,
    hoverRegionID:           PropTypes.number.isRequired,
    hoverComponentID:        PropTypes.number.isRequired,
    hoverBGColorID:          PropTypes.number.isRequired,
    history:                 PropTypes.array.isRequired,
    future:                  PropTypes.array.isRequired,
    draggable:               PropTypes.object,
    dropZoneID:              PropTypes.number.isRequired,
    draggableID:             PropTypes.number.isRequired,
    draggableRect:           PropTypes.object.isRequired,
    expandedBlocks:          PropTypes.array.isRequired,
    hoverMenus:              PropTypes.object.isRequired,
    builderActiveID:         PropTypes.func.isRequired,
    builderDrop:             PropTypes.func.isRequired,
    builderHoverID:          PropTypes.func.isRequired,
    builderHoverRegionID:    PropTypes.func.isRequired,
    builderHoverComponentID: PropTypes.func.isRequired,
    builderHoverBGColorID:   PropTypes.func.isRequired,
    builderDropZoneID:       PropTypes.func.isRequired,
    builderDeselectAll:      PropTypes.func.isRequired,
    builderExpandBlock:      PropTypes.func.isRequired
  };

  static defaultProps = {};

  /**
   *
   */
  componentDidMount() {
    this.canvas     = document.querySelector('.builder-canvas');
    this.canvasRect = this.canvas.getBoundingClientRect();

    this.wireup();
  }

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const {
      iframe,
      openCount,
      editing,
      isRulesEditing,
      draggableID,
      dropZoneID,
      builderDrop,
      history,
      future,
    } = this.props;

    if (
      iframe && !prevProps.iframe
      || editing && !prevProps.editing
      || openCount !== prevProps.openCount
      || history.length !== prevProps.history.length
      || future.length !== prevProps.future.length
      || isRulesEditing !== prevProps.isRulesEditing
    ) {
      this.wireup();
    }

    if (draggableID === -1 && prevProps.draggableID !== -1 && dropZoneID !== -1) {
      builderDrop(dropZoneID);
    }
  }

  /**
   *
   */
  componentWillUnmount() {
    const { iframe } = this.props;

    this.canvas.removeEventListener('mousemove', this.handleCanvasMouseMove, false);
    if (iframe) {
      browser.iFrameDocument(iframe).removeEventListener('mousemove', this.handleFrameMouseMove, false);
      browser.iFrameDocument(iframe).removeEventListener('mousedown', this.handleFrameClick, false);
    }
  }

  /**
   *
   */
  wireup = () => {
    const { iframe } = this.props;

    setTimeout(() => {
      this.componentWillUnmount();
      this.canvasRect = this.canvas.getBoundingClientRect();
      this.canvas.addEventListener('mousemove', this.handleCanvasMouseMove, false);
      if (iframe) {
        browser.iFrameDocument(iframe).addEventListener('mousemove', this.handleFrameMouseMove, false);
        browser.iFrameDocument(iframe).addEventListener('mousedown', this.handleFrameClick, false);
      }
    }, 1000);
  };

  /**
   * @param {MouseEvent} e
   */
  handleFrameClick = (e) => {
    const {
      blocks,
      activeID,
      editingID,
      editing,
      isRulesEditing,
      expandedBlocks,
      builderActiveID,
      builderContentEditing,
      builderActiveSectionID,
      builderDeselectAll,
      builderExpandBlock
    } = this.props;
    const { target, pageX, pageY } = e;

    if (!editing || isRulesEditing) {
      return;
    }

    const block = blocks.filterIntersecting(pageX, pageY);
    if (block && !block.element.classList.contains(constants.CLASS_BLOCK_EDIT_EMPTY)) {
      // eslint-disable-next-line max-len
      if (block.isCode() && expandedBlocks.indexOf(block.id) === -1 && expandedBlocks.indexOf(block.parentSectionID) === -1 && expandedBlocks.indexOf(block.parentComponentID) === -1) {
        if (block.parentComponentID !== -1) {
          builderExpandBlock(block.parentComponentID);
        } else {
          builderExpandBlock(block.parentSectionID !== -1 ? block.parentSectionID : block.id);
        }
      } else if (block.rules.isEditable) {
        if (block.id !== editingID && block.rules.canText) {
          builderContentEditing(block.id);
        } else if (block.id !== activeID && block.id !== editingID) {
          builderActiveID(block.id);
          if (block.parentSectionID !== -1) {
            // builderActiveSectionID(block.parentSectionID);
          }
        }
      } else if (block.isSection() && !block.rules.canAnchorEdit) {
        // builderActiveSectionID(block.id);
        if (editingID !== -1) {
          builderContentEditing(-1);
        }
        if (activeID !== -1) {
          builderActiveID(-1);
        }
      } else if (block.id !== activeID) {
        builderActiveID(block.id);
        builderActiveSectionID(-1);
        if (block.parentSectionID !== -1) {
          // builderActiveSectionID(block.parentSectionID);
        }
      } else {
        builderActiveID(-1);
      }
    } else if (!browser.hasParentClass(target, 'builder-block-container')
      && !browser.hasParentClass(target, 'builder-no-canvas-click')) {
      if (editingID !== -1) {
        builderContentEditing(-1);
      }
      builderDeselectAll();
    }
  };

  /**
   * @param {MouseEvent} e
   */
  handleCanvasMouseMove = (e) => {
    const { scrollTop } = this.props;
    const { pageX, pageY } = e;

    const offsetPageX = Math.floor(pageX - this.canvasRect.left);
    const offsetPageY = Math.floor(pageY + scrollTop) - this.canvasRect.top;
    if (offsetPageX < 0 || offsetPageY < 0) {
      return;
    }

    this.handleMove(offsetPageX, offsetPageY);
  };

  /**
   * @param {MouseEvent} e
   */
  handleFrameMouseMove = (e) => {
    const { pageX, pageY } = e;

    this.handleMove(pageX, pageY);
  };

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  handleMove = (pageX, pageY) => {
    const { editing, draggableID } = this.props;

    if (!editing) {
      this.handleSectionIntersect(pageX, pageY);
      return;
    }

    if (draggableID === -1) {
      this.handleSectionIntersect(pageX, pageY);
      this.handleBlockIntersect(pageX, pageY);
      this.handleComponentIntersect(pageX, pageY);
      this.handleRegionIntersect(pageX, pageY);
      this.handleBGColorIntersect(pageX, pageY);
    } else {
      this.handleZoneIntersect(pageX, pageY);
    }
  };

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  handleBlockIntersect = (pageX, pageY) => {
    const { blocks, builderHoverID, hoverID, hoverMenus } = this.props;

    if (hoverMenus[BLOCK_EDIT] || hoverMenus[BLOCK_BACKGROUND]) {
      clearTimeout(mouseOutTimeout);
      return;
    }

    const block = blocks.filterIntersecting(
      pageX + hoverBuffer,
      pageY + hoverBuffer,
      b => (b.isEdit() && !b.isCode())
    );

    if (block && hoverID === -1) {
      clearTimeout(mouseOutTimeout);
      builderHoverID(block.id);
    } else {
      clearTimeout(mouseOutTimeout);
      mouseOutTimeout = setTimeout(() => {
        if (block && block.id !== hoverID) {
          builderHoverID(block.id);
        } else if (!block && hoverID !== -1) {
          builderHoverID(-1);
        }
      }, mouseOutDelay);
    }
  };

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  handleRegionIntersect = (pageX, pageY) => {
    const { blocks, builderHoverRegionID, hoverRegionID, hoverMenus } = this.props;

    if (hoverMenus[BLOCK_REGION]) {
      clearTimeout(mouseRegionTimeout);
      return;
    }

    const block = blocks.filterIntersecting(
      pageX + hoverBuffer,
      pageY + hoverBuffer,
      b => b.isRegion()
    );

    if (block && hoverRegionID === -1) {
      clearTimeout(mouseRegionTimeout);
      builderHoverRegionID(block.id);
    } else {
      clearTimeout(mouseRegionTimeout);
      mouseRegionTimeout = setTimeout(() => {
        if (block && block.id !== hoverRegionID) {
          builderHoverRegionID(block.id);
        } else if (!block && hoverRegionID !== -1) {
          builderHoverRegionID(-1);
        }
      }, mouseOutDelay);
    }
  };

  /**
   * @param {number} offsetPageX
   * @param {number} offsetPageY
   */
  handleSectionIntersect = (offsetPageX, offsetPageY) => {
    const { blocks, hoverSectionID, activeSectionID, builderHoverSectionID, hoverMenus } = this.props;

    if (hoverMenus[BLOCK_SECTION] || hoverMenus[BLOCK_REGION]) {
      clearTimeout(mouseSectionTimeout);
      return;
    }

    const block = blocks.filterIntersecting(
      offsetPageX + hoverBuffer,
      offsetPageY + hoverBuffer,
      b => b.isSection()
    );

    if (block && block.element.classList.contains(constants.CLASS_BLOCK_EDIT_EMPTY)) {
      return;
    }

    if (block && hoverSectionID === -1 && block.id !== activeSectionID) {
      clearTimeout(mouseSectionTimeout);
      builderHoverSectionID(block.id);
    } else {
      clearTimeout(mouseSectionTimeout);
      mouseSectionTimeout = setTimeout(() => {
        if (block && block.id !== hoverSectionID && block.id !== activeSectionID) {
          builderHoverSectionID(block.id);
        } else if (!block && hoverSectionID !== -1) {
          builderHoverSectionID(-1);
        }
      }, mouseOutDelay);
    }
  };

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  handleComponentIntersect = (pageX, pageY) => {
    const { blocks, builderHoverComponentID, hoverComponentID, hoverMenus } = this.props;

    if (hoverMenus[BLOCK_COMPONENT]) {
      clearTimeout(mouseRegionTimeout);
      return;
    }

    const block = blocks.filterIntersecting(
      pageX + hoverBuffer,
      pageY + hoverBuffer,
      b => b.isComponent()
    );

    if (block && hoverComponentID === -1) {
      clearTimeout(mouseComponentTimeout);
      builderHoverComponentID(block.id);
    } else {
      clearTimeout(mouseComponentTimeout);
      mouseComponentTimeout = setTimeout(() => {
        if (block && block.id !== hoverComponentID) {
          builderHoverComponentID(block.id);
        } else if (!block && hoverComponentID !== -1) {
          builderHoverComponentID(-1);
        }
      }, mouseOutDelay);
    }
  };

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  handleBGColorIntersect = (pageX, pageY) => {
    const { blocks, builderHoverBGColorID, hoverBGColorID, hoverMenus } = this.props;

    if (hoverMenus[BLOCK_BG_COLOR]) {
      clearTimeout(mouseBGColorTimeout);
      return;
    }

    const block = blocks.filterIntersecting(
      pageX + hoverBuffer,
      pageY + hoverBuffer,
      b => b.isBackgroundColor()
    );

    if (block && hoverBGColorID === -1) {
      clearTimeout(mouseBGColorTimeout);
      builderHoverBGColorID(block.id);
    } else {
      clearTimeout(mouseBGColorTimeout);
      mouseBGColorTimeout = setTimeout(() => {
        if (block && block.id !== hoverBGColorID) {
          builderHoverBGColorID(block.id);
        } else if (!block && hoverBGColorID !== -1) {
          builderHoverBGColorID(-1);
        }
      }, mouseOutDelay);
    }
  };

  /**
   * @param {number} offsetPageX
   * @param {number} offsetPageY
   */
  handleZoneIntersect = (offsetPageX, offsetPageY) => {
    const { /** @type Zone[] */ zones, draggable, dropZoneID, draggableRect, builderDropZoneID } = this.props;

    let typeZones = zones.filter(z => z.type === draggable.type);

    // Sections cannot be dropped on isCode zones. Which are reserved for AMPscript blocks.
    if (!draggable.capabilities) {
      typeZones = typeZones.filter(z => !z.isCode());
    }

    // @see https://hodgef.com/blog/find-closest-element-click-coordinates-javascript-coding-question/
    const distances = [];
    typeZones.forEach((zone) => {
      const distance = Math.hypot(zone.styles.left - offsetPageX, zone.styles.top - offsetPageY);
      distances.push(distance);
    });

    const closestIndex = distances.indexOf(Math.min(...distances));
    const closestZone  = typeZones[closestIndex];
    const closestRight = offsetPageX + draggableRect.width;

    if (
      closestZone && closestZone.id !== dropZoneID
      && closestRight >= builderStyles.widthSidebar
      && closestRight >= closestZone.styles.left
    ) {
      builderDropZoneID(closestZone.id);
    } else if (closestZone && (closestRight < builderStyles.widthSidebar || closestRight < closestZone.styles.left)) {
      builderDropZoneID(-1);
    }
  };

  /**
   * @returns {*}
   */
  render() {
    return null;
  }
}
