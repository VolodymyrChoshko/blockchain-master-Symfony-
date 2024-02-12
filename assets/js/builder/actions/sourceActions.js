import { useMemo } from 'react';
import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';
import { loading, trimLeft } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import { uiNotice, uiConfirm, uiAlert } from 'builder/actions/uiActions';

export const SOURCE_SOURCES              = 'SOURCE_SOURCES';
export const SOURCE_ACTIVE_SOURCE_ID     = 'SOURCE_ACTIVE_SOURCE_ID';
export const SOURCE_LIST_FILES           = 'SOURCE_LIST_FILES';
export const SOURCE_DOWNLOAD             = 'SOURCE_DOWNLOAD';
export const SOURCE_IMPORT_SELECTED_PATH = 'SOURCE_IMPORT_SELECTED_PATH';
export const SOURCE_SELECT_FOLDER        = 'SOURCE_SELECT_FOLDER';
export const SOURCE_TRANSFER_COUNT       = 'SOURCE_TRANSFER_COUNT';
export const SOURCE_ACTIVE_IMAGE_SID     = 'SOURCE_ACTIVE_IMAGE_SID';

/**
 * @param {Array} sources
 * @returns {{sources: *, type: string}}
 */
export const sourceSources = (sources) => {
  return {
    type: SOURCE_SOURCES,
    sources
  };
};

/**
 * @param {number} activeSourceID
 * @param {boolean} reset
 * @returns {{type: string}}
 */
export const sourceActiveSourceID = (activeSourceID, reset = true) => {
  return {
    type: SOURCE_ACTIVE_SOURCE_ID,
    activeSourceID,
    reset
  };
};

/**
 * @param {function} dispatch
 * @param {*} state
 * @param {string} cmd
 * @param {Array} args
 * @returns {Promise}
 */
const execCommand = (dispatch, state, cmd, args = []) => {
  const { builder, source } = state;
  const { activeID, blocks, emailVersion, templateVersion } = builder;

  const block = blocks.getByID(activeID);
  if (block && block.element.tagName === 'IMG' && (block.rules.isAutoHeight || block.rules.isAutoWidth)) {
    const { naturalWidth, offsetWidth, naturalHeight, offsetHeight } = block.element;
    args.push(naturalWidth || offsetWidth);
    args.push(naturalHeight || offsetHeight);
    args.push(block.rules.isAutoHeight ? '1' : '0');
    args.push(block.rules.isAutoWidth ? '1' : '0');
  }

  const body = {
    cmd,
    args,
    iid:   source.activeSourceID,
    tid:   builder.mode === 'template' ? builder.id : builder.tid,
    eid:   builder.mode === 'template' ? 0 : builder.id,
    token: builder.token,
    emailVersion,
    templateVersion,
  };

  loading(true, false);

  return api.post(router.generate('integrations_sources'), body)
    .then((resp) => {
      if (resp.redirect) {
        if (!resp.oauth) {
          document.location = resp.redirect;
        }

        dispatch(uiConfirm('', `Please re-authenticate with ${resp.name} to continue.`, [
          {
            text:    'Okay',
            variant: 'main',
            action:  () => {
              document.location = resp.redirect;
            }
          },
          {
            text:    'Cancel',
            variant: 'alt'
          }
        ]));
      }

      return resp;
    })
    .catch((err) => {
      console.error(err);
      dispatch({
        type: SOURCE_LIST_FILES
      });
      dispatch(uiAlert('Error', err.toString()));
    })
    .finally(loading);
};

/**
 * @param {string} dir
 * @returns {Function}
 */
export const sourceListFiles = (dir) => {
  return (dispatch, getState) => {
    execCommand(dispatch, getState(), 'ls', [dir])
      .then((resp) => {
        dispatch({
          type: SOURCE_LIST_FILES,
          ...resp
        });
      });
  };
};

/**
 * @param {string} selectedPath
 * @returns {Function}
 */
export const sourceDownload = (selectedPath) => {
  return (dispatch, getState) => {
    execCommand(dispatch, getState(), 'download', [selectedPath])
      .then((resp) => {
        dispatch({
          type: SOURCE_DOWNLOAD,
          selectedPath,
          ...resp
        });
      });
  };
};

/**
 * @param {string} folderName
 * @returns {{type: string}}
 */
