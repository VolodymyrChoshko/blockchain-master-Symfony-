import api from 'lib/api';
import router from 'lib/router';
import React, { useEffect, useState, useRef } from 'react';
import { Button, ButtonGroup, Mask, MaskChild } from 'components';
import PropTypes from 'prop-types';
import { default as ReactCropper } from 'react-cropper'; // eslint-disable-line
import { loading } from 'utils';
import { useUIActions } from 'builder/actions/uiActions';
import browser from 'utils/browser';

const CropperModal = ({ id, src, width, height, onCancel, onSkip, onCrop }) => {
  const uiActions = useUIActions();
  const [isReady, setReady] = useState(false);
  const [isSaving, setSaving] = useState(false);
  const cropperRef = useRef(null);
  const valuesRef = useRef({});

  /**
   *
   */
  useEffect(() => {
    const i = new Image();
    i.onload = () => {
      setTimeout(() => {
        loading(false);
      }, 1000);

      setReady(true);
    };
    i.src = src;
  }, [src]);

  /**
   *
   */
  const handleCropClick = () => {
    const body = {
      src,
      cropperData:  valuesRef.current,
      isAutoWidth:  false,
      isAutoHeight: false,
      cropWidth:    1200,
      cropHeight:   630,
    };

    setSaving(true);
    loading(true);
    api.post(router.generate('build_images_crop', { id }), body)
      .then((resp) => {
        if (resp.error) {
          uiActions.alert('Error', resp.error);
          return;
        }
        onCrop(resp);
      })
      .catch((error) => {
        console.error(error);
        uiActions.alert('Error', 'Failed to upload image.');
      })
      .finally(() => {
        setSaving(false);
        loading(false);
      });
  };

  const style = { width: width + 100, height: height + 100 };
  const viewpointSize = browser.getViewpointSize();
  if (width > viewpointSize.width) {
    style.width = viewpointSize.width - 100;
  }
  if (height > viewpointSize.height) {
    style.height = viewpointSize.height - 100;
  }

  return (
    <Mask open>
      <MaskChild animation="zoomIn">
        <div className="modal modal-auto-height modal-auto-width visible d-flex flex-column">
          {isReady && (
            <ReactCropper
              viewMode={1}
              zoomable={false}
              ref={cropperRef}
              src={src}
              background={false}
              style={style}
              aspectRatio={1200 / 630}
              crop={e => valuesRef.current = e.detail}
            />
          )}
          <div className="text-center pt-4 mb-4">
            <ButtonGroup>
              <Button variant="main" onClick={handleCropClick} disabled={isSaving}>
                Crop
              </Button>
              <Button variant="main" onClick={onSkip} disabled={isSaving}>
                Skip
              </Button>
              <Button variant="alt" onClick={onCancel} disabled={isSaving}>
                Cancel
              </Button>
            </ButtonGroup>
          </div>
        </div>
      </MaskChild>
    </Mask>
  );
};

CropperModal.propTypes = {
  id:       PropTypes.number.isRequired,
  src:      PropTypes.string.isRequired,
  width:    PropTypes.number.isRequired,
  height:   PropTypes.number.isRequired,
  onCancel: PropTypes.func.isRequired,
  onSkip:   PropTypes.func.isRequired,
  onCrop:   PropTypes.func.isRequired,
};

export default CropperModal;
