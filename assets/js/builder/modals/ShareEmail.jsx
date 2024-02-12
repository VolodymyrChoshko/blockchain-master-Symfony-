import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import router from 'lib/router';
import { Widget, Input } from 'components/forms';
import { Modal, ButtonGroup, Flex, Button, Loading, CopyInput } from 'components';
import { builderActions, uiActions } from 'builder/actions';

const mapStateToProps = state => ({
  id:           state.builder.id,
  html:         state.builder.html,
  previewUrl:   state.builder.previewUrl,
  emailVersion: state.builder.emailVersion
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions, uiActions)
)
export default class ShareEmail extends React.PureComponent {
  static propTypes = {
    html:            PropTypes.string.isRequired,
    previewUrl:      PropTypes.string.isRequired,
    emailVersion:    PropTypes.number.isRequired,
    uiToast:         PropTypes.func.isRequired,
    builderSendTest: PropTypes.func.isRequired
  };

  static defaultProps = {};

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.state = {
      sendTest:  '',
      isSending: false
    };
  }

  /**
   *
   */
  handleSendClick = () => {
    const { builderSendTest } = this.props;
    const { sendTest } = this.state;

    this.setState({ isSending: true });
    builderSendTest(sendTest, () => {
      this.setState({ isSending: false });
    });
  };

  /**
   * @returns {*}
   */
  render() {
    const { id, html, previewUrl, emailVersion, uiToast, builderSendTest, ...props } = this.props;
    const { sendTest, isSending } = this.state;

    const footer = (
      <ButtonGroup>
        Download PDF:&nbsp;
        <a href={`${router.generate('build_export_pdf_email', { id })}?size=desktop&version=${emailVersion}`} className="mr-1">
          Desktop
        </a>
        |&nbsp;
        <a href={`${router.generate('build_export_pdf_email', { id })}?size=mobile&version=${emailVersion}`} className="mr-1">
          Mobile
        </a>
        |&nbsp;
        <a href={`${router.generate('build_export_pdf_email', { id })}?version=${emailVersion}`} className="mr-2">
          Both
        </a>

        Download Text:&nbsp;
        <a href={`${router.generate('build_export_text', { id })}?version=${emailVersion}`}>
          Text
        </a>
      </ButtonGroup>
    );

    return (
      <Modal title="Share" footer={footer} {...props} auto>
        <Widget label="Email Preview Link" htmlFor="input-preview-url">
          <CopyInput
            id="input-preview-url"
            value={previewUrl}
            onCopied={() => uiToast('Copied!')}
          />
        </Widget>
        <Widget label="Send Test Email" htmlFor="input-send-test">
          <Flex>
            <Input
              id="input-send-test"
              value={sendTest}
              placeholder="Email address"
              className="mr-2"
              onChange={e => this.setState({ sendTest: e.target.value })}
            />
            <Button variant="alt" onClick={this.handleSendClick} disabled={isSending}>
              Send
            </Button>
          </Flex>
        </Widget>
        {isSending && (
          <Loading />
        )}
      </Modal>
    );
  }
}
