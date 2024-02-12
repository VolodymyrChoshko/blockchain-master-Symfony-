import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import classNames from 'classnames';
import Data from 'builder/engine/Data';
import Styler from 'builder/engine/Styler';
import eventDispatcher from 'builder/store/eventDispatcher';
import { mapDispatchToProps } from 'utils';
import { builderActions } from 'builder/actions';
import browser from 'utils/browser';
import BlockToolbar from './Toolbar/BlockToolbar';
import TypingProgressPill from './Pill/TypingProgressPill';
import MenuContainer from './Menu/MenuContainer';
import EditableMenu from './Menu/EditableMenu';
import BGColorMenu from './Menu/BGColorMenu';
import AnchorMenu from './Menu/AnchorMenu';
import ImageMenu from './Menu/ImageMenu';
import ImagePill from './Pill/ImagePill';
import LinkMenu from './Menu/LinkMenu';
import CodeArrow from './Menu/CodeArrow';
import HeadLabel from './Menu/HeadLabel';
import LinkPill from './Pill/LinkPill';
import Outline from './Outline';
import EmptyBlock from './EmptyBlock';

const requestAnimationFrame = window.requestAnimationFrame
  || window.mozRequestAnimationFrame
  || window.webkitRequestAnimationFrame
  || window.msRequestAnimationFrame;
const cancelAnimationFrame = window.cancelAnimationFrame || window.mozCancelAnimationFrame;

const { floor } = Math;

const mapStateToProps = state => ({
  iframe:           state.builder.iframe,
  editing:          state.builder.editing,
  hoverID:          state.builder.hoverID,
  activeID:         state.builder.activeID,
  editingID:        state.builder.editingID,
  hoverRegionID:    state.builder.hoverRegionID,
  draggableID:      state.builder.draggableID,
  hoverSectionID:   state.builder.hoverSectionID,
  activeSectionID:  state.builder.activeSectionID,
  hoverComponentID: state.builder.hoverComponentID,
  draggingBlockID:  state.builder.draggingBlockID,
  draggingPosition: state.builder.draggingPosition,
  gridVisible:      state.builder.gridVisible,
  expandedBlocks:   state.builder.expandedBlocks
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions)
)
export default class BlockBak extends React.PureComponent {
  static propTypes = {
    block:                 PropTypes.object,
    iframe:                PropTypes.object.isRequired,
    editing:               PropTypes.bool.isRequired,
    hoverID:               PropTypes.number.isRequired,
    activeID:              PropTypes.number.isRequired,
    editingID:             PropTypes.number.isRequired,
    hoverRegionID:         PropTypes.number.isRequired,
    draggableID:           PropTypes.number.isRequired,
    hoverSectionID:        PropTypes.number.isRequired,
    activeSectionID:       PropTypes.number.isRequired,
    hoverComponentID:      PropTypes.number.isRequired,
    draggingBlockID:       PropTypes.number.isRequired,
    draggingPosition:      PropTypes.object.isRequired,
    expandedBlocks:        PropTypes.array.isRequired,
    zIndex:                PropTypes.number.isRequired,
    gridVisible:           PropTypes.bool.isRequired,
    builderDragEnd:        PropTypes.func.isRequired,
    builderDragStart:      PropTypes.func.isRequired,
    builderContentEditing: PropTypes.func.isRequired
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.frameID = 0;
    this.styler  = null;
    this.clone   = null;
    this.rect    = null;
    this.lastX   = 0;
    this.lastY   = 0;
    this.dragX   = 0;
    this.dragY   = 0;
    this.state   = {
      hoverAnchor: null,
      rectAnchor:  {}
    };
  }

  /**
   *
   */
  componentDidMount() {
    const { block, iframe } = this.props;

    this.canvas     = document.querySelector('.builder-canvas');
    this.canvasRect = this.canvas.getBoundingClientRect();
    this.iframeRect = iframe.getBoundingClientRect();
    if (block) {
      this.wireupEvents();
    }
  }

  /**
   *
   * @param prevProps
   */
  componentDidUpdate(prevProps) {
    const { block, iframe, draggingBlockID } = this.props;

    if (block && !prevProps.block) {
      this.wireupEvents();
    } else if (block && block.links.length !== prevProps.block.links.length) {
      this.wireupLinks();
    }
    if (draggingBlockID === block.id && prevProps.draggingBlockID === -1) {
      this.handleComponentDragging();
    } else if (draggingBlockID === -1 && prevProps.draggingBlockID === block.id) {
      this.handleComponentMouseUp(null);
    }

    this.iframeRect = iframe.getBoundingClientRect();
  }

