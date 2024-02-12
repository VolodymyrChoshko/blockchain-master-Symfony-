import React from 'react';
import PropTypes from 'prop-types';
import Clipboard from 'clipboard';
import Button from './Button';

export default class CopyButton extends React.PureComponent {
  static propTypes = {
    variant:  PropTypes.string,
    value:    PropTypes.string.isRequired,
    children: PropTypes.node,
    onCopied: PropTypes.func
  };

  static defaultProps = {
    variant:  'main',
    children: 'Copy',
    onCopied: () => {}
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.btn = React.createRef();
    this.clipboard = null;
  }

  /**
   *
   */
  componentDidMount() {
    const { onCopied } = this.props;

    this.clipboard = new Clipboard(this.btn.current);
    this.clipboard.on('success', onCopied);
  }

  /**
   *
   */
  componentWillUnmount() {
    this.clipboard.destroy();
  }

  /**
   * @returns {*}
   */
  render() {
    const { variant, children, value, ...props } = this.props;
    delete props.onCopied;

    return (
      <Button innerRef={this.btn} variant={variant} {...props} data-clipboard-text={value}>
        {children}
      </Button>
    );
  }
}
