import { useMemo } from 'react';
import * as constants from 'builder/engine/constants';
import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';
import { loading } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import { uiAlert, uiNotice, uiConfirmLoading } from 'builder/actions/uiActions';
import { HTMLUtils } from 'builder/engine';
import { builderUpdateBlock } from './builderActions';

export const MEDIA_CROP = 'MEDIA_CROP';

/**
 * @returns {Promise<unknown>}
 */
const getImageDims = (block) => {
  return new Promise((resolve) => {
    const { element } = block;
    if (block.hasBackground()) {
      const img = HTMLUtils.getBackgroundImage(element);
      const i = new Image();
      i.onload = () => {
        const { naturalWidth, naturalHeight, offsetWidth, offsetHeight } = i;

        const ratioWidth = offsetWidth / naturalWidth;
        const ratioHeight = offsetHeight / naturalHeight;
        const densityWidth = naturalWidth / offsetWidth;
        const densityHeight = naturalHeight / offsetHeight;
        resolve({
          naturalWidth,
          naturalHeight,
          offsetWidth,
          offsetHeight,
          ratioWidth,
          ratioHeight,
          densityWidth,
          densityHeight
        });
      };
      i.src = img;
    } else {
      const { naturalWidth, naturalHeight, offsetWidth, offsetHeight } = element;
      const ratioWidth = offsetWidth / naturalWidth;
      const ratioHeight = offsetHeight / naturalHeight;
      const densityWidth = naturalWidth / offsetWidth;
      const densityHeight = naturalHeight / offsetHeight;
      resolve({
        naturalWidth,
        naturalHeight,
        offsetWidth,
        offsetHeight,
        ratioWidth,
        ratioHeight,
        densityWidth,
        densityHeight
      });
    }
  });
};

/**
 * @param {boolean} isCropping
 * @param {*} cropData
 * @returns {{cropData: *, isCropping: *, type: string}}
 */
export const mediaCrop = (isCropping, cropData) => {
  return {
    type: MEDIA_CROP,
    isCropping,
    cropData
  };
};

/**
 * @param {Block} block
 * @param {*} field
 * @param {*} resp
 * @returns {{type: string}}
 */
export const mediaHandleResizeable = (block, field, resp) => {
  return async (dispatch) => {
    const { ratioWidth, ratioHeight } = await getImageDims(block);
    const { element, rules } = block;

    let newWidth = Math.round(ratioWidth * resp.width);
    let newHeight = Math.round(ratioHeight * resp.height);
    if (rules.minWidth && newWidth < rules.minWidth) {
      newWidth = rules.minWidth;
    }
    if (rules.maxWidth && newWidth > rules.maxWidth) {
      newWidth = rules.maxWidth;
    }
    if (rules.minHeight && newHeight < rules.minHeight) {
      newHeight = rules.minHeight;
    }
    if (rules.maxHeight && newHeight > rules.maxHeight) {
      newHeight = rules.maxHeight;
    }
    if (element.hasAttribute('width')) {
      element.setAttribute('width', newWidth.toString());
    }
    if (element.hasAttribute('height')) {
      element.setAttribute('height', newHeight.toString());
    }

    dispatch(builderUpdateBlock(block.id, field, resp));
  };
};

/**
 * @param {number} blockID
 * @param {*} resp
 * @param {string} type
 * @returns {{type: string}}
 */
export const mediaImport = (blockID, resp, type) => {
  return async (dispatch, getState) => {
    const { builder } = getState();
    const { blocks } = builder;

    /** @type {Block} */
    const block = blocks.getByID(blockID);
    const {
      naturalWidth,
      naturalHeight,
      offsetWidth,
      offsetHeight,
      densityWidth,
      densityHeight
    } = await getImageDims(block);

    if (
      type !== 'image/gif'
      && type !== 'image/png'
      && type !== 'image/svg+xml'
      && !block.rules.canResize
      && (naturalWidth && (!block.rules.isAutoWidth && resp.width < naturalWidth))
    ) {
      loading(false);
      dispatch(uiNotice('', 'The new image needs to be the same size or larger than the current image.'));
      return;
    }

    if (
      block.rules.canResize && (
        (block.rules.minWidth && resp.width < (block.rules.minWidth * densityWidth))
        || (block.rules.minHeight && resp.height < (block.rules.minHeight * densityHeight))
      )
    ) {
      loading(false);
      dispatch(uiNotice('', 'The new image needs to be the same size or larger than the current image.'));
      return;
    }

    const field = (!block.isBackground() && !block.hasBackground()) ? 'image' : 'background';
    resp.block  = block;
    if (block.rules.canResize) {
      loading(false);
      dispatch(mediaHandleResizeable(block, field, resp));
      return;
    }
    if (type === 'image/gif' || type === 'image/svg+xml') {
      loading(false);
      dispatch(builderUpdateBlock(blockID, field, resp));
      return;
    }

    if (!naturalWidth || !naturalHeight) {
      resp.cropWidth  = offsetWidth;
      resp.cropHeight = offsetHeight;
    } else {
      resp.cropWidth  = naturalWidth;
      resp.cropHeight = naturalHeight;
    }

    resp.onCrop = (cropperData, isAutoWidth, isAutoHeight, cropWidth, cropHeight) => {
      const body2 = {
        src: resp.src,
        cropperData,
        isAutoWidth,
        isAutoHeight,
        cropWidth,
        cropHeight
      };

      loading(false);
      if (cropperData) {
        dispatch(uiConfirmLoading('Image is getting cropped and scaled to the correct dimensions.'));
      } else {
        dispatch(uiConfirmLoading('Image is being resized.'));
      }

      api.post(router.generate('build_images_crop', { id: resp.id }), body2)
        .then((resp2) => {
          if (resp2.error) {
            dispatch(uiAlert('', resp2.error));
            return;
          }
          dispatch(builderUpdateBlock(blockID, field, resp2));
        })
        .catch((error) => {
          console.error(error);
          dispatch(uiAlert('Error', 'Failed to upload image.'));
        })
        .finally(() => {
          dispatch(mediaCrop(false, {}));
          setTimeout(() => {
            dispatch(uiConfirmLoading(false));
          }, 500);
        });
    };

    resp.onCancel = () => {
      dispatch(mediaCrop(false, {}));
    };

    dispatch(mediaCrop(true, resp));
  };
};

