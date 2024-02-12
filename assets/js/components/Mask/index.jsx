import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';
import browser from 'utils/browser';
import { builder as builderStyles } from 'lib/styles';
import { Container } from './styles';

export default class Mask extends React.PureComponent {
  static propTypes = {
    open:      PropTypes.bool,
    black:     PropTypes.bool,
    opaque:    PropTypes.bool,
    flexStart: PropTypes.bool,
    zIndex:    PropTypes.number,
    children:  PropTypes.node,
    onClick:   PropTypes.func,
    onVisible: PropTypes.func,
    onHidden:  PropTypes.func
  };

  static defaultProps = {
    open:      false,
    black:     false,
    flexStart: false,
    opaque:    false,
    zIndex:    10000,
    children:  '',
    onClick:   () => {},
    onVisible: () => {},
    onHidden:  () => {}
  };

  state = {
    visible: false,
  };

  /**
   *
   */
  componentDidMount() {
    const { open } = this.props;

    if (open) {
      this.open();
    }
  }

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { open } = this.props;
    const { open: pOpen } = prevProps;

    if (open && !pOpen) {
      this.open();
    } else if (!open && pOpen) {
      this.close();
    }
  }

  /**
   *
   */
  open = () => {
    const { onVisible } = this.props;

    setTimeout(() => {
      this.setState({ visible: true }, () => {
        setTimeout(() => {
          onVisible();
        }, builderStyles.durationMenus);
      });
    }, builderStyles.durationMenus);
  };

  /**
   *
   */
  close = () => {
    const { onHidden } = this.props;

    this.setState({
      visible: false,
    });
    onHidden();
  };

  /**
   * @param {Event} e
   */
  handleClick = (e) => {
    const { onClick } = this.props;

    if (!browser.hasParentClass(e.target, 'be-mask-child')) {
      e.preventDefault();
      onClick(e);
    }
  };

  /**
   * @returns {*}
   */
  render() {
    const { children, black, flexStart, zIndex, opaque, open } = this.props;
    const { visible } = this.state;

    return ReactDOM.createPortal(
      <Container
        zIndex={zIndex}
        black={black}
        opaque={opaque}
        mounted={open}
        visible={visible}
        flexStart={flexStart}
        onMouseDown={this.handleClick}
        onMouseMove={e => e.stopPropagation()}
      >
        {open && React.Children.map(children, (child) => {
          return React.cloneElement(child, {
            ...child.props,
            open: visible
          });
        })}
      </Container>,
      document.body
    );
  }
}