  /**
   *
   */
  componentWillUnmount() {
    const { block } = this.props;

    if (block) {
      block.element.removeEventListener('mouseleave', this.handleAnchorMouseLeave);
      block.element.removeEventListener('keydown', this.handleKeydown);
      block.element.querySelectorAll('a').forEach((el) => {
        el.removeEventListener('mouseenter', this.handleAnchorMouseEnter);
        el.removeEventListener('mouseleave', this.handleAnchorMouseLeave);
      });
    }

    if (this.styler) {
      this.styler.destroy();
    }
  }

  /**
   *
   */
  wireupEvents = () => {
    const { /** @type Block */ block } = this.props;

    // Force the outline to resize when the inner text changes.
    block.element.addEventListener('keydown', this.handleKeydown);
    this.wireupLinks();
  };

  /**
   *
   */
  wireupLinks = () => {
    const { block } = this.props;

    if (block.isEdit()) {
      block.element.removeEventListener('mouseleave', this.handleAnchorMouseLeave);
      block.element.addEventListener('mouseleave', this.handleAnchorMouseLeave, false);
      block.element.querySelectorAll('a').forEach((el) => {
        el.removeEventListener('mouseenter', this.handleAnchorMouseEnter);
        el.removeEventListener('mouseleave', this.handleAnchorMouseLeave);
        el.addEventListener('mouseenter', this.handleAnchorMouseEnter, false);
        el.addEventListener('mouseleave', this.handleAnchorMouseLeave, false);
      });
    }
  };

  /**
   * @param {HTMLElement|EventTarget} el
   * @returns {boolean}
   */
  hasImageChild = (el) => {
    for (let i = 0; i < el.childNodes.length; i++) {
      if (el.childNodes[i].nodeType === Node.TEXT_NODE) {
        continue; // eslint-disable-line
      }

      return el.childNodes[i].tagName === 'IMG';
    }

    return false;
  };

  /**
   *
   */
  handleComponentDragging = () => {
    const { /** @type Block */ block, iframe, draggingPosition, builderDragStart, builderContentEditing } = this.props;
    const { element } = block;

    builderContentEditing(-1);

    const computedStyle            = window.getComputedStyle(element);
    this.rect                      = element.getBoundingClientRect();
    this.clone                     = element.cloneNode(true);
    this.clone.style.left          = `${this.rect.x}px`;
    this.clone.style.top           = `${this.rect.y}px`;
    this.clone.style.width         = `${element.offsetWidth}px`;
    this.clone.style.height        = `${element.offsetHeight}px`;
    this.clone.style.pointerEvents = 'none';
    this.clone.style.fontSize      = computedStyle.getPropertyValue('font-size');
    this.clone.style.lineHeight    = computedStyle.getPropertyValue('line-height');
    this.clone.style.letterSpacing = computedStyle.getPropertyValue('letter-spacing');
    this.clone.style.fontWeight    = computedStyle.getPropertyValue('font-weight');
    this.clone.setAttribute('data-be-clone', 1);
    Data.removeBlockID(this.clone);

    const doc       = browser.iFrameDocument(iframe);
    this.styler     = new Styler(doc);
    const className = this.styler.createFromWindowCSS('.builder-draggable-clone');
    this.clone.classList.add(className);
    doc.body.appendChild(this.clone);
    doc.body.style.cursor = 'move';
    document.body.style.cursor = 'move';

    element.setAttribute('data-be-orig-style', element.getAttribute('style'));
    element.style.pointerEvents = 'none';
    element.style.opacity    = 0;

    this.lastX = draggingPosition.pageX;
    this.lastY = draggingPosition.pageY;

    document.addEventListener('mousemove', this.handleDocMouseMove, false);
    doc.addEventListener('mousemove', this.handleFrameMouseMove, false);
    doc.addEventListener('mouseup', this.handleFrameMouseUp, false);
    document.addEventListener('mouseup', this.handleComponentMouseUp, false);
    builderDragStart(block, this.clone);
    eventDispatcher.trigger('drag-start');
  };

