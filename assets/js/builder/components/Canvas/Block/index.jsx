import React, { useEffect, useMemo, useState, useRef } from 'react';
import { useSelector } from 'react-redux';
import Data from 'builder/engine/Data';
import Styler from 'builder/engine/Styler';
import { useBuilderActions } from 'builder/actions/builderActions';
import eventDispatcher from 'builder/store/eventDispatcher';
import browser from 'utils/browser';
import * as constants from 'builder/engine/constants';
import usePrevious from 'builder/hooks/usePrevious';
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

/**
 * @param {HTMLElement|EventTarget} el
 * @returns {boolean}
 */
const hasImageChild = (el) => {
  for (let i = 0; i < el.childNodes.length; i++) {
    if (el.childNodes[i].nodeType === Node.TEXT_NODE) {
      continue; // eslint-disable-line
    }

    return el.childNodes[i].tagName === 'IMG';
  }

  return false;
};

const CanvasBlock = ({ /** @type Block */ block, zIndex }) => {
  const builderActions = useBuilderActions();
  const iframe = useSelector(state => state.builder.iframe);
  const editing = useSelector(state => state.builder.editing);
  const hoverID = useSelector(state => state.builder.hoverID);
  const activeID = useSelector(state => state.builder.activeID);
  const editingID = useSelector(state => state.builder.editingID);
  const hoverRegionID = useSelector(state => state.builder.hoverRegionID);
  const draggableID = useSelector(state => state.builder.draggableID);
  const hoverSectionID = useSelector(state => state.builder.hoverSectionID);
  const activeSectionID = useSelector(state => state.builder.activeSectionID);
  const hoverComponentID = useSelector(state => state.builder.hoverComponentID);
  const draggingBlockID = useSelector(state => state.builder.draggingBlockID);
  const draggingPosition = useSelector(state => state.builder.draggingPosition);
  const expandedBlocks = useSelector(state => state.builder.expandedBlocks);
  const [hoverAnchor, setHoverAnchor] = useState(null);
  const [rectAnchor, setRectAnchor] = useState({});
  const [forceUpdate, setForceUpdate] = useState(0);
  const frameID = useRef(0);
  const styler = useRef(null);
  const clone = useRef(null);
  const elRect = useRef(null);
  const lastX = useRef(0);
  const lastY = useRef(0);
  const dragX = useRef(0);
  const dragY = useRef(0);
  const canvas = useRef(null);
  const canvasRect = useRef(null);
  const iframeRect = useRef(null);
  const prevBlock = usePrevious(block);
  const prevDraggingBlockID = usePrevious(draggingBlockID);

  /**
   *
   */
  const handleAnchorMouseLeave = () => {
    setHoverAnchor(null);
    setRectAnchor({});
  };

  /**
   * @param {MouseEvent} e
   */
  const handleAnchorMouseEnter = (e) => {
    const { currentTarget } = e;

    if (!editing || hasImageChild(currentTarget)) {
      return;
    }

    setHoverAnchor(currentTarget);
    setRectAnchor(currentTarget.getBoundingClientRect());
  };

  /**
   *
   */
  const handleKeydown = () => {
    setForceUpdate(v => v + 1);
  };

  /**
   *
   */
  const wireupLinks = () => {
    if (block.isEdit()) {
      block.element.removeEventListener('mouseleave', handleAnchorMouseLeave);
      block.element.addEventListener('mouseleave', handleAnchorMouseLeave, false);
      block.element.querySelectorAll('a').forEach((el) => {
        el.removeEventListener('mouseenter', handleAnchorMouseEnter);
        el.removeEventListener('mouseleave', handleAnchorMouseLeave);
        el.addEventListener('mouseenter', handleAnchorMouseEnter, false);
        el.addEventListener('mouseleave', handleAnchorMouseLeave, false);
      });
    }
  };

  /**
   *
   */
  const wireupEvents = () => {
    // Force the outline to resize when the inner text changes.
    block.element.addEventListener('keydown', handleKeydown);
    wireupLinks();
  };

  /**
   *
   */
  const handleEditableMenuChange = () => {
    handleAnchorMouseLeave();
    wireupLinks();
  };

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  const handleMove = (pageX, pageY) => {
    const deltaX = lastX.current - pageX;
    const deltaY = lastY.current - pageY;
    lastX.current = pageX;
    lastY.current = pageY;
    dragX.current -= deltaX;
    dragY.current -= deltaY;

    cancelAnimationFrame(frameID.current);
    frameID.current = requestAnimationFrame(() => {
      clone.current.style.transform = `translate3d(${dragX.current}px, ${dragY.current}px, 0)`;
      eventDispatcher.trigger('dragging', { dragX: dragX.current, dragY: dragY.current });
    });
  };

  /**
   * @param {MouseEvent} e
   */
  const handleDocMouseMove = (e) => {
    const offsetPageX = Math.floor(e.pageX - iframeRect.current.left);
    const offsetPageY = Math.floor(e.pageY - iframeRect.current.top);
    handleMove(offsetPageX, offsetPageY);
  };

  /**
   * @param {MouseEvent} e
   */
  const handleFrameMouseMove = (e) => {
    handleMove(e.pageX, e.pageY);
  };

  /**
   * @param {MouseEvent} e
   */
  const handleFrameMouseUp = (e) => {
    // eslint-disable-next-line no-use-before-define
    handleComponentMouseUp(e);
  };

  /**
   * @param {MouseEvent} e
   */
  const handleComponentMouseUp = (e) => {
    const origStyle = block.element.getAttribute('data-be-orig-style');
    if (origStyle) {
      block.element.setAttribute('style', origStyle);
      block.element.removeAttribute('data-be-orig-style');
    } else {
      block.element.setAttribute('style', '');
    }

    const doc = browser.iFrameDocument(iframe);
    document.removeEventListener('mousemove', handleDocMouseMove);
    doc.removeEventListener('mousemove', handleFrameMouseMove);
    doc.removeEventListener('mouseup', handleFrameMouseUp);
    document.removeEventListener('mouseup', handleComponentMouseUp);
    doc.body.style.cursor = 'default';
    document.body.style.cursor = 'default';

    lastX.current = 0;
    lastY.current = 0;
    dragX.current = 0;
    dragY.current = 0;
    if (clone.current) {
      clone.current.remove();
    }
    if (styler.current) {
      styler.current.destroy();
      styler.current = null;
    }

    if (e) {
      builderActions.dragEnd(e.pageX, e.pageY);
    } else {
      builderActions.dragEnd(draggingPosition.pageX, draggingPosition.pageY);
    }
    builderActions.draggingBlock(-1);
    eventDispatcher.trigger('drag-end');
  };

  /**
   *
   */
  const handleComponentDragging = () => {
    const { element } = block;

    builderActions.contentEditing(-1);

    const computedStyle               = window.getComputedStyle(element);
    elRect.current                    = element.getBoundingClientRect();
    clone.current                     = element.cloneNode(true);
    clone.current.style.left          = `${parseInt(elRect.current.x, 10)}px`;
    clone.current.style.top           = `${parseInt(elRect.current.y, 10)}px`;
    clone.current.style.width         = `${element.offsetWidth}px`;
    clone.current.style.height        = `${element.offsetHeight}px`;
    clone.current.style.pointerEvents = 'none';
    clone.current.style.fontSize      = computedStyle.getPropertyValue('font-size');
    clone.current.style.lineHeight    = computedStyle.getPropertyValue('line-height');
    clone.current.style.letterSpacing = computedStyle.getPropertyValue('letter-spacing');
    clone.current.style.fontWeight    = computedStyle.getPropertyValue('font-weight');
    clone.current.setAttribute('data-be-clone', 1);
    clone.current.style.position = 'absolute';
    clone.current.style.zIndex = '100000';
    clone.current.style.pointerEvents = 'none';
    Data.removeBlockID(clone.current);

    const doc       = browser.iFrameDocument(iframe);
    styler.current  = new Styler(doc);
    const className = styler.current.createFromWindowCSS('.builder-draggable-clone');
    clone.current.classList.add(className);
    doc.body.appendChild(clone.current);
    doc.body.style.cursor = 'move';
    document.body.style.cursor = 'move';

    element.setAttribute('data-be-orig-style', element.getAttribute('style'));
    element.style.pointerEvents = 'none';
    element.style.opacity    = 0;

    lastX.current = draggingPosition.pageX;
    lastY.current = draggingPosition.pageY;

    document.addEventListener('mousemove', handleDocMouseMove, false);
    doc.addEventListener('mousemove', handleFrameMouseMove, false);
    doc.addEventListener('mouseup', handleFrameMouseUp, false);
    document.addEventListener('mouseup', handleComponentMouseUp, false);
    builderActions.dragStart(block, clone.current);
    eventDispatcher.trigger('drag-start');
  };

  /**
   * @param {Block} b
   * @param {*} r
   * @returns {*}
   */
  const getStyles = (b, r = null) => {
    const rect = r || b.element.getBoundingClientRect();
    const { left, right, bottom, top, width, height } = rect;

    const padding   = 1;
    const topFix    = 1;
    const leftFix   = 1;
    const heightFix = -1;
    const widthFix  = -2;

    return {
      top:    floor(top - padding) + topFix,
      left:   floor(left - padding) + leftFix,
      right:  floor(right + padding) + leftFix,
      bottom: floor(bottom + padding) + topFix,
      width:  floor(width + (padding * 2)) + widthFix,
      height: floor(height + (padding * 2)) + heightFix,
      zIndex
    };
  };

  /**
   *
   */
  useEffect(() => {
    canvas.current     = document.querySelector('.builder-canvas');
    canvasRect.current = canvas.current.getBoundingClientRect();
    iframeRect.current = iframe.getBoundingClientRect();
    if (block) {
      wireupEvents();
    }

    return () => {
      if (block) {
        block.element.removeEventListener('mouseleave', handleAnchorMouseLeave);
        block.element.removeEventListener('keydown', handleKeydown);
        block.element.querySelectorAll('a').forEach((el) => {
          el.removeEventListener('mouseenter', handleAnchorMouseEnter);
          el.removeEventListener('mouseleave', handleAnchorMouseLeave);
        });
      }

      if (styler.current) {
        styler.current.destroy();
      }
    };
  }, [block, iframe]);

  /**
   *
   */
  useEffect(() => {
    if (!block) {
      return;
    }
    if (block && !prevBlock) {
      wireupEvents();
    } else if (block && block.links.length !== prevBlock.links.length) {
      wireupLinks();
    }
    if (draggingBlockID === block.id && prevDraggingBlockID === -1) {
      handleComponentDragging();
    } else if (draggingBlockID === -1 && prevDraggingBlockID === block.id) {
      handleComponentMouseUp(null);
    }

    iframeRect.current = iframe.getBoundingClientRect();
  }, [block, draggingBlockID, prevBlock, prevDraggingBlockID]);

  /**
   *
   */
  const hovering = useMemo(() => {
    if (!block) {
      return false;
    }
    return block.id === hoverID
      || block.id === hoverSectionID
      || block.id === hoverRegionID
      || block.id === hoverComponentID;
  }, [block, hoverID, hoverSectionID, hoverRegionID, hoverComponentID]);

  /**
   *
   */
  const active = useMemo(() => {
    if (!block) {
      return false;
    }

    return (block.id === activeID || block.id === activeSectionID || block.id === editingID);
  }, [block, activeID, activeSectionID, editingID]);

  /**
   *
   */
  const showToolbar = useMemo(() => {
    if (!block) {
      return false;
    }

    return (
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
  }, [block, draggingBlockID, hoverSectionID, hoverRegionID, hoverID, hoverComponentID, draggableID, expandedBlocks]);

  /**
   *
   */
  const showImageMenu = useMemo(() => {
    if (!block) {
      return false;
    }

    return (
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
  }, [block, activeID, hoverID, editingID]);

  /**
   *
   */
  const [toolbarPlacement, showHeadLabel] = useMemo(() => {
    if (!block) {
      return ['top', false];
    }

    // Determines the placement of the block toolbar so it doesn't go off the screen.
    let t = 'top';
    if (block.isCode()) {
      t = 'center';
    } else if (block.type === 'edit') {
      t = 'bottom';
    }
    if (block.element.getBoundingClientRect().top < 30) {
      t = 'bottom';
      if (block.isCode()) {
        t = 'center-bottom';
      }
    }

    const h = (block.isCode() && block.element.getAttribute('data-area') === 'head');

    return [t, h];
  }, [block]);

  /**
   *
   */
  const [showImagePill, showLinkPill] = useMemo(() => {
    if (!block) {
      return [false, false];
    }

    let i = false;
    let l  = false;
    if ((hovering || active) && draggableID === -1) {
      if (block.isImage() || block.isBackground()) {
        i = true;
      } else if (block.tag === 'a') {
        l = true;
      }
    }

    return [i, l];
  }, [block, hovering, active, draggableID]);

  /**
   *
   */
  const showOutline = useMemo(() => {
    if (!block) {
      return false;
    }
    return (!block.empty && draggableID === -1);
  }, [block, draggableID]);

  /**
   *
   */
  const showEditableMenu = useMemo(() => {
    if (!block) {
      return false;
    }
    return (block.id === editingID && !block.isCode());
  }, [block, editingID]);

  /**
   *
   */
  const [showLinkMenu, showAnchorMenu, showBGColorMenu] = useMemo(() => {
    if (!block) {
      return [false, false, false];
    }
    const l = (block.id === activeID && !block.rules.canText && block.rules.canLink);
    const a = (block.id === activeID && block.rules.canAnchorEdit);
    const c = (block.isBackgroundColor() && block.id === activeID);

    return [l, a, c];
  }, [block, activeID]);

  /**
   *
   */
  const showTypingProgress = useMemo(() => {
    if (!block) {
      return false;
    }
    return (block.id === editingID && block.rules.maxChars !== 0);
  }, [block, editingID]);

  /**
   *
   */
  const showCodeArrow = useMemo(() => {
    if (!block) {
      return false;
    }

    return (
      editing && block.isCode() && (block.isSection() || block.isComponent())
      && expandedBlocks.indexOf(block.id) === -1
    );
  }, [block, editing, expandedBlocks]);

  /**
   *
   */
  // const styles = useMemo(() => {
    if (!block) {
      // return {};
    }

    // return s;
  // }, [block, iframe, expandedBlocks, forceUpdate]);

  if (!block || block.element.classList.contains(constants.CLASS_BLOCK_EDIT_EMPTY)) {
    return null;
  }

  const s = getStyles(block);
  if (block.isBackground() && s.right > browser.iFrameDocument(iframe).body.offsetWidth) {
    delete s.right;
    delete s.bottom;
    s.top = 40;
  }

  const styles = s;

  return (
    <MenuContainer dimensions={styles}>
      {showOutline && (
        <Outline
          block={block}
          hover={hovering}
          active={active}
          dimensions={{ ...styles }}
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
        <EditableMenu onChange={handleEditableMenuChange} />
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
          dimensions={getStyles(null, rectAnchor)}
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
};

export default CanvasBlock;
