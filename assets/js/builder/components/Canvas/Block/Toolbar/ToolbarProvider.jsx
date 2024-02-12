import React from 'react';
import PropTypes from 'prop-types';
import { builder as builderStyles } from 'lib/styles';
import ToolbarContext from './ToolbarContext';

const { floor } = Math;
const positions = {
  left:   ['right', 'top', 'bottom', 'middle'],
  right:  ['left', 'top', 'bottom', 'middle'],
  top:    ['bottom', 'right', 'left', 'middle'],
  bottom: ['top', 'left', 'right', 'middle'],
  middle: []
};

/**
 * @param rect1
 * @param rect2
 * @returns {boolean}
 */
const isOverlapping = (rect1, rect2) => {
  return !(
    rect1.top > rect2.bottom
    || rect1.right < rect2.left
    || rect1.bottom < rect2.top
    || rect1.left > rect2.right
  );
};

export default class ToolbarProvider extends React.PureComponent {
  static propTypes = {
    children: PropTypes.node
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.toolbars     = {};
    this.contextValue = {
      getToolbarStyle: this.getToolbarStyle,
      register:        this.register,
      unregister:      this.unregister
    };
  }

  /**
   * @returns {*}
   */
  getToolbarStyle = (toolbar, block, placement, retry = true) => {
    const { left, bottom, width, top } = block.rect;

    const padding = 1;
    const topFix  = 1;
    const leftFix = 1;
    // const rect    = toolbar.getBoundingClientRect();

    /**
     * @param className
     */
    const setPositionClass = (className) => {
      return;
      toolbar.classList.remove('position-top');
      toolbar.classList.remove('position-left');
      toolbar.classList.remove('position-bottom');
      toolbar.classList.remove('position-center');
      toolbar.classList.remove('position-center-bottom');
      toolbar.classList.add(`position-${className}`);
    };

    let styles;
    setPositionClass(placement);

    switch (placement) {
      case 'left':
        styles = {
          top:  floor(top - padding) + topFix,
          left: floor((left - padding) + leftFix),
          // width: builderStyles.menuBarWidth
        };
        if (styles.left < 1) {
          return this.getToolbarStyle(toolbar, block, 'top-bottom', false);
        }
        break;
      case 'bottom':
        styles = {
          top:  floor(bottom + padding) - topFix,
          left: floor(left - padding) + leftFix
        };
        styles.right  = styles.left + width;
        styles.bottom = styles.top + builderStyles.menuBarHeight;
        break;
      case 'center':
        styles = {
          top:  floor(top - builderStyles.menuBarHeight - padding) + topFix,
          left: floor(left + leftFix)
        };
        break;
      case 'center-bottom':
        styles = {
          top:  bottom + topFix,
          left: floor(left + leftFix)
        };
        break;
      case 'top-bottom':
        styles = {
          top,
          left: floor(left - padding) + leftFix
        };
        break;
      default:
        setPositionClass('top');
        styles = {
          top:  floor(top - builderStyles.menuBarHeight - padding) + topFix,
          left: floor(left - padding) + leftFix
        };
        styles.right  = styles.left + width;
        // styles.bottom = styles.top + builderStyles.menuBarHeight;
        break;
    }

    if (retry) {
      if (styles.top <= builderStyles.menuBarHeight && placement !== 'bottom' && placement !== 'center-bottom') {
        let newPlacement;
        if (placement === 'center') {
          newPlacement = 'center-bottom';
        } else {
          newPlacement = placement === 'left' ? 'top' : 'left';
        }

        return this.getToolbarStyle(
          toolbar,
          block,
          newPlacement,
          false
        );
      }
    }

    if (!styles.width) {
      styles.width = width + leftFix;
    }

    return {
      styles,
      placement
    };
  };

  /**
   * @param {HTMLElement} toolbar
   * @param {Block} block
   * @param {string} placement
   * @returns {*}
   */
  register = (toolbar, block, placement) => {
    if (this.toolbars[block.id]) {
      delete this.toolbars[block.id];
    }

    const { styles } = this.getToolbarStyle(toolbar, block, placement);

    const keys = Object.keys(this.toolbars);
    /* if (keys.length > 0) {
      for (let i = 0; i < keys.length; i++) {
        const key = keys[i];
        if (block.type === 'edit' && isOverlapping(styles, this.toolbars[key].styles)) {
          // eslint-disable-next-line prefer-destructuring
          placement = positions[placement][0];
          ({ styles } = this.getToolbarStyle(toolbar, block, placement));
        }
      }
    } */

    this.toolbars[block.id] = {
      toolbar,
      styles
    };

    return {
      styles,
      placement
    };
  };

  /**
   * @param {HTMLElement} toolbar
   * @param {Block} block
   */
  unregister = (toolbar, block) => {
    if (this.toolbars[block.id]) {
      delete this.toolbars[block.id];
    }
  };

  /**
   * @returns {*}
   */
  render() {
    const { children } = this.props;

    return (
      <ToolbarContext.Provider value={this.contextValue}>
        {children}
      </ToolbarContext.Provider>
    );
  }
}
