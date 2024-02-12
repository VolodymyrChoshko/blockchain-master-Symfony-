import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { builderActions } from 'builder/actions';
import { Modal, Button, ButtonGroup } from 'components';

const mapStateToProps = state => ({
  layouts: state.builder.layouts
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions)
)
export default class ImportModal extends React.PureComponent {
  static propTypes = {
    layouts:                 PropTypes.array.isRequired,
    closeModal:              PropTypes.func.isRequired,
    builderUploadNewVersion: PropTypes.func.isRequired
  };

  static defaultProps = {};

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.file = React.createRef();
  }

  /**
   * @param {Event} e
   */
  handleFileChange = (e) => {
    const { builderUploadNewVersion, closeModal } = this.props;
    const { files } = e.target;

    closeModal();
    builderUploadNewVersion(files[0]);
  };

  /**
   * @returns {*}
   */
  render() {
    const { closeModal, layouts, ...props } = this.props;
    delete props.title;

    const footer = (
      <ButtonGroup className="text-center">
        <Button variant="main" onClick={() => this.file.current.click()}>
          Continue
        </Button>
        <Button variant="alt" onClick={() => closeModal()}>
          Cancel
        </Button>
      </ButtonGroup>
    );

    return (
      <Modal title="Import New Version" footer={footer} {...props} auto>
        <input
          ref={this.file}
          type="file"
          accept=".html,.zip"
          className="form-hidden-file-input"
          onChange={this.handleFileChange}
        />
        {layouts.length === 0 ? (
          <div className="p-2 pt-1 pb-3">
            Are you sure you want to replace the current version of the template and all of its components?
          </div>
        ) : (
          <div className="p-2 pt-1 pb-3">
            Any current layouts will not be updated and will have to be rebuilt to use the new template version.
          </div>
        )}
      </Modal>
    );
  }
}
