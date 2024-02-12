import React from 'react';
import PropTypes from 'prop-types';
import { sourceActions } from 'builder/actions';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import storage from 'lib/storage';
import { Modal, Loading } from 'components';
import Step1 from './ExportEmailModal/Step1';
import Step2 from './ExportEmailModal/Step2';
import Step3 from './ExportEmailModal/Step3';

const mapStateToProps = state => ({
  id:              state.builder.id,
  token:           state.builder.token,
  emailVersion:    state.builder.emailVersion,
  templateVersion: state.builder.templateVersion,
});

@connect(mapStateToProps, mapDispatchToProps(sourceActions))
export default class ExportEmailModal extends React.PureComponent {
  static propTypes = {
    id:                   PropTypes.number.isRequired,
    token:                PropTypes.string.isRequired,
    emailVersion:         PropTypes.number.isRequired,
    sourceSources:        PropTypes.func.isRequired,
    sourceActiveSourceID: PropTypes.func.isRequired
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.state = {
      step:    0,
      html:    '',
      images:  [],
      sources: {}
    };
  }

  /**
   *
   */
  componentDidMount() {
    const { id, token, emailVersion, sourceSources, sourceActiveSourceID } = this.props;

    api.post(`${router.generate('build_email_html', { id })}?previewToken=${token}&version=${emailVersion}`)
      .then((resp) => {
        sourceSources(resp.sources);
        const source = storage.get(`export.baseSource-${id}`);
        if (source) {
          sourceActiveSourceID(source, false);
        } else if (resp.sources.length > 0) {
          sourceActiveSourceID(resp.sources[0].id, false);
        }
      });

    api.get(`${router.generate('build_export_email', { id })}?imagesRelative=1&detailedSources=1&version=${emailVersion}`)
      .then(({ sources, images, html }) => {
        this.setState({
          html,
          images,
          sources,
          step: images.length > 0 ? 1 : 3,
        });
      });
  }

  /**
   * @param {string} html
   * @param {number} step
   */
  handleStep1Finished = (html, step) => {
    this.setState({ step });
    if (html !== '') {
      this.setState({ html });
    }
  };

  /**
   * @returns {*}
   */
  render() {
    const { id, ...props } = this.props;
    const { step, sources, images, html } = this.state;

    let title = 'Export images';
    if (step === 3) {
      title = 'Export HTML';
    }

    return (
      <Modal title={title} {...props} auto>
        {{
          0: () => <Loading />,
          1: () => <Step1 sources={sources} images={images} onFinished={this.handleStep1Finished} />,
          2: () => <Step2 images={images} />,
          3: () => <Step3 sources={sources} images={images} html={html} />
        }[step]()}
      </Modal>
    );
  }
}