  /**
   * @param {MouseEvent} e
   */
  handleComponentMouseUp = (e) => {
    const { /** @type Block */ block, iframe, draggingPosition, builderDragEnd, builderDraggingBlock } = this.props;

    const origStyle = block.element.getAttribute('data-be-orig-style');
    if (origStyle) {
      block.element.setAttribute('style', origStyle);
      block.element.removeAttribute('data-be-orig-style');
    } else {
      block.element.setAttribute('style', '');
    }

    const doc = browser.iFrameDocument(iframe);
    document.removeEventListener('mousemove', this.handleDocMouseMove);
    doc.removeEventListener('mousemove', this.handleFrameMouseMove);
    doc.removeEventListener('mouseup', this.handleFrameMouseUp);
    document.removeEventListener('mouseup', this.handleComponentMouseUp);
    doc.body.style.cursor = 'default';
    document.body.style.cursor = 'default';

    this.lastX = 0;
    this.lastY = 0;
    this.dragX = 0;
    this.dragY = 0;
    if (this.clone) {
      this.clone.remove();
    }
    if (this.styler) {
      this.styler.destroy();
      this.styler = null;
    }

    if (e) {
      builderDragEnd(e.pageX, e.pageY);
    } else {
      builderDragEnd(draggingPosition.pageX, draggingPosition.pageY);
    }
    builderDraggingBlock(-1);
    eventDispatcher.trigger('drag-end');
  };

  /**
   * @param {MouseEvent} e
   */
  handleFrameMouseMove = (e) => {
    const { pageX, pageY } = e;

    this.handleMove(pageX, pageY);
  };

  /**
   * @param {MouseEvent} e
   */
  handleDocMouseMove = (e) => {
    const offsetPageX = Math.floor(e.pageX - this.iframeRect.left);
    const offsetPageY = Math.floor(e.pageY - this.iframeRect.top);
    this.handleMove(offsetPageX, offsetPageY);
  };

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  handleMove = (pageX, pageY) => {
    const deltaX = this.lastX - pageX;
    const deltaY = this.lastY - pageY;
    this.lastX   = pageX;
    this.lastY   = pageY;
    this.dragX   -= deltaX;
    this.dragY   -= deltaY;

    cancelAnimationFrame(this.frameID);
    this.frameID = requestAnimationFrame(() => {
      this.clone.style.transform = `translate3d(${this.dragX}px, ${this.dragY}px, 0)`;
      eventDispatcher.trigger('dragging', { dragX: this.dragX, dragY: this.dragY });
    });
  };

  /**
   * @param {MouseEvent} e
   */
  handleFrameMouseUp = (e) => {
    this.handleComponentMouseUp(e);
  };

  /**
   * @param {MouseEvent} e
   */
  handleAnchorMouseEnter = (e) => {
    const { editing } = this.props;
    const { currentTarget } = e;

    if (!editing || this.hasImageChild(currentTarget)) {
      return;
    }

    this.setState({
      hoverAnchor: currentTarget,
      rectAnchor:  currentTarget.getBoundingClientRect()
    });
  };

  /**
   *
   */
  handleAnchorMouseLeave = () => {
    this.setState({
      hoverAnchor: null,
      rectAnchor:  {}
    });
  };

  /**
   *
   */
  handleEditableMenuChange = () => {
    this.handleAnchorMouseLeave();
    this.wireupLinks();
  };

  /**
   *
   */
  handleKeydown = () => {
    this.forceUpdate();
  };

  /**
   * @param {Block} b
   * @param {*} r
   * @returns {*}
   */
  getStyles = (b, r = null) => {
    const rect = r || b.element.getBoundingClientRect();
    const { left, right, bottom, top, width, height } = rect;
    const { zIndex } = this.props;

    const padding   = 1;
    const topFix    = 1;
    const leftFix   = 1;
    const heightFix = -1;
    const widthFix  = -2;

    return {
      top:    floor(top - padding) + topFix,
      left:   floor(left - padding) + leftFix + 350,
      right:  floor(right + padding),
      bottom: floor(bottom + padding),
      width:  floor(width + (padding * 2)) + widthFix,
      height: floor(height + (padding * 2)) + heightFix,
      zIndex
    };
  };

