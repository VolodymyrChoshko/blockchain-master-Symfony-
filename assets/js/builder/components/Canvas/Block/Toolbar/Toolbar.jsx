import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import eventDispatcher from 'builder/store/eventDispatcher';
import { mapDispatchToProps } from 'utils';
import { builderActions } from 'builder/actions';
import ToolbarContext from './ToolbarContext';

@connect(
  null,
  mapDispatchToProps(builderActions)
)
export default class Toolbar extends React.PureComponent {
  static propTypes = {
    placement:         PropTypes.string.isRequired,
    block:             PropTypes.object.isRequired,
    className:         PropTypes.string,
    children:          PropTypes.node,
    builderHoverMenus: PropTypes.func
  };

  // noinspection JSUnusedGlobalSymbols
  static contextType = ToolbarContext; // eslint-disable-line

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.state = {
      styles:    { display: 'none' },
      placement: null
    };

    this.toolbar = React.createRef();
    this.timeout = 0;
  }

  /**
   *
   */
  componentDidMount() {
    /* eslint-disable react/destructuring-assignment */
    this.timeout = setTimeout(() => {
      const { styles, placement } = this.context.register(
        this.toolbar.current,
        this.props.block,
        this.props.placement
      );
      /* eslint-enable react/destructuring-assignment */
      this.setState({
        styles: { ...styles, display: 'block' },
        placement
      });
    }, Math.floor(Math.random() * 100));

    eventDispatcher.on('drag-start', this.handleDragStart);
    eventDispatcher.on('drag-end', this.handleDragEnd);
    eventDispatcher.on('dragging', this.handleDragging);
  }

  /**
   *
   */
  componentWillUnmount() {
    const { block, builderHoverMenus } = this.props;

    // eslint-disable-next-line react/destructuring-assignment
    this.context.unregister(this.toolbar.current, block);
    builderHoverMenus(block.id, block.type, false);
    clearTimeout(this.timeout);

    eventDispatcher.off('drag-start', this.handleDragStart);
    eventDispatcher.off('drag-end', this.handleDragEnd);
    eventDispatcher.off('dragging', this.handleDragging);
  }

  /**
   *
   */
  handleDragStart = () => {
    this.toolbar.current.style.pointerEvents = 'none';
  };

  /**
   *
   */
  handleDragEnd = () => {
    this.toolbar.current.style.pointerEvents = 'all';
  };

  /**
   * @param {*} args
   */
  handleDragging = (args) => {
    const { dragX, dragY } = args;

    this.toolbar.current.style.transform = `translate3d(${dragX}px, ${dragY}px, 0)`;
  };

  /**
   *
   */
  handleMouseEnter = () => {
    const { block, builderHoverMenus } = this.props;

    builderHoverMenus(block.id, block.type, true);
  };

  /**
   *
   */
  handleMouseLeave = () => {
    const { block, builderHoverMenus } = this.props;

    builderHoverMenus(block.id, block.type, false);
  };

  /**
   * @returns {*}
   */
  render() {
    const { block, className, children } = this.props;
    const { styles, placement } = this.state;

    // delete styles.width;

    return (
      <div
        style={styles}
        ref={this.toolbar}
        onMouseEnter={this.handleMouseEnter}
        onMouseLeave={this.handleMouseLeave}
        className={
          classNames(className, `builder-menu-toolbar builder-menu-toolbar-b-${block.type} position-${placement}`)
        }
      >
        {children}
      </div>
    );
  }
}
