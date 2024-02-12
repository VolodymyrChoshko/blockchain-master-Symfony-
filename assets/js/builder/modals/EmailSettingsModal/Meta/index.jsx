import { Input, Widget } from 'components/forms';
import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { DocMeta } from 'builder/engine';
import { DATA_IMG_ID, DATA_HOSTED } from 'builder/engine/constants';
import { useSelector } from 'react-redux';
import { useMediaActions } from 'builder/actions/mediaActions';
import browser from 'utils/browser';
import Button from 'components/Button';
import CropperModal from './CropperModal';

const Meta = ({ onUpdate, closeModal }) => {
  const mediaActions = useMediaActions();
  const [lang, setLang] = useState({});
  const [meta, setMeta] = useState({});
  const [crop, setCrop] = useState(null);
  const iframe = useSelector(state => state.builder.iframe);
  const fileRef = useRef(null);
  const origImageRef = useRef('');
  const imageChangedRef = useRef(false);

  /**
   *
   */
  const handleModalSubmit = () => {
    if (lang.domLang) {
      lang.domLang.setAttribute('lang', lang.lang);
    }
    if (meta.domDescription) {
      meta.domDescription.setAttribute('content', meta.description);
    }
    if (meta.domAppTitle) {
      meta.domAppTitle.setAttribute('content', meta.appTitle);
    }
    if (meta.domOGTitle) {
      meta.domOGTitle.setAttribute('content', meta.ogTitle);
    }
    if (meta.domOGDescription) {
      meta.domOGDescription.setAttribute('content', meta.ogDescription);
    }
    if (meta.domOGImage) {
      if (imageChangedRef.current && meta.ogImage !== origImageRef.current) {
        meta.domOGImage.removeAttribute(DATA_IMG_ID);
        meta.domOGImage.removeAttribute(DATA_HOSTED);
      }
      meta.domOGImage.setAttribute('content', meta.ogImage);
      if (meta.ogImageID) {
        meta.domOGImage.setAttribute(DATA_IMG_ID, meta.ogImageID);
        meta.domOGImage.setAttribute(DATA_HOSTED, '1');
      }
    }
    if (meta.domOGUrl) {
      meta.domOGUrl.setAttribute('content', meta.ogUrl);
    }

    if (onUpdate) {
      onUpdate(null);
    }
    closeModal();
  };

  /**
   *
   */
  useEffect(() => {
    if (iframe) {
      const doc = browser.iFrameDocument(iframe);
      setLang(DocMeta.getLang(doc));
      const origMeta = DocMeta.getMetaTags(doc);
      setMeta(origMeta);
      if (origMeta.ogImage) {
        origImageRef.current = origMeta.ogImage;
      }
    }
  }, [iframe]);

  /**
   *
   */
  useEffect(() => {
    document.addEventListener('emailSettingsModalSubmit.meta', handleModalSubmit, false);

    return () => document.removeEventListener('emailSettingsModalSubmit.meta', handleModalSubmit);
  }, [meta, lang]);

  /**
   * @param {Event} e
   */
  const handleChange = (e) => {
    if (e.target.name === 'lang') {
      const newLang = { ...lang };
      newLang.lang  = e.target.value;
      setLang(newLang);
    } else if (e.target.name === 'ogImage') {
      const newMeta   = { ...meta };
      newMeta.ogImage = e.target.value;
      setMeta(newMeta);
      imageChangedRef.current = true;
    } else {
      const newMeta          = { ...meta };
      newMeta[e.target.name] = e.target.value;
      setMeta(newMeta);
    }
  };

  /**
   * @param {Event} e
   */
  const handleFileChange = (e) => {
    if (e.target.files.length > 0) {
      mediaActions.uploadRandom(e.target.files[0], setCrop);
    }
  };

  /**
   * @param {*} cropData
   */
  const handleCrop = (cropData) => {
    setCrop(null);
    const newMeta = { ...meta };
    newMeta.ogImage = cropData.src;
    newMeta.ogImageID = cropData.id;
    setMeta(newMeta);
  };

  return (
    <form>
      {lang.domLang && (
        <Widget label="Language" htmlFor="email-settings-input-lang">
          <Input
            name="lang"
            value={lang.lang || ''}
            id="email-settings-input-lang"
            onChange={handleChange}
          />
        </Widget>
      )}
      {meta.domDescription && (
        <Widget label="Description" htmlFor="email-settings-input-description">
          <Input
            name="description"
            value={meta.description || ''}
            id="email-settings-input-description"
            onChange={handleChange}
          />
        </Widget>
      )}
      {meta.domAppTitle && (
        <Widget label="Apple App Title" htmlFor="email-settings-input-app-title">
          <Input
            name="appTitle"
            value={meta.appTitle || ''}
            id="email-settings-input-app-title"
            onChange={handleChange}
            maxLength={15}
          />
        </Widget>
      )}
      {meta.domOGTitle && (
        <Widget label="Open Graph Title" htmlFor="email-settings-input-og-title">
          <Input
            name="ogTitle"
            value={meta.ogTitle || ''}
            id="email-settings-input-og-title"
            onChange={handleChange}
          />
        </Widget>
      )}
      {meta.ogDescription && (
        <Widget label="Open Graph Description" htmlFor="email-settings-input-og-description">
          <Input
            name="ogDescription"
            value={meta.ogDescription || ''}
            id="email-settings-input-og-description"
            onChange={handleChange}
          />
        </Widget>
      )}
      {meta.ogUrl && (
        <Widget label="Open Graph URL" htmlFor="email-settings-input-og-url">
          <Input
            name="ogUrl"
            value={meta.ogUrl || ''}
            id="email-settings-input-og-url"
            onChange={handleChange}
          />
        </Widget>
      )}
      {meta.ogImage && (
        <Widget label="Open Graph Image" htmlFor="email-settings-input-og-image">
          <div className="d-flex">
            <Input
              name="ogImage"
              value={meta.ogImage || ''}
              id="email-settings-input-og-image"
              onChange={handleChange}
              className="mr-2"
            />
            <Button variant="main" onClick={() => fileRef.current.click()}>
              Upload
            </Button>
          </div>
          <input
            ref={fileRef}
            type="file"
            style={{ display: 'none' }}
            accept="image/*"
            onChange={handleFileChange}
          />
        </Widget>
      )}

      {crop && (
        <CropperModal
          src={crop.src}
          width={crop.width}
          id={crop.id}
          height={crop.height}
          onCancel={() => setCrop(null)}
          onSkip={() => setCrop(null)}
          onCrop={handleCrop}
        />
      )}
    </form>
  );
};

Meta.propTypes = {
  onUpdate:   PropTypes.func,
  closeModal: PropTypes.func,
};

export default Meta;