  /**
   * @returns {*}
   */
  render() {
    const {
            /** @type {Block} */ block,
            gridVisible,
            editing,
            iframe,
            hoverID,
            activeID,
            editingID,
            hoverRegionID,
            draggableID,
            draggingBlockID,
            hoverSectionID,
            activeSectionID,
            hoverComponentID,
            expandedBlocks
          } = this.props;
    const { hoverAnchor, rectAnchor } = this.state;

    if (!block) {
      return null;
    }

    const hovering   = block.id === hoverID || block.id === hoverSectionID || block.id === hoverRegionID
      || block.id === hoverComponentID;
    const active     = (block.id === activeID || block.id === activeSectionID || block.id === editingID)
      && !block.isImage();
    const styles     = this.getStyles(block);
    const dimensions = Object.assign({}, styles);
    const classes    = classNames(`builder-block-b-${block.type}`, {
      'active':                 active,
      'builder-block-grid':     gridVisible,
      'builder-block-editable': block.rules.isEditable && block.children.length === 0
    });

    // Determines the placement of the block toolbar so it doesn't go off the screen.
    let toolbarPlacement = 'top';
    if (block.isCode()) {
      toolbarPlacement = 'center';
    } else if (block.type === 'edit') {
      toolbarPlacement = 'bottom';
    }
    if (block.element.getBoundingClientRect().top < 30) {
      toolbarPlacement = 'bottom';
      if (block.isCode()) {
        toolbarPlacement = 'center-bottom';
      }
    }
    if (block.isBackground() && styles.right > browser.iFrameDocument(iframe).body.offsetWidth) {
      delete styles.right;
      delete styles.bottom;
      styles.top = 40;
    }

    // Shows the block toolbar when...
    const showToolbar = (
      // The block is being dragged.
      (draggingBlockID === block.id)
      // Or...
      || (
        // The block is hovered over.
        (block.id === hoverSectionID
          || block.id === hoverRegionID
          || block.id === hoverID
          || block.id === hoverComponentID
        )
        // And it's not an empty block.
        && !block.empty
        // And a block isn't be dragged in from the sidebar.
        && draggableID === -1
        // And it's not an expanded code block.
        && !(block.isCode() && expandedBlocks.indexOf(block.id) === -1)
      )
    );

    // Shows the image menu when...
    const showImageMenu = (
      // The block is an image or has a background image.
      (block.isImage() || block.hasBackground())
      // And the block is active or a background image being hovered over.
      && (
        block.id === activeID
        || ((block.isBackground() || block.hasBackground()) && block.id === hoverID)
      )
      // And no text block is currently being edited.
      && editingID === -1
    );

    let showImagePill = false;
    let showLinkPill  = false;
    if ((hovering || active) && draggableID === -1) {
      if (block.isImage()) {
        showImagePill = true;
      } else if (block.tag === 'a') {
        showLinkPill = true;
      }
    }
    const showOutline        = (!block.empty && draggableID === -1);
    const showEditableMenu   = (block.id === editingID && !block.isCode());
    const showLinkMenu       = (block.id === activeID && !block.rules.canText && block.rules.canLink);
    const showAnchorMenu     = (block.id === activeID && block.rules.canAnchorEdit);
    const showTypingProgress = (block.id === editingID && block.rules.maxChars !== 0);
    const showHeadLabel      = (block.isCode() && block.element.getAttribute('data-area') === 'head');
    const showBGColorMenu    = (block.isBackgroundColor() && block.id === activeID);
    const showCodeArrow      = (
      editing && block.isCode() && (block.isSection() || block.isComponent())
      && expandedBlocks.indexOf(block.id) === -1
    );

    return (
      <MenuContainer dimensions={styles}>
        {showOutline && (
          <Outline
            hover={hovering}
            className={classes}
            dimensions={dimensions}
            visible={active || hovering || block.id === editingID}
          />
        )}
        {block.empty && (
          <EmptyBlock
            block={block}
            editing={editing}
            draggableID={draggableID}
          />
        )}
        {showToolbar && (
          <BlockToolbar
            block={block}
            key={`block-menu-${block.id}`}
            placement={toolbarPlacement}
            active={block.id === activeID}
          />
        )}
        {showEditableMenu && (
          <EditableMenu onChange={this.handleEditableMenuChange} />
        )}
        {showImageMenu && (
          <ImageMenu block={block} position="top" />
        )}
        {showLinkMenu && (
          <LinkMenu block={block} position="top" />
        )}
        {showImagePill && (
          <ImagePill block={block} dimensions={styles} />
        )}
        {showLinkPill && (
          <LinkPill block={block} dimensions={styles} />
        )}
        {(hoverAnchor && !showLinkPill) && (
          <LinkPill
            href={hoverAnchor.getAttribute('href')}
            dimensions={this.getStyles(null, rectAnchor)}
          />
        )}
        {showTypingProgress && (
          <TypingProgressPill block={block} dimensions={styles} />
        )}
        {showCodeArrow && (
          <CodeArrow dimensions={styles} />
        )}
        {showHeadLabel && (
          <HeadLabel dimensions={styles} />
        )}
        {showBGColorMenu && (
          <BGColorMenu block={block} position="top" />
        )}
        {showAnchorMenu && (
          <AnchorMenu block={block} position="top" />
        )}
      </MenuContainer>
    );
  }
}
