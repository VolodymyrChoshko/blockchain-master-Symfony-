import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { Modal, Button } from 'components';
import { Input, Widget } from 'components/forms';
import { builderActions } from 'builder/actions';

@connect(
  null,
  mapDispatchToProps(builderActions)
)
export default class BlockSettingsModal extends React.PureComponent {
  static propTypes = {
    block:              PropTypes.object.isRequired,
    builderUpdateBlock: PropTypes.func.isRequired
  };

  static defaultProps = {};

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.state = {
      src:  props.block.element.src || '',
      alt:  props.block.element.alt || '',
      link: props.block.element.parentNode.getAttribute('href') || ''
    };
  }

  /**
   *
   */
  handleSaveClick = () => {
    const { block, builderUpdateBlock, closeModal } = this.props;
    const { src, alt, link } = this.state;

    if (!block.isBackground()) {
      block.element.src = src;
      block.element.alt = alt;
    }
    if (block.rules.isLinkable) {
      block.element.parentNode.setAttribute('href', link);
    }

    builderUpdateBlock(block.id, 'element', block.element);
    closeModal();
  };

  /**
   * @returns {*}
   */
  render() {
    const { block, closeModal, ...props } = this.props;
    const { src, alt, link } = this.state;

    return (
      <Modal title="Block Settings" {...props} auto>
        {!block.isBackground() && (
          <Widget label="Image URL" htmlFor="block-settings-image-url">
            <Input
              value={src}
              id="block-settings-image-url"
              onChange={e => this.setState({ src: e.target.value })}
            />
          </Widget>
        )}
        {!block.isBackground() && (
          <Widget label="Alternative Text" htmlFor="block-settings-image-alt">
            <Input
              value={alt}
              id="block-settings-image-alt"
              onChange={e => this.setState({ alt: e.target.value })}
            />
          </Widget>
        )}
        {block.rules.isLinkable && (
          <Widget label="Link URL" htmlFor="block-settings-link-url">
            <Input
              value={link}
              id="block-settings-link-url"
              onChange={e => this.setState({ link: e.target.value })}
            />
          </Widget>
        )}
        <Button variant="main" onClick={this.handleSaveClick}>
          Update
        </Button>
        <Button variant="alt" onClick={closeModal}>
          Cancel
        </Button>
      </Modal>
    );
  }
}
