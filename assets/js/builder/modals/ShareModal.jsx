import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import router from 'lib/router';
import { Widget } from 'components/forms';
import { Modal, Link, ButtonGroup, CopyInput, CopyButton } from 'components';
import { uiActions } from 'builder/actions';

const mapStateToProps = state => ({
  id:              state.builder.id,
  origHtml:        state.builder.origHtml,
  previewUrl:      state.builder.previewUrl,
  templateVersion: state.builder.templateVersion
});

@connect(
  mapStateToProps,
  mapDispatchToProps({ uiToast: uiActions.uiToast })
)
export default class ShareModal extends React.PureComponent {
  static propTypes = {
    origHtml:        PropTypes.string.isRequired,
    previewUrl:      PropTypes.string.isRequired,
    templateVersion: PropTypes.number.isRequired,
    uiToast:         PropTypes.func.isRequired
  };

  static defaultProps = {};

  /**
   * @returns {*}
   */
  render() {
    const { id, origHtml, previewUrl, templateVersion, uiToast, ...props } = this.props;

    const footer = (
      <ButtonGroup>
        <CopyButton value={origHtml} onCopied={() => uiToast('Copied!')}>
          Copy HTML
        </CopyButton>
        <Link href={`${router.generate('build_template_download', { id })}?version=${templateVersion}`} variant="main">
          Download
        </Link>
      </ButtonGroup>
    );

    return (
      <Modal title="Share & Export" footer={footer} {...props} auto>
        <Widget label="Template Preview Link" htmlFor="input-preview-url" className="m-0 p-2 pt-1 pb-3">
          <CopyInput
            id="input-preview-url"
            value={previewUrl}
            onCopied={() => uiToast('Copied!')}
          />
        </Widget>
      </Modal>
    );
  }
}
