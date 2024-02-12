import React from 'react';
import PropTypes from 'prop-types';
import { builder as builderStyles } from 'lib/styles';
import MenuContext from './MenuContext';

const positions = ['top', 'bottom', 'left', 'right', 'middle'];
const startingPositions = {
  left:   ['right', 'top', 'bottom', 'middle'],
  right:  ['left', 'top', 'bottom', 'middle'],
  top:    ['bottom', 'right', 'left', 'middle'],
  bottom: ['top', 'left', 'right', 'middle'],
  middle: []
};

export default class Menu extends React.PureComponent {
  static propTypes = {
    open:          PropTypes.bool,
    position:      PropTypes.oneOf(positions),
    nextPositions: PropTypes.array,
    children:      PropTypes.func.isRequired
  };

  static defaultProps = {};

  // noinspection JSUnusedGlobalSymbols
  static contextType = MenuContext; // eslint-disable-line

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.positions = props.nextPositions || startingPositions[props.position];
    this.menuRef   = React.createRef();
    this.state     = {
      resolved: {
        style:    {},
        position: props.position
      }
    };
  }

  /**
   *
   */
  componentDidMount() {
    const { open, position } = this.props;

    this.canvas = document.querySelector('.builder-canvas');
    if (!open) {
      return;
    }

    const menuRect = this.getMenuRect();
    const resolved = this.getStyles(position, menuRect);
    if (resolved.position !== position) {
      this.setState({ resolved });
    }

    const tool          = this.menuRef.current;
    tool.style.position = 'absolute';
    tool.style.top      = `${resolved.style.top}px`;
    tool.style.left     = `${resolved.style.left}px`;
  }

  /**
   * @param {*} prevProps
   * @param {*} prevState
   */
  componentDidUpdate(prevProps, prevState) {
    const { open, position } = this.props;
    const { open: pOpen } = prevProps;
    const { resolved } = this.state;
    const { resolved: pResolved } = prevState;

    if (open && !pOpen) {
      const rect        = this.getMenuRect();
      const newResolved = this.getStyles(position, rect);
      this.setState({ resolved: newResolved });

      const tool          = this.menuRef.current;
      tool.style.position = 'absolute';
      tool.style.top      = `${newResolved.style.top}px`;
      tool.style.left     = `${newResolved.style.left}px`;
    } else if (resolved.position !== pResolved.position) {
      const tool      = this.menuRef.current;
      tool.style.top  = `${resolved.style.top}px`;
      tool.style.left = `${resolved.style.left}px`;
    }
  }

  /**
   * @returns {{top: number, left: number, width: number, height: number}}
   */
  getMenuRect = () => {
    const { current } = this.menuRef;

    const rect = current.getBoundingClientRect();

    return {
      left:   rect.left,
      top:    rect.top,
      width:  rect.width,
      height: rect.height
    };
  };

  /**
   * @param {string} currentPosition
   * @param {array} tried
   * @returns {string}
   */
  getNextPosition = (currentPosition, tried) => {
    if (tried.length === this.positions.length) {
      return '';
    }

    const index = this.positions.indexOf(currentPosition);
    let next    = index + 1;
    if (next > this.positions.length - 1) {
      next = 0;
    }

    return this.positions[next];
  };

  /**
   * @returns {*}
   */
  getStyles = (position, menuRect, tried = []) => {
    switch (position) {
      case 'left':
        return this.getStylesLeft(menuRect, tried);
      case 'right':
        return this.getStylesRight(menuRect, tried);
      case 'bottom':
        return this.getStylesBottom(menuRect, tried);
      case 'top':
        return this.getStylesTop(menuRect, tried);
      default:
        return this.getStylesMiddle(menuRect);
    }
  };

  /**
   * @param {{ left: number, top: number, width: number, height: number }} menuRect
   * @returns {{top: *, left: number}}
   */
  getStylesMiddle = (menuRect) => {
    const { dimensions } = this.context;
    const contentLeft = Math.floor(dimensions.left + ((dimensions.width / 2) - (menuRect.width / 2)));
    const contentTop  = Math.floor(dimensions.top + ((dimensions.height / 2) - (menuRect.height / 2)));

    const style = {
      top:  contentTop,
      left: contentLeft
    };

    return {
      style,
      position: 'middle'
    };
  };

  /**
   * @param {{ left: number, top: number, width: number, height: number }} menuRect
   * @param {array} tried
   * @returns {{top: *, left: number}}
   */
  getStylesTop = (menuRect, tried) => {
    const { dimensions } = this.context;
    const { menuOffset } = builderStyles;
    const contentLeft = Math.floor(dimensions.left + ((dimensions.width / 2) - (menuRect.width / 2)));

    const style = {
      top:  Math.floor((dimensions.top - menuRect.height - menuOffset)),
      left: contentLeft
    };

    tried.push('top');
    if (style.top - this.canvas.scrollTop < 0) {
      const nextPosition = this.getNextPosition('top', tried);
      if (nextPosition === '') {
        style.top  = Math.floor((dimensions.top + menuOffset));
        style.left = contentLeft;
      } else {
        return this.getStyles(nextPosition, menuRect, tried);
      }
    }

    return {
      style,
      position: 'top'
    };
  };

  /**
   * @param {{ left: number, top: number, width: number, height: number }} menuRect
   * @param {array} tried
   * @returns {{top: *, left: number}}
   */
  getStylesBottom = (menuRect, tried) => {
    const { dimensions } = this.context;
    const { menuOffset } = builderStyles;
    const contentLeft = Math.floor(dimensions.left + ((dimensions.width / 2) - (menuRect.width / 2)));

    const style = {
      top:  Math.floor((dimensions.top + dimensions.height + menuOffset)),
      left: contentLeft
    };

    tried.push('bottom');
    if (style.top > this.canvas.offsetHeight + this.canvas.scrollTop) {
      const nextPosition = this.getNextPosition('bottom', tried);
      if (nextPosition === '') {
        style.top  = Math.floor(((dimensions.top + dimensions.height) - menuRect.height - menuOffset));
        style.left = contentLeft;
      } else {
        return this.getStyles(nextPosition, menuRect, tried);
      }
    }

    return {
      style,
      position: 'bottom'
    };
  };

  /**
   * @param {{ left: number, top: number, width: number, height: number }} menuRect
   * @param {array} tried
   * @returns {{top: *, left: number}}
   */
  getStylesLeft = (menuRect, tried) => {
    const { dimensions } = this.context;
    const { menuOffset } = builderStyles;
    const contentTop = Math.floor(dimensions.top + ((dimensions.height / 2) - (menuRect.height / 2)));

    const style = {
      top:  contentTop,
      left: Math.floor(dimensions.left - menuRect.width - menuOffset)
    };

    tried.push('left');
    if (style.left < 0) {
      const nextPosition = this.getNextPosition('left', tried);
      if (nextPosition === '') {
        style.top  += menuOffset;
        style.left = Math.floor(dimensions.left + menuRect.width + menuOffset);
      } else {
        return this.getStyles(nextPosition, menuRect, tried);
      }
    }

    return {
      style,
      position: 'left'
    };
  };

  /**
   * @param {{ left: number, top: number, width: number, height: number }} menuRect
   * @param {array} tried
   * @returns {{top: *, left: number}}
   */
  getStylesRight = (menuRect, tried) => {
    const { dimensions } = this.context;
    const { menuOffset } = builderStyles;
    const overlayRect = this.canvas.getBoundingClientRect();
    const contentTop = Math.floor(dimensions.top + ((dimensions.height / 2) - (menuRect.height / 2)));

    const style = {
      top:  contentTop,
      left: Math.floor(dimensions.left + dimensions.width + menuOffset)
    };

    tried.push('right');
    if (style.left > overlayRect.width) {
      const nextPosition = this.getNextPosition('right', tried);
      if (nextPosition === '') {
        style.top += menuOffset;
        style.left = Math.floor((dimensions.left + dimensions.width) - menuRect.width - menuOffset);
      } else {
        return this.getStyles(nextPosition, menuRect, tried);
      }
    }

    return {
      style,
      position: 'right'
    };
  };

  /**
   * @returns {*}
   */
  getColumnStyles = () => {
    return {
      menu: {
        flexDirection: 'column',
        width:         builderStyles.menuWidth
      },
      button: {
        // marginBottom: '0.5rem'
      }
    };
  };

  /**
   * @returns {*}
   */
  getRowStyles = () => {
    // noinspection JSSuspiciousNameCombination
    return {
      menu: {
        flexDirection: 'row',
        height:        builderStyles.menuWidth
      },
      button: {
        // marginRight: '0.5rem'
      }
    };
  };

  /**
   * @returns {*}
   */
  render() {
    const { open, children } = this.props;
    const { resolved } = this.state;

    if (!open) {
      return null;
    }

    let styles;
    if (resolved.position === 'left' || resolved.position === 'right') {
      styles = this.getColumnStyles();
    } else {
      styles = this.getRowStyles();
    }

    return children({
      menuRef:  this.menuRef,
      position: resolved.position,
      styles
    });
  }
}