export const sourceMakeFolder = (folderName) => {
  return (dispatch, getState) => {
    const { source } = getState();
    const { wdir } = source;

    const path = '/' + trimLeft(`${wdir}/${folderName}`, '/'); // eslint-disable-line
    execCommand(dispatch, getState(), 'mkdir', [path])
      .then(() => {
        dispatch(sourceListFiles(wdir));
      })
      .catch((err) => {
        if (err.response && err.response.data && err.response.data.error) {
          console.error(err.response.data.error);
          dispatch(uiAlert('Error', err.response.data.error));
        }
      });
  };
};

/**
 * @returns {{type: string}}
 */
export const sourceImportSelectedPath = () => {
  return (dispatch, getState) => {
    const { source } = getState();
    const { selectedPath } = source;

    execCommand(dispatch, getState(), 'import-image', [selectedPath])
      .then((imported) => {
        dispatch({
          type: SOURCE_IMPORT_SELECTED_PATH,
          imported
        });
      });
    };
};

/**
 * @param {string} folder
 * @returns {{type: string}}
 */
export const sourceSelectFolder = (folder) => {
  return {
    type: SOURCE_SELECT_FOLDER,
    folder
  };
};

/**
 * @param {number} transferCount
 * @returns {{type: string}}
 */
export const sourceTransferCount = (transferCount) => {
  return {
    type: SOURCE_TRANSFER_COUNT,
    transferCount
  };
};

/**
 * @param {Array} images
 * @param {Function} onComplete
 * @returns {function(...[*]=)}
 */
export const sourceTransferImages = (images, onComplete) => {
  return (dispatch, getState) => {
    const { source, builder } = getState();
    const { id, emailVersion, templateVersion } = builder;

    const body = {
      images,
      sid:    source.activeSourceID,
      folder: source.folder,
      emailVersion,
      templateVersion,
    };

    dispatch({
      type:           SOURCE_ACTIVE_IMAGE_SID,
      activeImageSID: source.activeSourceID
    });
    dispatch(sourceTransferCount(0));
    api.post(router.generate('build_images_transfer', { id }), body)
      .then((uuid) => {
        let isBusy = false;
        const url = router.generate('build_images_transfer_progress', { uuid });
        const int = setInterval(() => {
          if (isBusy) {
            return;
          }
          isBusy = true;
          api.get(url)
            .then((resp) => {
              if (resp === 'Not_Found') {
                throw new Error(resp);
              }

              const count = parseInt(resp, 10);
              // eslint-disable-next-line no-restricted-globals
              if (isNaN(count)) {
                throw new Error('Progress reported NaN.');
              }
              if (count === -1) {
                clearInterval(int);
                dispatch(sourceTransferCount(images.length));
                setTimeout(onComplete, 1000);
              } else {
                dispatch(sourceTransferCount(count));
              }
            })
            .finally(() => {
              isBusy = false;
            });
        }, 1000);
      });
  };
};

/**
 * @returns {{type: string}}
 */
export const sourceTransferHTML = (baseType, sid, baseUrl, onComplete, extra = {}) => {
  return (dispatch, getState) => {
    const { builder, source } = getState();

    /**
     * @param {number} checkExisting
     * @param {string} filename
     * @returns {*}
     */
    const getTransferArgs = (checkExisting = 0, filename = '') => {
      const args = [
        source.folder,
        baseType,
        checkExisting
      ];

      if (baseType === 'source') {
        args.push(source.activeSourceID);
      } else if (baseType === 'manual') {
        args.push(baseUrl);
      } else {
        args.push(null);
      }

      args.push(filename);
      args.push(extra);

      return args;
    };

    const body = {
      cmd:             'export-html',
      iid:             source.activeSourceID,
      eid:             builder.id,
      emailVersion:    builder.emailVersion,
      templateVersion: builder.templateVersion,
      args:            getTransferArgs(0, '')
    };

    dispatch(sourceTransferCount(source.transferCount + 1));
    api.post(router.generate('integrations_sources'), body)
      .then((resp) => {
        if (resp && resp.error) {
          dispatch(uiAlert('Error', resp.error));
        } else {
          dispatch(uiNotice('success', 'Transfer complete!'));
        }
        onComplete();
      })
      .catch((error) => {
        console.error(error);
        if (error.response) {
          console.error(error.response.data);
        }
        dispatch(uiNotice('error', 'Error transferring file'));
        onComplete();
      });
  };
};

const actions = {
  sources:        sourceSources,
  activeSourceID: sourceActiveSourceID,
};

/**
 * @returns {{}}
 */
export const useSourceActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
