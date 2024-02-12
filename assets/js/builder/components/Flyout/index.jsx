import React, { useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import browser from 'utils/browser';
import { builderActions } from 'builder/actions';

const overlap   = 5;
const childSize = 5;

// @see https://css-tricks.com/dropdown-menus-with-more-forgiving-mouse-movement-paths/

const Flyout = ({ block, children, innerRef, builderHoverMenus }) => {
  let el;
  if (innerRef) {
    el = innerRef;
  } else {
    el = useRef(null);
  }
  let timeout = 0;

  const handleMouseEnter = () => {
    builderHoverMenus(block.id, block.type, true);
  };

  useEffect(() => {
    const flyoutBase = browser.createDocElement('div', {
      className:    `builder-canvas-flyout builder-canvas-flyout-b-${block.type}`,
      onMouseEnter: handleMouseEnter
    });
    const flyoutRight = browser.createDocElement('div', {
      className:    `builder-canvas-flyout builder-canvas-flyout-b-${block.type}`,
      onMouseEnter: handleMouseEnter
    });
    const flyoutLeft = browser.createDocElement('div', {
      className:    `builder-canvas-flyout builder-canvas-flyout-b-${block.type}`,
      onMouseEnter: handleMouseEnter
    });
    flyoutBase.appendChild(flyoutRight);
    flyoutBase.appendChild(flyoutLeft);

    timeout = setTimeout(() => {
      /** @type HTMLElement */
      const { current } = el;
      if (!current) {
        return;
      }

      const rect    = current.getBoundingClientRect();
      const toolbar = current.closest('.builder-menu-toolbar');
      const isTop   = toolbar.classList.contains('position-top');

      browser.setStyles(flyoutBase, {
        top:    rect.top - overlap,
        left:   rect.left - overlap,
        width:  rect.width + (overlap * 2),
        height: rect.height + (overlap * 2)
      });

      if (isTop) {
        browser.setStyles(flyoutRight, {
          left:   rect.right + overlap,
          top:    rect.bottom - childSize + overlap,
          height: childSize,
          width:  childSize
        });
        browser.setStyles(flyoutLeft, {
          left:   rect.left - childSize - overlap,
          top:    rect.bottom - childSize + overlap,
          height: childSize,
          width:  childSize
        });
      } else {
        browser.setStyles(flyoutRight, {
          left:   rect.right + overlap,
          top:    rect.top - overlap,
          height: childSize,
          width:  childSize
        });
        browser.setStyles(flyoutLeft, {
          left:   rect.left - childSize - overlap,
          top:    rect.top - overlap,
          height: childSize,
          width:  childSize
        });
      }

      current.parentNode.appendChild(flyoutBase);
    }, 100);

    return () => {
      clearTimeout(timeout);
      flyoutBase.remove();
    };
  }, [timeout]);

  return React.cloneElement(React.Children.only(children), {
    innerRef: el
  });
};

Flyout.propTypes = {
  block:             PropTypes.object.isRequired,
  children:          PropTypes.node.isRequired,
  innerRef:          PropTypes.object,
  builderHoverMenus: PropTypes.func
};

export default connect(null, mapDispatchToProps(builderActions))(Flyout);
