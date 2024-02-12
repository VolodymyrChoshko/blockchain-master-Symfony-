import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { connect, useSelector } from 'react-redux';
import { mapDispatchToProps, loading } from 'utils';
import storage from 'lib/storage';
import router from 'lib/router';
import arrays from 'utils/arrays';
import { uiActions, builderActions, sourceActions } from 'builder/actions';
import { Select, Widget } from 'components/forms';
import { hasIntegrationRule } from 'lib/integrations';
import { Button, ButtonGroup, CopyButton, Flex } from 'components';
import { Filename } from './styles';

const Step3 = ({
   id,
   html,
   images,
   sources,
   uiModal,
   uiToast,
   emailVersion,
   sourceTransferHTML,
   activeSourceID,
   activeImageSID,
   sourceActiveSourceID,
   sourceSelectFolder
  }) => {
  const [transferring, setTransferring] = useState(false);
  const [htmlSources, setHtmlSources]   = useState([]);
  const [activeSource, setActiveSource] = useState(null);
  const [zipUrl, setZipUrl]             = useState('');
  const [htmlUrl, setHtmlUrl]           = useState('');
  const [textUrl, setTextUrl]           = useState('');
  const [folder, setFolder]             = useState(storage.get(`export.destFolder-${id}`, ''));
  const [type]                          = useState(storage.get(`export.baseType-${id}`, ''));
  const [url]                           = useState(storage.get(`export.baseUrl-${id}`, ''));
  const isIntegrationsDisabled          = useSelector(state => state.integrations.isIntegrationsDisabled);

  useEffect(() => {
    // Create the values for the dropdown list of sources.
    // The source saved in storage may no longer be available. When that
    // happens use the first found from the list of available sources.
    const source    = storage.get(`export.destSource-${id}`, activeSourceID);
    let sourceFound = false;
    const values    = Object.keys(sources).map((key) => {
      if (sources[key].settings.rules.can_export_html) {
        if (sources[key].id.toString() === source.toString()) {
          sourceFound = true;
        }
        return { label: sources[key].name, value: sources[key].id };
      }
      return null;
    }).filter(v => v);
    setHtmlSources(values);

    if (!sourceFound && values.length > 0) {
      setFolder('');
      storage.set(`export.destFolder-${id}`, '');
      sourceActiveSourceID(values[0].value);
      setActiveSource(arrays.findByID(sources, parseInt(values[0].value, 10)));
    } else if (source) {
      sourceActiveSourceID(source);
      setActiveSource(arrays.findByID(sources, parseInt(source.toString(), 10)));
    }

    if (folder) {
      sourceSelectFolder(folder);
    }

    switch (type) {
      case 'source':
        setZipUrl(
          `${router.generate('build_export_zip_email', { id })}?imagesSID=${activeImageSID}&version=${emailVersion}`
        );
        setHtmlUrl(
          `${router.generate('build_export_html_email', { id })}?imagesSID=${activeImageSID}&version=${emailVersion}`
        );
        setTextUrl(
          `${router.generate('build_export_text', { id })}?imagesSID=${activeImageSID}&version=${emailVersion}`
        );
        break;
      case 'relative':
        setZipUrl(`${router.generate('build_export_zip_email', { id })}?imagesRelative=1&version=${emailVersion}`);
        setHtmlUrl(`${router.generate('build_export_html_email', { id })}?imagesRelative=1&version=${emailVersion}`);
        setTextUrl(
          `${router.generate('build_export_text', { id })}?imagesRelative=1&version=${emailVersion}`
        );
        break;
      default:
        setZipUrl(`${router.generate('build_export_zip_email', { id })}?version=${emailVersion}`);
        setHtmlUrl(`${router.generate('build_export_html_email', { id })}?version=${emailVersion}`);
        setTextUrl(
          `${router.generate('build_export_text', { id })}?version=${emailVersion}`
        );
        break;
    }
  }, [sources]);

  /**
   * @param {string} key
   * @param {string} value
   */
  const handleChange = (key, value) => {
    switch (key) {
      case 'source':
        setFolder('');
        sourceActiveSourceID(parseInt(value, 10));
        storage.set(`export.destFolder-${id}`, '');
        storage.set(`export.destSource-${id}`, value);
        setActiveSource(arrays.findByID(sources, parseInt(value.toString(), 10)));
        break;
    }
  };

  /**
   *
   */
  const handleBrowseClick = () => {
    uiModal('sourceBrowse', true, {
      selectType: 'folder',
      onChoose:   (fld, ds) => {
        // Changing the active source sets the active folder to ''. Be sure to call sourceActiveSourceID
        // before sourceSelectFolder.
        sourceActiveSourceID(parseInt(ds, 10));
        setFolder(fld);
        sourceSelectFolder(fld);
        storage.set(`export.destFolder-${id}`, fld);
        storage.set(`export.destSource-${id}`, ds);
        uiModal('sourceBrowse', false);
      }
    });
  };

  /**
   *
   */
  const handleTransferClick = () => {
    if (hasIntegrationRule(activeSource, 'export_settings_show')) {
      /**
       * @param {*} values
       */
      const onUpdate = (values) => {
        loading(true);
        setTransferring(true);
        sourceTransferHTML(
          type,
          activeSourceID,
          url,
          () => {
            loading(false);
            setTransferring(false);
          },
          values
        );
      };

      uiModal('emailSettings', true, {
        id,
        onUpdate,
        source:    activeSource,
        button:    'Finish Transfer',
        saveTitle: true,
      });
    } else {
      loading(true);
      sourceSelectFolder(folder);
      setTransferring(true);

      sourceTransferHTML(
        type,
        activeSourceID,
        url,
        () => {
          loading(false);
          setTransferring(false);
        }
      );
    }
  };

  let transferDisabled = folder === '' || transferring;
  if ((activeSource && !activeSource.settings.rules.can_list_folders) && !transferring) {
    transferDisabled = false;
  }

  return (
    <>
      {htmlSources.length > 0 && (
        <Widget>
          <label htmlFor="export-base-url-source-html">
            Transfer HTML to source:
          </label>
          <Flex>
            <Select
              id="export-base-url-source-html"
              className="d-block mr-2"
              options={htmlSources}
              value={(activeSourceID || 0).toString()}
              onChange={e => handleChange('source', e.target.value)}
            />
            <Button
              variant="alt"
              className="mr-2"
              onClick={handleBrowseClick}
              disabled={(!activeSource || !activeSource.settings.rules.can_list_folders) || isIntegrationsDisabled}
            >
              Browse
            </Button>
            <Button
              variant="main"
              style={{ minWidth: 85, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
              disabled={transferDisabled || isIntegrationsDisabled}
              onClick={handleTransferClick}
            >
              {transferring ? (
                <img src="/assets/images/ellipsis.svg" alt="Transferring" style={{ marginTop: 2 }} />
              ) : (
                'Transfer'
              )}
            </Button>
          </Flex>
          {folder && (
            <Filename className="mt-3">
              {folder}
            </Filename>
          )}
        </Widget>
      )}

      <Widget>
        <label>
          Download files:
        </label>
        <ButtonGroup>
          {images.length !== 0 && (
            <Button as="a" href={zipUrl} variant="alt">
              ZIP of images and HTML
            </Button>
          )}
          <Button as="a" href={htmlUrl} variant="alt">
            HTML Only
          </Button>
          <Button as="a" href={textUrl} variant="alt">
            Text Only
          </Button>
        </ButtonGroup>
      </Widget>

      <Widget className="mb-0">
        <label>
          Copy code:
        </label>
        <CopyButton value={html} variant="alt" onCopied={() => uiToast('Copied!')}>
          Copy HTML to clipboard
        </CopyButton>
      </Widget>
    </>
  );
};

Step3.propTypes = {
  id:                   PropTypes.number.isRequired,
  html:                 PropTypes.string.isRequired,
  emailVersion:         PropTypes.number.isRequired,
  activeSourceID:       PropTypes.number.isRequired,
  activeImageSID:       PropTypes.number.isRequired,
  images:               PropTypes.array.isRequired,
  sources:              PropTypes.array.isRequired,
  uiToast:              PropTypes.func.isRequired,
  uiModal:              PropTypes.func.isRequired,
  sourceSelectFolder:   PropTypes.func.isRequired,
  sourceTransferHTML:   PropTypes.func.isRequired,
  sourceActiveSourceID: PropTypes.func.isRequired
};

const mapStateToProps = state => ({
  id:             state.builder.id,
  emailVersion:   state.builder.emailVersion,
  activeSourceID: state.source.activeSourceID,
  activeImageSID: state.source.activeImageSID
});

export default connect(
  mapStateToProps,
  mapDispatchToProps(sourceActions, builderActions, uiActions)
)(Step3);
