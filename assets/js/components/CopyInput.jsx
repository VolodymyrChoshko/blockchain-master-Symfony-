import React from 'react';
import PropTypes from 'prop-types';
import Clipboard from 'clipboard';
import Textarea from './forms/Textarea';
import Input from './forms/Input';
import Flex from './Flex';
import Button from './Button';

export default class CopyInput extends React.PureComponent {
  static propTypes = {
    id:       PropTypes.string.isRequired,
    theme:    PropTypes.string,
    value:    PropTypes.string.isRequired,
    text:     PropTypes.string,
    textarea: PropTypes.bool,
    onCopied: PropTypes.func
  };

  static defaultProps = {
    theme:    'alt',
    text:     'Copy',
    textarea: false,
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
    const { id, theme, text, value, textarea, ...props } = this.props;
    delete props.onCopied;

    return (
      <Flex {...props}>
        {textarea ? (
          <Textarea
            id={id}
            value={value}
            readOnly
          />
        ) : (
          <Input
            id={id}
            value={value}
            className="mr-2"
            readOnly
          />
        )}
        <Button
          variant={theme}
          innerRef={this.btn}
          data-clipboard-target={`#${id}`}
        >
          {text}
        </Button>
      </Flex>
    );
  }
}
