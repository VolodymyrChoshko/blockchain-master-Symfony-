import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { Modal, Button, ButtonGroup } from 'components';
import { Textarea } from 'components/forms';
import { builderActions } from 'builder/actions';

const mapStateToProps = state => ({

});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions)
)
export default class HtmlModal extends React.PureComponent {
  static propTypes = {
    block:               PropTypes.object.isRequired,
    builderUpdateBlock:  PropTypes.func.isRequired,
    builderUpdateBlocks: PropTypes.func.isRequired
  };

  static defaultProps = {};

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.state = {
      html: props.block.element.outerHTML
    };
  }

  /**
   *
   */
  handleUpdateClick = () => {
    const { block, builderUpdateBlock, builderUpdateBlocks, closeModal } = this.props;
    const { html } = this.state;

    block.element.outerHTML = html;
    builderUpdateBlock(block.id, 'element', block.element);
    builderUpdateBlocks();
    closeModal();
  };

  /**
   * @returns {*}
   */
  render() {
    const { closeModal, ...props } = this.props;
    const { html } = this.state;

    const footer = (
      <ButtonGroup className="text-center">
        <Button variant="main" onClick={this.handleUpdateClick}>
          Update
        </Button>
        <Button variant="alt" onClick={() => closeModal()}>
          Cancel
        </Button>
      </ButtonGroup>
    );

    return (
      <Modal title="HTML" className="modal-html-editor" footer={footer} {...props} lg auto>
        <Textarea
          id="html-modal-html"
          value={html}
          onChange={e => this.setState({ html: e.target.value })}
        />
      </Modal>
    );
  }
}
