import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { connect, useSelector } from 'react-redux';
import { SlideDown } from 'react-slidedown';
import { loading, mapDispatchToProps } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import storage from 'lib/storage';
import { uiActions, sourceActions } from 'builder/actions';
import { Input, Radio, Select, Widget } from 'components/forms';
import { Button } from 'components';
import { Filename } from './styles';

const Step1 = ({
   id,
   sources,
   images,
   activeSourceID,
   emailVersion,
   templateVersion,
   onFinished,
   uiModal,
   sourceSelectFolder,
   sourceActiveSourceID,
   sourceTransferImages
}) => {
  const [type, setType]                 = useState(storage.get(`export.baseType-${id}`, ''));
  const [url, setUrl]                   = useState(storage.get(`export.baseUrl-${id}`, ''));
  const [folder, setFolder]             = useState(storage.get(`export.baseFolder-${id}`, ''));
  const [imageSources, setImageSources] = useState([]);
  const isIntegrationsDisabled          = useSelector(state => state.integrations.isIntegrationsDisabled);

  useEffect(() => {
    // Create the values for the dropdown list of sources.
    // The source saved in storage may no longer be available. When that
    // happens use the first found from the list of available sources.
    const source    = storage.get(`export.baseSource-${id}`, activeSourceID);
    let sourceFound = false;
    const values    = Object.keys(sources).map((key) => {
      if (sources[key].settings.rules.can_export_images) {
        if (sources[key].id.toString() === source.toString()) {
          sourceFound = true;
        }
        return { label: sources[key].name, value: sources[key].id };
      }
      return null;
    }).filter(v => v);
    setImageSources(values);

    if (!sourceFound && values.length > 0) {
      sourceActiveSourceID(values[0].value);
      sourceSelectFolder('');
      setFolder('');
      storage.set(`export.baseFolder-${id}`, '');
    } else if (source) {
      sourceActiveSourceID(source);
    }
    if (folder) {
      sourceSelectFolder(folder);
    }
  }, [sources]);

  /**
   * @param {string} key
   * @param {string} value
   */
  const handleChange = (key, value) => {
    switch (key) {
      case 'type':
        setType(value);
        storage.set(`export.baseType-${id}`, value);
        break;
      case 'source':
        setFolder('');
        sourceSelectFolder('');
        sourceActiveSourceID(parseInt(value, 10));
        storage.set(`export.baseSource-${id}`, value);
        storage.get(`export.baseFolder-${id}`, '');
        break;
      case 'url':
        setUrl(value);
        storage.set(`export.baseUrl-${id}`, value);
        break;
    }
  };

  /**
   *
   */
  const handleBrowseClick = () => {
    uiModal('sourceBrowse', true, {
      selectType: 'folder',
      onChoose:   (fld, bs) => {
        // Changing the active source sets the active folder to ''. Be sure to call sourceActiveSourceID
        // before sourceSelectFolder.
        sourceActiveSourceID(parseInt(bs, 10));
        setFolder(fld);
        sourceSelectFolder(fld);
        storage.set(`export.baseFolder-${id}`, fld);
        storage.set(`export.baseSource-${id}`, bs);
        uiModal('sourceBrowse', false);
      }
    });
  };

  /**
   *
   */
  const handleTransfer = () => {
    const base = router.generate('build_export_email_img_base', { id });
    const path = `${base}?version=${emailVersion}&templateVersion=${templateVersion}`;

    const step = (type === 'source' ? 2 : 3);
    if (type === 'manual') {
      const body = {
        imgBase: url
      };

      loading(true);
      api.post(path, body)
        .then(({ html }) => {
          loading(false);
          onFinished(html, step);
        });
    } else if (type === 'source') {
      onFinished('', step);
      sourceTransferImages(images, () => {
        const body = {
          imgSource: activeSourceID
        };
        console.error(body);
        api.post(path, body)
          .then(({ html }) => {
            onFinished(html, 3);
          });
      });
    } else {
      onFinished('', step);
    }
  };

  let disabled = type !== 'relative';
  if (type === 'source' && folder !== '') {
    disabled = false;
  } else if (type === 'manual' && url !== '') {
    disabled = false;
  }

  return (
    <>
      <div className="pb-2">
        Your email contains {images.length} uploaded images. Choose
        an option below for how you want your
        email HTML code to reference your images.
      </div>

      {(imageSources.length > 0) && (
        <Widget className="m-0" underlined>
          <Radio
            id="export-base-url-source"
            value="source"
            label="Set image URLs from source"
            onChange={e => handleChange('type', e.target.value)}
            checked={type === 'source'}
            disabled={isIntegrationsDisabled}
          />
          <SlideDown closed={type !== 'source'} className="pt-2">
            <label htmlFor="export-lightbox-input-sources" className="sr-only">
              Set destination for updated images
            </label>
            <div className="d-flex">
              <Select
                id="export-lightbox-input-sources"
                className="d-block mr-2 form-control"
                value={(activeSourceID || 0).toString()}
                onChange={e => handleChange('source', e.target.value)}
                options={imageSources}
              />
              <Button variant="alt" disabled={isIntegrationsDisabled} onClick={handleBrowseClick}>
                Browse
              </Button>
            </div>
            {folder && (
              <Filename className="mt-3">
                {folder}
              </Filename>
            )}
          </SlideDown>
        </Widget>
      )}

      <Widget className="m-0" underlined>
        <Radio
          id="export-base-url-manual"
          value="manual"
          label="Manually set base URL for images"
          onChange={e => handleChange('type', e.target.value)}
          checked={type === 'manual'}
        />
        <SlideDown closed={type !== 'manual'}>
          {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
          <label htmlFor="export-lightbox-input-base-url" className="sr-only">
            Base URL
          </label>
          <Input
            type="text"
            id="export-lightbox-input-base-url"
            className="d-block mt-2 form-control"
            placeholder="https://"
            value={url}
            onChange={e => handleChange('url', e.target.value)}
          />
          <div className="text">
            Note: images will need to be uploaded to your server
          </div>
        </SlideDown>
      </Widget>

      <Widget className="m-0" underlined>
        <Radio
          id="export-base-url-relative"
          value="relative"
          label="Use relative image URLs"
          onChange={e => handleChange('type', e.target.value)}
          checked={type === 'relative'}
        />
      </Widget>

      <div className="pt-3">
        <Button variant="main" disabled={disabled} onClick={handleTransfer}>
          Export Images
        </Button>
      </div>
    </>
  );
};

Step1.propTypes = {
  id:                   PropTypes.number.isRequired,
  images:               PropTypes.array.isRequired,
  sources:              PropTypes.array.isRequired,
  activeSourceID:       PropTypes.number.isRequired,
  emailVersion:         PropTypes.number.isRequired,
  templateVersion:      PropTypes.number.isRequired,
  onFinished:           PropTypes.func.isRequired,
  sourceActiveSourceID: PropTypes.func.isRequired,
  uiModal:              PropTypes.func.isRequired
};

const mapStateToProps = state => ({
  id:              state.builder.id,
  emailVersion:    state.builder.emailVersion,
  templateVersion: state.builder.templateVersion,
  activeSourceID:  state.source.activeSourceID
});

export default connect(
  mapStateToProps,
  mapDispatchToProps(sourceActions, uiActions)
)(Step1);