/**
 * @param {number} blockID
 * @param {*} file
 * @returns {Function}
 */
export const mediaUpload = (blockID, file) => {
  return async (dispatch, getState) => {
    const { builder } = getState();
    const { id, mode, blocks, emailVersion } = builder;

    const block = blocks.getByID(blockID);
    const {
      naturalWidth,
      naturalHeight,
      offsetWidth,
      offsetHeight,
      ratioWidth,
      ratioHeight,
      densityWidth,
      densityHeight
    } = await getImageDims(block);
    const width  = naturalWidth || offsetWidth;
    const height = naturalHeight || offsetHeight;

    const body = new FormData();
    body.append('id', id);
    body.append('mode', mode);
    body.append('image', file);
    body.append('width', width);
    body.append('height', height);
    body.append('ratioWidth', ratioWidth);
    body.append('ratioHeight', ratioHeight);
    body.append('densityWidth', densityWidth);
    body.append('densityHeight', densityHeight);
    body.append('maxWidth', block.rules.maxWidth.toString());
    body.append('maxHeight', block.rules.maxHeight.toString());
    body.append('canResize', block.rules.canResize ? '1' : '0');
    body.append('isAutoHeight', block.rules.isAutoHeight ? '1' : '0');
    body.append('isAutoWidth', block.rules.isAutoWidth ? '1' : '0');
    body.append('version', emailVersion);

    loading(true);
    api.post(router.generate('build_images_upload'), body)
      .then((resp) => {
        if (resp.error) {
          loading(false);
          dispatch(uiAlert('', resp.error));
          return;
        }

        dispatch(mediaImport(blockID, resp, file.type));
      })
      .catch((error) => {
        console.error(error);
        loading(false);
        dispatch(uiAlert('', error.toString()));
      });
  };
};

/**
 * @param {File} file
 * @param {Function} cb
 * @returns {(function(*, *): Promise<void>)|*}
 */
export const mediaUploadRandom = (file, cb) => {
  return async (dispatch, getState) => {
    const { builder } = getState();
    const { id, mode, emailVersion } = builder;

    const body = new FormData();
    body.append('id', id);
    body.append('mode', mode);
    body.append('image', file);
    body.append('version', emailVersion);

    loading(true);
    api.post(router.generate('build_images_upload'), body)
      .then((resp) => {
        if (resp.error) {
          loading(false);
          dispatch(uiAlert('', resp.error));
          return;
        }

        loading(false);
        cb(resp);
        // dispatch(mediaImport(blockID, resp, file.type));
      })
      .catch((error) => {
        console.error(error);
        loading(false);
        dispatch(uiAlert('', error.toString()));
      });
  };
};

/**
 * @param {string} src
 * @param {number} id
 * @param {string} mode
 * @param {HTMLElement} element
 * @returns {(function(*, *): Promise<void>)|*}
 */
export const mediaSourceUpload = (src, id, mode, element = null) => {
  return async () => {
    const formData = new FormData();
    formData.append('src', src);
    formData.append('id', id.toString());
    formData.append('mode', mode);

    const resp = await api.post(
      router.generate('build_images_upload'),
      formData
    );
    if (element) {
      element.setAttribute('src', resp.src);
      element.setAttribute('original', resp.original);
      element.setAttribute(constants.DATA_IMG_ID, resp.id);
      element.removeAttribute(constants.DATA_HOSTED);
    }
  };
};

/**
 * @param id
 * @returns {(function(): void)|*}
 */
export const mediaDelete = (id) => {
  return () => {
    api.req('DELETE', router.generate('build_images_delete', { id }));
  };
};

export const actions = {
  upload:       mediaUpload,
  uploadRandom: mediaUploadRandom,
};

/**
 * @returns {{}}
 */
export const useMediaActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
