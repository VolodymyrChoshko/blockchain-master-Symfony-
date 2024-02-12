import { mediaSourceUpload } from 'builder/actions/mediaActions';
import { useMemo } from 'react';
import { BlockEngine, HTMLUtils, CodeBlocks, Data } from 'builder/engine';
import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';
import { loading } from 'utils';
import browser, { iFrameDocument } from 'utils/browser';
import api from 'lib/api';
import router from 'lib/router';
import ContentEditable from 'builder/engine/ContentEditable';
import UpgradeEngine from 'builder/engine/UpgradeEngine';
import eventDispatcher from 'builder/store/eventDispatcher';
import { actions as historyActions } from 'builder/actions/historyActions';
import { actions as templateActions } from 'dashboard/actions/templateActions';
import { actions as commentActions } from 'builder/actions/commentActions';
import { sourceSources } from './sourceActions';
import { actions as checklistActions } from './checklistActions';
import { socketHTML, socketSubRoom, socketUpdateState } from './socketActions';
import {
  uiModal,
  uiSidebarSection,
  uiAlert,
  uiConfirm,
  uiNotice,
  uiPrompt,
  uiConfirmLoading,
  uiToggleUpgrading,
} from './uiActions';
import { userInitialState } from './userActions';

export const BUILDER_INITIAL_STATE       = 'BUILDER_INITIAL_STATE';
export const BUILDER_CLEAR_STATE         = 'BUILDER_CLEAR_STATE';
export const BUILDER_SET_STATE           = 'BUILDER_SET_STATE';
export const BUILDER_OPEN                = 'BUILDER_OPEN';
export const BUILDER_OPENED              = 'BUILDER_OPENED';
export const BUILDER_UNDO                = 'BUILDER_UNDO';
export const BUILDER_REDO                = 'BUILDER_REDO';
export const BUILDER_CLEAR_UNDO          = 'BUILDER_CLEAR_UNDO';
export const BUILDER_DROP                = 'BUILDER_DROP';
export const BUILDER_MOVE                = 'BUILDER_MOVE';
export const BUILDER_EDITING             = 'BUILDER_EDITING';
export const BUILDER_SAVE                = 'BUILDER_SAVE';
export const BUILDER_SET_EMAIL_VERSION   = 'BUILDER_SET_EMAIL_VERSION';
export const BUILDER_UPDATE_BLOCK        = 'BUILDER_UPDATE_BLOCK';
export const BUILDER_CANVAS_MOUNTED      = 'BUILDER_CANVAS_MOUNTED';
export const BUILDER_IFRAME_MOUNTED      = 'BUILDER_IFRAME_MOUNTED';
export const BUILDER_IFRAME_REFRESH      = 'BUILDER_IFRAME_REFRESH';
export const BUILDER_HOVER_MENUS         = 'BUILDER_HOVER_MENUS';
export const BUILDER_HOVER_ID            = 'BUILDER_HOVER_ID';
export const BUILDER_HOVER_SECTION_ID    = 'BUILDER_HOVER_SECTION_ID';
export const BUILDER_HOVER_REGION_ID     = 'BUILDER_HOVER_REGION_ID';
export const BUILDER_HOVER_COMPONENT_ID  = 'BUILDER_HOVER_COMPONENT_ID';
export const BUILDER_HOVER_BG_COLOR_ID   = 'BUILDER_HOVER_BG_COLOR_ID';
export const BUILDER_DROP_ZONE_ID        = 'BUILDER_DROP_ZONE_ID';
export const BUILDER_ACTIVE_ID           = 'BUILDER_ACTIVE_ID';
export const BUILDER_UPDATE_BLOCKS       = 'BUILDER_UPDATE_BLOCKS';
export const BUILDER_REMOVE_BLOCK        = 'BUILDER_REMOVE_BLOCK';
export const BUILDER_CLONE_BLOCK         = 'BUILDER_CLONE_BLOCK';
export const BUILDER_TOGGLE_GRID         = 'BUILDER_TOGGLE_GRID';
export const BUILDER_DRAG_START          = 'BUILDER_DRAG_START';
export const BUILDER_DRAG_END            = 'BUILDER_DRAG_END';
export const BUILDER_SCROLL_TOP          = 'BUILDER_SCROLL_TOP';
export const BUILDER_DESELECT_ALL        = 'BUILDER_DESELECT_ALL';
export const BUILDER_CONTENT_EDITING     = 'BUILDER_CONTENT_EDITING';
export const BUILDER_LAYOUT_LOAD         = 'BUILDER_LOAD_LAYOUT';
export const BUILDER_LAYOUT_SAVE         = 'BUILDER_LAYOUT_SAVE';
export const BUILDER_LAYOUT_SETTINGS     = 'BUILDER_LAYOUT_SETTINGS';
export const BUILDER_LAYOUT_DELETE       = 'BUILDER_LAYOUT_DELETE';
export const BUILDER_LAYOUT_UPGRADE      = 'BUILDER_LAYOUT_UPGRADE';
export const BUILDER_EMAIL_UPGRADE       = 'BUILDER_EMAIL_UPGRADE';
export const BUILDER_VARIATION           = 'BUILDER_VARIATION';
export const BUILDER_REFRESH_RECTS       = 'BUILDER_REFRESH_RECTS';
export const BUILDER_ACTIVE_SECTION_ID   = 'BUILDER_ACTIVE_SECTION_ID';
export const BUILDER_IMAGE_DIM           = 'BUILDER_IMAGE_DIM';
export const BUILDER_CANVAS_HEIGHT       = 'BUILDER_CANVAS_HEIGHT';
export const BUILDER_ROLLBACK_EDITING    = 'BUILDER_ROLLBACK_EDITING';
export const BUILDER_CANCEL_EDITING      = 'BUILDER_CANCEL_EDITING';
export const BUILDER_EXPAND_BLOCK        = 'BUILDER_EXPAND_BLOCK';
export const BUILDER_SET_COLOR_SCHEME    = 'BUILDER_SET_COLOR_SCHEME';
export const BUILDER_UPDATE_HTML         = 'BUILDER_UPDATE_HTML';
export const BUILDER_SET_HTML            = 'BUILDER_SET_HTML';
export const BUILDER_SET_MODE            = 'BUILDER_SET_MODE';
export const BUILDER_UPDATE_LAYOUT       = 'BUILDER_UPDATE_LAYOUT';
export const BUILDER_SET_GRANT           = 'BUILDER_SET_GRANT';
export const BUILDER_UPDATE_ROOM         = 'BUILDER_UPDATE_ROOM';
export const BUILDER_UPDATE_LIBRARIES    = 'BUILDER_UPDATE_LIBRARIES';
export const BUILDER_UPDATE_LAYOUTS      = 'BUILDER_UPDATE_LAYOUTS';
export const BUILDER_DRAGGING_BLOCK      = 'BUILDER_DRAGGING_BLOCK';
export const BUILDER_UPLOADING_STATUS    = 'BUILDER_UPLOADING_STATUS';
export const BUILDER_UPDATE_HISTORY      = 'BUILDER_UPDATE_HISTORY';
export const BUILDER_PUSH_HTML           = 'BUILDER_PUSH_HTML';
export const BUILDER_TOGGLE_VARIANT_VISIBILITY = 'BUILDER_TOGGLE_VARIANT_VISIBILITY';
export const BUILDER_SAVE_TEMPLATE = 'BUILDER_SAVE_TEMPLATE';
export const BUILDER_SET_FIRST_RULES_EDIT = 'BUILDER_SET_FIRST_RULES_EDIT';
export const BUILDER_ADD_UPGRADING = 'BUILDER_ADD_UPGRADING';
export const BUILDER_REMOVE_UPGRADING = 'BUILDER_REMOVE_UPGRADING';
export const BUILDER_UPGRADE_PERCENT = 'BUILDER_UPGRADE_PERCENT';
export const BUILDER_PIN_GROUP_SAVE  = 'BUILDER_PIN_GROUP_SAVE';
export const BUILDER_PIN_GROUP_UPDATE = 'BUILDER_PIN_GROUP_UPDATE';
export const BUILDER_PIN_GROUP_DELETE = 'BUILDER_PIN_GROUP_DELETE';
export const BUILDER_SCROLL_TO_BLOCK = 'BUILDER_SCROLL_TO_BLOCK';

/**
 * @param {string} key
 * @param {*} value
 * @returns {{type: string, value: *, key: *}}
 */
export const builderSetState = (key, value) => {
  return {
    type: BUILDER_SET_STATE,
    key,
    value
  };
};

/**
 * @param {array} room
 * @returns {{type: string, room: array}}
 */
export const builderUpdateRoom = (room) => {
  return {
    type: BUILDER_UPDATE_ROOM,
    room
  };
};

window.undoBusy = false;

/**
 * @returns {{type: string}}
 */
export const builderUndo = () => {
  return (dispatch, getState) => {
    dispatch({
      type: BUILDER_UNDO
    });
    const { iframe, html } = getState().builder;
    browser.iFrameSrc(iframe, html);
  };
};

/**
 * @returns {{type: string}}
 */
export const builderRedo = () => {
  return (dispatch, getState) => {
    dispatch({
      type: BUILDER_REDO
    });
    const { iframe, html } = getState().builder;
    browser.iFrameSrc(iframe, html);
  };
};

/**
 * @param {*} canvas
 * @returns {{canvas: *, type: string}}
 */
export const builderCanvasMounted = (canvas) => {
  return {
    type: BUILDER_CANVAS_MOUNTED,
    canvas
  };
};

/**
 * @param {*} iframe
 * @returns {{type: string, iframe: *}}
 */
export const builderIFrameMounted = (iframe) => {
  return (dispatch, getState) => {
    const { rules } = getState();

    dispatch({
      type:           BUILDER_IFRAME_MOUNTED,
      isRulesEditing: rules.isEditing,
      iframe
    });
  };
};

/**
 * @param {string} html
 * @returns {{type: string}}
 */
export const builderIFrameRefresh = (html = '') => {
  return {
    type: BUILDER_IFRAME_REFRESH,
    html,
  };
};

/**
 * @param {string} html
 * @returns {{type: string}}
 */
export const builderUpdateHTML = (html) => {
  return {
    type: BUILDER_UPDATE_HTML,
    html
  };
};

/**
 * @param {string} html
 * @param {number} blockID
 * @param {string} origHtml
 * @returns {{type: string}}
 */
export const builderSetHTML = (html, blockID = -1, origHtml = '') => {
  return {
    type: BUILDER_SET_HTML,
    blockID,
    origHtml,
    html
  };
};

/**
 * @param grant
 * @returns {{type: string, grant}}
 */
export const builderSetGrant = (grant) => {
  return {
    type: BUILDER_SET_GRANT,
    grant
  };
};

/**
 * @param {boolean} editing
 * @returns {{type: string, editing: *}}
 */
export const builderEditing = (editing) => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { room } = builder;

    if (editing) {
      let isEditing = false;
      room.forEach((u) => {
        if (u.state === 'editing') {
          isEditing = true;
        }
      });
      if (isEditing) {
        dispatch(uiAlert('Session', 'Email is being edited by another team member.'));
      } else {
        dispatch(socketUpdateState('editing'));
        dispatch({
          type: BUILDER_EDITING,
          editing
        });
      }
    }
  };
};

/**
 * @returns {{type: string}}
 */
export const builderCancelEditing = () => {
  return (dispatch) => {
    dispatch(socketUpdateState('watching'));
    // dispatch(unlockEmail());
    dispatch({
      type: BUILDER_CANCEL_EDITING
    });
  };
};

/**
 * @returns {{type: string}}
 */
export const builderRefreshRects = () => {
  return {
    type: BUILDER_REFRESH_RECTS
  };
};

/**
 * @returns {{type: string}}
 */
export const builderUpdateBlocks = () => {
  return (dispatch, getState) => {
    const { rules } = getState();
    if (!rules.isEditing) {
      dispatch({
        type: BUILDER_UPDATE_BLOCKS
      });
    }
  };
};

/**
 * @param {number} blockID
 * @param {string} field
 * @param {*} value
 * @param {string} alt
 * @returns {{blockID: *, field: *, type: string, value: *}}
 */
export const builderUpdateBlock = (blockID, field, value, alt = '') => {
  return (dispatch) => {
    dispatch({
      type: BUILDER_UPDATE_BLOCK,
      blockID,
      field,
      value,
      alt
    });
    // dispatch(socketUpdateBlock(blockID, field, value, alt));
  };
};

/**
 * @param {string} src
 * @param {number} width
 * @param {number} height
 * @returns {{type: string}}
 */
export const builderImageDim = (src, width, height) => {
  return {
    type: BUILDER_IMAGE_DIM,
    height,
    width,
    src
  };
};

/**
 * @param {*} layout
 * @returns {{type: string}}
 */
export const builderUpdateLayout = (layout) => {
  return {
    type: BUILDER_UPDATE_LAYOUT,
    layout
  };
};

/**
 * @returns {function(*=, *): void}
 */
export const builderRunLayoutChecks = () => {
  return (dispatch, getState) => {
    const interval = setInterval(() => {
      let upgrading  = false;
      const promises = [];
      getState().builder.layouts.forEach((layout) => {
        if (layout.upgrading) {
          upgrading = true;
          promises.push(api.get(router.generate('build_layout', { id: layout.id })));
        }
      });
      if (!upgrading) {
        clearInterval(interval);
      } else {
        Promise.all(promises)
          .then((responses) => {
            responses.forEach((l) => {
              dispatch(builderUpdateLayout(l));
            });
          });
      }
    }, 5000);
  };
};

/**
 * @returns {(function(*, *): void)|*}
 */
export const builderCheckPendingLibraries = () => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { id, mode } = builder;

    if (builder.libraries.length === 0) {
      return;
    }

    /**
     * @param {array} libraries
     * @returns {number}
     */
    const countDone = (libraries) => {
      let done = 0;
      libraries.forEach((lib) => {
        if (lib.screenshotDesktop !== '' && lib.screenshotMobile !== '') {
          done += 1;
        }
      });

      return done;
    };

    const done = countDone(builder.libraries);
    if (done === builder.libraries.length) {
      return;
    }

    const checkInterval = setInterval(() => {
      api.get(`${router.generate('build_libraries')}?id=${id}&mode=${mode}`)
        .then((libraries) => {
          if (countDone(libraries) === libraries.length) {
            clearInterval(checkInterval);
            dispatch({
              type: BUILDER_UPDATE_LIBRARIES,
              libraries
            });
          }
        });
    }, 2500);
  };
};

/**
 * @param is
 * @returns {{initialState, type: string}}
 */
export const builderInitialState = is => ({
  type:         BUILDER_INITIAL_STATE,
  initialState: is,
});

/**
 * @returns {{type: string}}
 */
export const builderClearState = () => ({
  type: BUILDER_CLEAR_STATE,
});

/**
 * @param emailVersion
 * @param previewUrl
 * @returns {{emailVersion, type: string}}
 */
export const builderSetEmailVersion = (emailVersion, previewUrl = '') => ({
  type: BUILDER_SET_EMAIL_VERSION,
  previewUrl,
  emailVersion,
});

/**
 * @param id
 * @returns {function(*): Promise<unknown>}
 */
export const builderUpgradeStatus = (id) => {
  return (dispatch) => {
    return new Promise((resolve) => {
      const upgradeRoute = router.generate('build_template_upgrade_status', { id });
      const interval = setInterval(async () => {
        const isRunning = await api.get(upgradeRoute);
        if (!isRunning) {
          dispatch(uiToggleUpgrading(false));
          clearInterval(interval);
          resolve();
        }
      }, 1000);
    });
  };
};

/**
 * @param id
 * @returns {(function(*): Promise<void>)|*}
 */
export const builderUpgrade = (id) => {
  return (dispatch) => {
    return new Promise((resolve) => {
      dispatch(uiToggleUpgrading(true));

      api.post(router.generate('build_template_upgrade', { id }))
        .then(async (tid) => {
          if (tid) {
            await dispatch(builderUpgradeStatus(tid));
          }
          resolve();
        });
    });
  };
};

/**
 * @param isLayout
 * @param opts
 * @returns {Function}
 */
export const builderOpen = (isLayout = false, opts = {}) => {
  return async (dispatch, getState) => {
    const { builder, user } = getState();
    const { iframe } = builder;

    // eslint-disable-next-line prefer-const
    let { mode, id, isCurrentVersion, emailVersion, templateVersion, previewToken } = opts;
    if (builder.id) {
      ({ id, mode } = builder);
    }
    if (mode) {
      dispatch({
        type: BUILDER_SET_MODE,
        mode,
      });
    }

    let route;
    if (templateVersion) {
      route = `${router.generate('build_template_html', { id })}?previewToken=${previewToken}&version=${templateVersion}`;
    } else if (mode.indexOf('template') === 0) {
      route = `${router.generate('build_template_html', { id })}?previewToken=${previewToken}`;
    } else if (emailVersion) {
      route = `${router.generate('build_email_html', { id })}?previewToken=${previewToken}&version=${emailVersion}`;
    } else {
      route = `${router.generate('build_email_html', { id })}?previewToken=${previewToken}`;
    }

    /* if (mode.indexOf('preview') === -1) {
      const tid          = mode.indexOf('template') === 0 ? id : opts.tid;
      const needsUpgrade = await api.get(router.generate('build_template_upgrade_check', { id: tid }));
      if (needsUpgrade === true) {
        await dispatch(builderUpgrade(tid));
      }
    } */

    api.post(route)
      .then((resp) => {
        dispatch(builderInitialState(resp.initialState.builder));
        dispatch(userInitialState(resp.initialState.user));
        dispatch(sourceSources(resp.sources));
        dispatch(checklistActions.setSettings(resp.checklistSettings, resp.checklistItems));
        dispatch(historyActions.set(resp.history));
        dispatch(commentActions.set(resp.comments));
        dispatch(templateActions.setBillingPlan(resp.initialState.billingPlan));
        // eslint-disable-next-line prefer-destructuring
        mode = resp.initialState.builder.mode || mode;

        delete resp.sources;
        const { html } = resp;
        delete resp.html;
        delete resp.history;

        if (isLayout) {
          resp.editing = true;
        }

        dispatch({
          emailVersion,
          ...resp,
          type:            BUILDER_OPEN,
          groups:          Array.isArray(resp.groups) ? {} : resp.groups,
          linkStyles:      resp.linkStyles,
          mode:            (mode === 'email_preview' && user.email !== '') ? 'email' : mode,
          templateVersion: resp.templateVersion || templateVersion,
          isCurrentVersion
        });

        // Update the loading animation on layout thumbnails when they're
        // in the process of being updated.
        if (resp.layouts && resp.layouts.length > 0) {
          dispatch(builderRunLayoutChecks());
        }

        // Check if thumbnails are still generating otherwise re-fetch libs.
        if (resp.libraries.length > 0) {
          dispatch(builderCheckPendingLibraries());
        }

        BlockEngine.setUpdateCallback((e) => {
          if (e) {
            const { target } = e;
            if (target.tagName === 'IMG') {
              const img = target;
              dispatch(builderImageDim(img.src, img.naturalWidth, img.naturalHeight));
            }
          }

          if (getState().builder.iframe) {
            dispatch(builderUpdateBlocks());
          }
        });

        BlockEngine.openHTML(html, resp.groups, resp.linkStyles)
          .then(({ html: h, hasColorScheme }) => {
            if (iframe) {
              browser.iFrameSrc(iframe, h);
            }
            dispatch({
              type: BUILDER_OPEN,
              html: h,
              hasColorScheme
            });
            dispatch({
              type: BUILDER_OPENED
            });

            dispatch(socketSubRoom());
            // eslint-disable-next-line no-use-before-define
            dispatch(builderWatchShortKeys());
          });

        BlockEngine.setMediaUploadCallback((src, element) => {
          if (mode.indexOf('email') === 0) {
            dispatch(mediaSourceUpload(src, id, mode, element));
          }
        });
      })
      .catch((error) => {
        console.error(error);
        if (error.toString().indexOf('getBoundingClientRect') === -1) {
          dispatch(uiAlert('', error.toString()));
        }
      });
  };
};

/**
 * @param {number} editingID
 * @returns {{editingID: *, type: string}}
 */
export const builderContentEditing = (editingID) => {
  return (dispatch, getState) => {
    const { builder } = getState();

    if (editingID !== -1 && builder.editingID !== -1) {
      dispatch(builderContentEditing(-1));
    }

    const { iframe } = builder;
    const doc = browser.iFrameDocument(iframe);
    const placeCaretAtEnd = (el) => {
      el.focus();
      if (typeof iframe.contentWindow.getSelection !== 'undefined'
        && typeof doc.createRange !== 'undefined') {
        const range = doc.createRange();
        range.selectNodeContents(el);
        range.collapse(false);
        const sel = iframe.contentWindow.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
      } else if (typeof doc.body.createTextRange !== 'undefined') {
        const textRange = doc.body.createTextRange();
        textRange.moveToElementText(el);
        textRange.collapse(false);
        textRange.select();
      }
    };

    dispatch({
      type:          BUILDER_CONTENT_EDITING,
      editingID,
      cloneCallback: (clonedElement) => {
        const id = Data.getBlockID(clonedElement);
        dispatch(builderActiveID(id)); // eslint-disable-line no-use-before-define
        dispatch(builderContentEditing(id));
        setTimeout(() => {
          placeCaretAtEnd(clonedElement);
        }, 250);
      }
    });
    if (editingID === -1) {
      dispatch(builderUpdateBlock(builder.editingID, '', null));
      dispatch(builderUpdateBlocks());

      // const block = getState().builder.blocks.getByID(lastEditingID);
      // dispatch(socketUpdateHTML(lastEditingID, block.element.innerHTML));
    }
  };
};

/**
 * @returns {{type: string}}
 */
export const builderRollbackEditing = () => {
  return {
    type: BUILDER_ROLLBACK_EDITING
  };
};

/**
 * @returns {{type: string}}
 */
export const builderSave = (cb = () => {}) => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { id, tid, emailVersion, blocks, iframe, editingID } = builder;

    if (iframe) {
      if (editingID !== -1) {
        if (window.getSelection) {
          window.getSelection().removeAllRanges();
        } else if (document.selection) {
          document.selection.empty();
        }
        dispatch(builderContentEditing(-1));
      }

      // Collapse any open code blocks.
      blocks.forEach((block) => {
        if (block.isCode() && (block.isSection() || block.isComponent())) {
          CodeBlocks.collapse(block.element);
        }
      });

      loading(true);
      BlockEngine.getHTML()
        .then(({ html, title }) => {
          api.post(router.generate('build_save', { id }), {
            html,
            title,
            version: emailVersion || 0
          })
            .then((resp) => {
              if (resp.history) {
                dispatch(historyActions.set(resp.history));
              }
              if (resp.version && resp.previewUrl) {
                dispatch(builderSetEmailVersion(resp.version, resp.previewUrl));
                window.history.replaceState(
                  null,
                  resp.title,
                  router.generate('build_email', { tid, id })
                );
              }

              dispatch({
                type:    BUILDER_SAVE,
                version: resp.version,
              });
              dispatch(builderSetHTML(resp.html));
              dispatch(socketUpdateState('watching'));
              dispatch({ type: BUILDER_CLEAR_UNDO });
              // dispatch(unlockEmail());
              dispatch(socketHTML(resp.html));
              if (typeof cb === 'function') {
                cb();
              } else {
                loading(false);
              }
            })
            .catch((error) => {
              console.error(error);
              dispatch(uiAlert('', error.toString()));
            })
            .finally(() => {
              if (typeof cb !== 'function') {
                loading(false);
              }
            });
        });
    } else if (typeof cb === 'function') {
      cb();
    }
  };
};

/**
 * @param {function|null} cb
 * @returns {(function(*, *): Promise<void>)|*}
 */
const builderSaveTemplate = (cb = null) => {
  return async (dispatch, getState) => {
    const { builder } = getState();
    const { id, layouts, libraries, templateVersion } = builder;

    loading(true);
    BlockEngine.getHTML()
      .then(({ html, title }) => {
        api.post(router.generate('build_template_save', { id }), {
          html:    html.replace(/font-family: &quot;Basis Grotesque&quot;/g, "font-family: 'Basis Grotesque'"),
          title,
          version: templateVersion || 0
        })
          .then((resp) => {
            if (resp.history) {
              dispatch(historyActions.set(resp.history));
            }

            BlockEngine.openHTML(resp.html, resp.groups, resp.linkStyles)
              .then(async ({ html: h, hasColorScheme }) => {
                dispatch({
                  type: BUILDER_SAVE_TEMPLATE,
                  ...resp,
                  html: h,
                  hasColorScheme
                });
                dispatch(socketHTML(resp.html));

                const checkLayouts = () => {
                  return new Promise((resolve) => {
                    if (layouts.length > 0) {
                      const ids = builder.layouts.map((l) => {
                        if (builder.templateVersion > l.version && l.isUpgradable) {
                          return l.id;
                        }
                        return null;
                      }).filter(v => v);
                      if (ids.length === 0) {
                        resolve();
                        return;
                      }

                      dispatch(uiConfirm(
                        '',
                        'This template has layouts saved. Would you like to update them to include these updates?',
                        [
                          {
                            text:    'Okay',
                            variant: 'main',
                            action:  async () => {
                              // eslint-disable-next-line no-use-before-define
                              await dispatch(builderLayoutUpgradeAll(resolve));
                            }
                          },
                          {
                            text:    'Later',
                            variant: 'alt',
                            action:  resolve,
                          }
                        ]
                      ));
                    } else {
                      resolve();
                    }
                  });
                };

                const checkPins = () => {
                  return new Promise((resolve) => {
                    if (libraries.length > 0) {
                      const ids = builder.libraries.map((l) => {
                        if (!l.mobile && builder.templateVersion > l.tmp_version && l.isUpgradable) {
                          return l.id;
                        }
                        return null;
                      }).filter(v => v);
                      if (ids.length === 0) {
                        resolve();
                        return;
                      }

                      dispatch(uiConfirm(
                        '',
                        'This template has pins saved. Would you like to update them to include these updates?',
                        [
                          {
                            text:    'Okay',
                            variant: 'main',
                            action:  async () => {
                              // eslint-disable-next-line no-use-before-define
                              await dispatch(builderPinsUpgradeAll(resolve));
                            }
                          },
                          {
                            text:    'Later',
                            variant: 'alt',
                            action:  resolve,
                          }
                        ]
                      ));
                    } else {
                      resolve();
                    }
                  });
                };

                if (typeof cb === 'function') {
                  cb();
                }

                await checkLayouts();
                await checkPins();
              });
          })
          .catch((error) => {
            console.error(error);
            dispatch(uiAlert('', error.toString()));
          })
          .finally(() => {
            loading(false);
          });
      });
  };
};

/**
 * @param uuid
 * @param cb
 * @returns {(function(*): Promise<void>)|*}
 */
const checkUploadProgress = (uuid, cb) => {
  return async (dispatch) => {
    let isBusy = false;
    let isDone = false;
    const url = router.generate('build_templates_uploading_status', { uuid });
    const it = setInterval(() => {
      try {
        if (isBusy) {
          return;
        }
        isBusy = true;

        api.get(url)
          .then((status) => {
            if (!isDone) {
              dispatch({
                type:            BUILDER_UPLOADING_STATUS,
                uploadingStatus: {
                  ...status,
                  uuid,
                }
              });
            }

            if (status.percent === 100 && !isDone) {
              isDone = true;
              clearInterval(it);
              cb(status);
              setTimeout(() => {
                dispatch({
                  type:            BUILDER_UPLOADING_STATUS,
                  uploadingStatus: null
                });
              }, 500);
            }
          });
      } catch (error) {
        clearInterval(it);
        cb(0);
      } finally {
        isBusy = false;
      }
    }, 1000);
  };
};

/**
 * @param formData
 * @param cb
 * @returns {(function(*): Promise<void>)|*}
 */
export const builderUploadTemplate = (formData, cb) => {
  return async (dispatch) => {
    dispatch({
      type:            BUILDER_UPLOADING_STATUS,
      uploadingStatus: {
        uuid:    '',
        message: 'Uploading template.',
        percent: 0
      }
    });

    api.post(router.generate('build_template_upload'), formData)
      .then((uuid) => {
        if (uuid.error) {
          dispatch(uiAlert('Import error', uuid.message));
          return;
        }

        dispatch(checkUploadProgress(uuid, (status) => {
          if (status.id === 0) {
            dispatch(uiAlert('Error', 'Error uploading template.'));
          } else {
            cb(status.id);
          }
        }));
      })
      .catch((error) => {
        console.error(error);
        dispatch({
          type:            BUILDER_UPLOADING_STATUS,
          uploadingStatus: null
        });
        dispatch(uiAlert('Error', 'Error uploading template.'));
      });
  };
};

/**
 * @param {File} file
 * @returns {(function(*, *): void)|*}
 */
export const builderUploadNewVersion = (file) => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { id, isCurrentVersion, layouts, libraries } = builder;

    dispatch({
      type:            BUILDER_UPLOADING_STATUS,
      uploadingStatus: {
        uuid:    '',
        message: 'Uploading template.',
        percent: 0
      }
    });

    const formData = new FormData();
    formData.set('template', file);
    api.post(router.generate('build_template_upload_new_version', { id }), formData)
      .then((uuid) => {
        if (uuid.error) {
          dispatch(uiAlert('Import error', uuid.message));
          return;
        }

        const refreshIt = () => {
          if (isCurrentVersion) {
            document.location.reload();
          } else {
            document.location = router.generate('build_template_version', {
              id:      status.id,
              version: status.meta.version
            });
          }
        };

        const checkLayouts = () => {
          return new Promise((resolve) => {
            if (layouts.length > 0) {
              const ids = builder.layouts.map((l) => {
                if (builder.templateVersion > l.version && l.isUpgradable) {
                  return l.id;
                }
                return null;
              }).filter(v => v);
              if (ids.length === 0) {
                resolve();
                return;
              }

              dispatch(uiConfirm(
                '',
                'This template has layouts saved. Would you like to update them to include these updates?',
                [
                  {
                    text:    'Okay',
                    variant: 'main',
                    action:  async () => {
                      // eslint-disable-next-line no-use-before-define
                      await dispatch(builderLayoutUpgradeAll(resolve));
                    }
                  },
                  {
                    text:    'Later',
                    variant: 'alt',
                    action:  resolve,
                  }
                ]
              ));
            } else {
              resolve();
            }
          });
        };

        const checkPins = () => {
          return new Promise((resolve) => {
            if (libraries.length > 0) {
              const ids = builder.libraries.map((l) => {
                if (!l.mobile && builder.templateVersion > l.tmp_version && l.isUpgradable) {
                  return l.id;
                }
                return null;
              }).filter(v => v);
              if (ids.length === 0) {
                resolve();
                return;
              }

              dispatch(uiConfirm(
                '',
                'This template has pins saved. Would you like to update them to include these updates?',
                [
                  {
                    text:    'Okay',
                    variant: 'main',
                    action:  async () => {
                      // eslint-disable-next-line no-use-before-define
                      await dispatch(builderPinsUpgradeAll(resolve));
                    }
                  },
                  {
                    text:    'Later',
                    variant: 'alt',
                    action:  resolve,
                  }
                ]
              ));
            } else {
              resolve();
            }
          });
        };

        dispatch(checkUploadProgress(uuid, async (status) => {
          if (status.id === 0) {
            dispatch(uiAlert('Error', 'Error uploading template.'));
          } else {
            await checkLayouts();
            await checkPins();
            refreshIt();
          }
        }));
      });
  };
};

/**
 * @param {string} title
 * @returns {Function}
 */
export const builderLayoutSave = (title) => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { id, tid, mode } = builder;

    dispatch({
      type:            BUILDER_UPLOADING_STATUS,
      uploadingStatus: {
        uuid:    '',
        message: 'Uploading template.',
        percent: 0
      }
    });

    BlockEngine.getHTML()
      .then(({ html }) => {
        const saveID = mode === 'template' ? id : tid;

        const formData = new FormData();
        formData.append('html', html);
        formData.append('name', title);
        api.post(router.generate('build_templates_layout_upload', { id: saveID }), formData)
          .then((uuid) => {
            if (uuid.error) {
              dispatch(uiAlert('Import error', uuid.message));
              return;
            }

            dispatch(checkUploadProgress(uuid, (status) => {
              if (status.id === 0) {
                dispatch(uiAlert('Error', 'Error uploading template.'));
              } else {
                dispatch({
                  type:              BUILDER_LAYOUT_SAVE,
                  title,
                  id:                status.id,
                  screenshotDesktop: status.meta.screenshotDesktop,
                  screenshotMobile:  status.meta.screenshotMobile
                });
                dispatch(uiNotice('success', 'Layout saved.'));
              }
            }));
          })
          .finally(() => {
            dispatch(uiConfirmLoading(false));
          });
      });
  };
};

/**
 * @param {number} lid
 * @returns {Function}
 */
export const builderLayoutLoad = (lid) => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { id: eid, emailVersion, isChanged } = builder;

    const loadLayout = () => {
      loading(true);
      api.post(router.generate('build_layout_load', { id: eid, lid }), { emailVersion })
        .then((resp) => {
          dispatch(builderSetHTML(resp.html));
          dispatch(socketHTML(resp.html));
          dispatch(uiNotice('success', 'Layout loaded.'));
        })
        .catch((error) => {
          console.error(error);
          dispatch(uiAlert('', error.toString()));
        })
        .finally(loading);
    };

    if (isChanged) {
      dispatch(uiConfirm('', 'Are you sure you want to replace your current content? Changes cannot be undone.', [
        {
          text:    'Yes',
          variant: 'danger',
          action:  () => {
            loadLayout();
          }
        },
        {
          text:    'No',
          variant: 'alt'
        }
      ]));
    } else {
      loadLayout();
    }
  };
};

/**
 * @param {number} id
 * @param {string} title
 * @returns {Function}
 */
export const builderLayoutSettings = (id, title) => {
  return (dispatch) => {
    api.post(router.generate('build_layout_settings', { id }), { title })
      .then(() => {
        dispatch({
          type: BUILDER_LAYOUT_SETTINGS,
          title,
          id
        });
      });
  };
};

/**
 * @param {number} id
 * @returns {Function}
 */
export const builderLayoutDelete = (id) => {
  return (dispatch) => {
    loading(true);
    api.req('DELETE', router.generate('build_layout_delete', { id }))
      .then(() => {
        dispatch({
          type: BUILDER_LAYOUT_DELETE,
          id
        });
        dispatch(uiNotice('success', 'Layout deleted.'));
      })
      .finally(() => {
        loading(false);
      });
  };
};

/**
 * @param item
 * @returns {{item, type: string}}
 */
export const builderAddUpgrading = (item) => {
  return {
    type: BUILDER_ADD_UPGRADING,
    item,
  };
};

/**
 * @param item
 * @returns {{item, type: string}}
 */
export const builderRemoveUpgrading = (item) => {
  return {
    type: BUILDER_REMOVE_UPGRADING,
    item,
  };
};

/**
 * @param percent
 * @returns {{type: string, percent}}
 */
export const builderUpgradePercent = (percent) => {
  return {
    type: BUILDER_UPGRADE_PERCENT,
    percent,
  };
};

/**
 * @param {function} cb
 * @returns {(function(*, *): Promise<void>)|*}
 */
export const builderLayoutUpgradeAll = (cb = null) => {
  return async (dispatch, getState) => {
    const { builder } = getState();

    dispatch(builderUpgradePercent(0));
    dispatch(builderAddUpgrading('layouts'));

    const ids = builder.layouts.map((l) => {
      if (builder.templateVersion > l.version && l.isUpgradable) {
        return l.id;
      }
      return null;
    }).filter(v => v);

    await UpgradeEngine.upgradeLayouts(ids, builder.tid || builder.id, builder.origHtml, async () => {
      const { layouts } = await api.post(router.generate('build_template_html', { id: builder.tid || builder.id }));
      dispatch({
        type: BUILDER_UPDATE_LAYOUTS,
        layouts,
      });
      dispatch(builderRemoveUpgrading('layouts'));
      dispatch(uiNotice('success', 'Upgrade complete!'));
      if (cb) {
        cb();
      }
    }, (p) => {
      dispatch(builderUpgradePercent(p));
    });
  };
};

/**
 * @returns {(function(*): void)|*}
 */
export const builderEmailUpgrade = () => {
  return async (dispatch, getState) => {
    const { builder } = getState();

    dispatch(builderUpgradePercent(0));
    dispatch(builderAddUpgrading('email'));
    await UpgradeEngine.upgradeEmail(builder.id, builder.tid, builder.origHtml, builder.templateVersion, async () => {
      const { html, emailVersion, previewUrl, history } = await api.post(router.generate('build_email_html', { id: builder.id }));
      dispatch(historyActions.set(history));

      dispatch(builderSetEmailVersion(emailVersion, previewUrl));
      dispatch({
        type:    BUILDER_SAVE,
        version: emailVersion,
      });
      dispatch(builderSetHTML(html));
      dispatch(builderRemoveUpgrading('email'));
      dispatch(socketHTML(html));
      dispatch(uiNotice('success', 'Upgrade complete!'));
    }, (p) => {
      dispatch(builderUpgradePercent(p));
    });
  };
};

/**
 * @param {function} cb
 * @returns {(function(*, *): Promise<void>)|*}
 */
export const builderPinsUpgradeAll = (cb = null) => {
  return async (dispatch, getState) => {
    const { builder } = getState();

    dispatch(builderUpgradePercent(0));
    dispatch(builderAddUpgrading('pins'));

    const ids = builder.libraries.map((l) => {
      if (!l.mobile && builder.templateVersion > l.tmp_version && l.isUpgradable) {
        return l.id;
      }
      return null;
    }).filter(v => v);

    await UpgradeEngine.upgradePins(ids, builder.tid || builder.id, builder.origHtml, () => {
      api.get(`${router.generate('build_libraries')}?id=${builder.id}&mode=${builder.mode}`)
        .then((libraries) => {
          dispatch({
            type: BUILDER_UPDATE_LIBRARIES,
            libraries
          });
          dispatch(builderRemoveUpgrading('pins'));
          dispatch(uiNotice('success', 'Upgrade complete!'));
          if (cb) {
            cb();
          }
        });
    }, (p) => {
      dispatch(builderUpgradePercent(p));
    });
  };
};

/**
 * @returns {(function(*, *): Promise<void>)|*}
 */
export const builderReloadHistory = () => {
  return async (dispatch, getState) => {
    const { builder } = getState();
    const { id, tid, mode, isCurrentVersion } = builder;

    if (mode.indexOf('email') === 0) {
      const resp = await api.get(router.generate('build_email_history', { id }));
      dispatch(historyActions.set(resp.history));
      if (!isCurrentVersion) {
        dispatch(builderSetEmailVersion(resp.version, resp.previewUrl));
        window.history.replaceState(
          null,
          resp.title,
          router.generate('build_email_version', { tid, id, version: resp.version })
        );
      } else {
        dispatch(builderSetEmailVersion(resp.version));
      }
    }
  };
};

/**
 * @param {Function} onComplete
 * @returns {Function}
 */
export const builderEmailSettings = (onComplete = null) => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { id, iframe, editing } = builder;

    /**
     * @param {*} values
     */
    const onUpdate = (values) => {
      if (values) {
        dispatch(builderSetState('emailLinkParams', values.emailLinkParams || {}));
        dispatch(builderSetState('epaEnabled', values.epaEnabled || false));
        dispatch(builderSetState('emaAliasEnabled', values.emaAliasEnabled || false));

        delete values.title;
        delete values.preview;
        delete values.emailLinkParams;
        delete values.epaEnabled;
        delete values.emaAliasEnabled;

        // Pass integration settings back to the integrations.
        const keys = Object.keys(values);
        if (keys.length > 0) {
          keys.forEach((key) => {
            const [sid] = key.split('.');
            const body  = { [key]: values[key] };
            api.post(router.generate('integrations_settings_update', { sid }), body);
          });
        }
      }

      dispatch(builderUpdateHTML(browser.iFrameSrc(iframe)));
      if (!editing) {
        dispatch(builderSave());
      }
      if (onComplete) {
        onComplete();
      }
    };

    dispatch(uiModal('emailSettings', true, { id, onUpdate }));
  };
};

/**
 * @param {number} blockID
 * @param {string} menuName
 * @param {boolean} adding
 * @returns {{type: string}}
 */
export const builderHoverMenus = (blockID, menuName, adding) => {
  return {
    type: BUILDER_HOVER_MENUS,
    blockID,
    menuName,
    adding
  };
};

/**
 * @param {number} hoverID
 * @returns {{hoverID: *, type: string}}
 */
export const builderHoverID = (hoverID) => {
  return {
    type: BUILDER_HOVER_ID,
    hoverID
  };
};

/**
 * @param {number} hoverSectionID
 * @returns {{type: string}}
 */
export const builderHoverSectionID = (hoverSectionID) => {
  return {
    type: BUILDER_HOVER_SECTION_ID,
    hoverSectionID
  };
};

/**
 * @param {number} activeSectionID
 * @returns {{type: string}}
 */
export const builderActiveSectionID = (activeSectionID) => {
  return {
    type: BUILDER_ACTIVE_SECTION_ID,
    activeSectionID
  };
};

/**
 * @param {number} hoverRegionID
 * @returns {{type: string}}
 */
export const builderHoverRegionID = (hoverRegionID) => {
  return {
    type: BUILDER_HOVER_REGION_ID,
    hoverRegionID
  };
};

/**
 * @param {number} hoverComponentID
 * @returns {{type: *, hoverComponentID: *}}
 */
export const builderHoverComponentID = (hoverComponentID) => {
  return {
    type: BUILDER_HOVER_COMPONENT_ID,
    hoverComponentID
  };
};

/**
 * @param {number} hoverBGColorID
 * @returns {{type: *, hoverBgColorID: *}}
 */
export const builderHoverBGColorID = (hoverBGColorID) => {
  return {
    type: BUILDER_HOVER_BG_COLOR_ID,
    hoverBGColorID
  };
};

/**
 * @param {number} draggingBlockID
 * @param {number} pageX
 * @param {number} pageY
 * @returns {{type: string}}
 */
export const builderDraggingBlock = (draggingBlockID, pageX = 0, pageY = 0) => {
  return {
    type: BUILDER_DRAGGING_BLOCK,
    draggingBlockID,
    pageX,
    pageY
  };
};

/**
 * @param {number} dropZoneID
 * @returns {{hoverID: *, type: string}}
 */
export const builderDropZoneID = (dropZoneID) => {
  return {
    type: BUILDER_DROP_ZONE_ID,
    dropZoneID
  };
};

/**
 * @param {number} activeID
 * @param {boolean} deselectEditing
 * @returns {{activeID: *, type: string}}
 */
export const builderActiveID = (activeID, deselectEditing = true) => {
  return (dispatch, getState) => {
    if (deselectEditing && getState().builder.editingID !== -1) {
      dispatch(builderContentEditing(-1));
    }
    dispatch({
      type: BUILDER_ACTIVE_ID,
      activeID
    });
  };
};

/**
 * @returns {{type: string}}
 */
export const builderDeselectAll = () => {
  return (dispatch) => {
    dispatch(builderActiveID(-1));
    dispatch(builderHoverID(-1));
    dispatch(builderContentEditing(-1));
    dispatch({
      type: BUILDER_DESELECT_ALL
    });
  };
};

/**
 * @param {number} blockID
 * @returns {{blockID: *, type: string}}
 */
export const builderRemoveBlock = (blockID) => {
  return (dispatch) => {
    dispatch(builderDeselectAll());
    dispatch({
      type: BUILDER_REMOVE_BLOCK,
      blockID
    });
    setTimeout(() => {
      // dispatch(builderDeselectAll());
    }, 100);
  };
};

/**
 * @param {number} blockID
 * @returns {{blockID: *, type: string}}
 */
export const builderCloneBlock = (blockID) => {
  return (dispatch) => {
    dispatch({
      type: BUILDER_CLONE_BLOCK,
      blockID
    });
  };
};

/**
 * @param {number} blockID
 * @returns {(function(*, *))|*}
 */
export const builderLibrarySave = (blockID) => {
  return (dispatch, getState) => {
    dispatch(uiPrompt('Section Name', '', '', (name) => {
      if (!name) {
        return;
      }

      loading(true);
      const { builder } = getState();
      const { id, mode, blocks } = builder;
      const block = blocks.getByID(blockID);
      const element = block.element.cloneNode(true);
      Data.removeBlockID(element);
      element.querySelectorAll('*')
        .forEach((el) => {
          Data.removeBlockID(el);
        });

      const body = {
        id,
        mode,
        name,
        html: element.outerHTML
      };

      api.post(router.generate('build_library'), body)
        .then((libraries) => {
          dispatch({
            type: BUILDER_UPDATE_LIBRARIES,
            libraries
          });

          dispatch(uiSidebarSection('libraries'));
          dispatch(builderCheckPendingLibraries());
          eventDispatcher.trigger('sectionLibraryAdded');
          dispatch(uiNotice('success', 'Saved!'));
        })
        .finally(() => {
          loading(false);
        });
    }));
  };
};

/**
 * @param {number} id
 * @returns {(function(*): void)|*}
 */
export const builderLibraryDelete = (id) => {
  return (dispatch, getState) => {
    loading(true);
    api.req('DELETE', router.generate('build_library_delete', { id }))
      .then((libraries) => {
        if (Array.isArray(libraries)) {
          dispatch({
            type: BUILDER_UPDATE_LIBRARIES,
            libraries
          });

          const { builder } = getState();
          const { sections, components, libraries: libs } = builder;
          if (libs.length === 0) {
            if (sections.length > 0) {
              dispatch(uiSidebarSection('sections'));
            } else if (components.length > 0) {
              dispatch(uiSidebarSection('components'));
            } else {
              dispatch(uiSidebarSection('sections'));
            }
          }
        }
      })
      .finally(() => {
        loading(false);
      });
  };
};

/**
 * @param {number} id
 * @param {string} name
 * @param {number} pinGroup
 * @returns {(function(*): void)|*}
 */
export const builderLibraryUpdate = (id, name, pinGroup) => {
  return (dispatch) => {
    const body = {
      name,
      pinGroup,
    };

    loading(true);
    api.post(router.generate('build_library_update', { id }), body)
      .then((libraries) => {
        if (Array.isArray(libraries)) {
          dispatch({
            type: BUILDER_UPDATE_LIBRARIES,
            libraries
          });
        }
      })
      .finally(() => {
        loading(false);
      });
  };
};

/**
 * @param name
 * @returns {(function(*, *): void)|*}
 */
export const builderPinGroupSave = (name) => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { id, tid } = builder;

    const body = {
      name
    };

    loading(true);
    api.post(router.generate('build_libraries_pin_groups_save', { id: tid || id }), body)
      .then((pinGroup) => {
        dispatch({
          type: BUILDER_PIN_GROUP_SAVE,
          pinGroup,
        });
      })
      .finally(() => {
        loading(false);
      });
  };
};

/**
 * @param id
 * @param name
 * @returns {(function(*): void)|*}
 */
export const builderPinGroupUpdate = (id, name) => {
  return (dispatch) => {
    const body = {
      name
    };

    loading(true);
    api.post(router.generate('build_libraries_pin_groups_update', { id }), body)
      .then((pinGroup) => {
        dispatch({
          type: BUILDER_PIN_GROUP_UPDATE,
          pinGroup,
        });
      })
      .finally(() => {
        loading(false);
      });
  };
};

/**
 * @param id
 * @returns {(function(*): void)|*}
 */
export const buildPinGroupDelete = (id) => {
  return (dispatch) => {
    loading(true);
    api.req('DELETE', router.generate('build_libraries_pin_groups_delete', { id }))
      .then(() => {
        dispatch({
          type: BUILDER_PIN_GROUP_DELETE,
          id,
        });
      })
      .finally(() => {
        loading(false);
      });
  };
};

/**
 * @param {number} blockID
 * @returns {{type: string}}
 */
export const builderExpandBlock = (blockID) => {
  return {
    type: BUILDER_EXPAND_BLOCK,
    blockID
  };
};

/**
 * @param {boolean} gridVisible
 * @returns {{gridVisible: *, type: string}}
 */
export const builderToggleGrid = (gridVisible = null) => {
  return {
    type: BUILDER_TOGGLE_GRID,
    gridVisible
  };
};

/**
 * @param {*} draggable
 * @param {*} draggableClone
 * @returns {{draggableID: *, type: string}}
 */
export const builderDragStart = (draggable, draggableClone) => {
  return (dispatch) => {
    dispatch(builderContentEditing(-1));
    dispatch({
      type: BUILDER_DRAG_START,
      draggable,
      draggableClone
    });
  };
};

/**
 * @param {number} pageX
 * @param {number} pageY
 * @returns {{draggableID: *, type: string}}
 */
export const builderDragEnd = (pageX, pageY) => {
  return {
    type: BUILDER_DRAG_END,
    pageX,
    pageY
  };
};

/**
 * @param {number} dropZoneID
 * @returns {{draggableID: *, type: string, dropZoneID: *}}
 */
export const builderDrop = (dropZoneID) => {
  return {
    type: BUILDER_DROP,
    dropZoneID
  };
};

/**
 * @param {number} blockID
 * @param {string} direction
 * @returns {{blockID: *, type: string, direction: *}}
 */
export const builderMove = (blockID, direction) => {
  return {
    type: BUILDER_MOVE,
    direction,
    blockID
  };
};

/**
 * @param {number} scrollTop
 * @returns {{type: string, scrollTop: *}}
 */
export const builderScrollTop = (scrollTop) => {
  return {
    type: BUILDER_SCROLL_TOP,
    scrollTop
  };
};

/**
 * @param {string} canvasHeight
 * @returns {{type: string}}
 */
export const builderCanvasHeight = (canvasHeight) => {
  return {
    type: BUILDER_CANVAS_HEIGHT,
    canvasHeight
  };
};

/**
 * @returns {{type: string}}
 */
export const builderExportEmail = () => {
  return (dispatch) => {
    dispatch(uiModal('exportEmail', true));
  };
};

/**
 * @param {number} blockID
 * @param {number} variationIndex
 * @returns {{type: string}}
 */
export const builderVariation = (blockID, variationIndex) => {
  return (dispatch, getState) => {
    const { builder } = getState();

    if (builder.editingID !== -1) {
      dispatch(builderContentEditing(-1));
    }

    dispatch({
      type: BUILDER_VARIATION,
      variationIndex,
      blockID
    });
    setTimeout(() => {
      dispatch(builderRefreshRects());
    }, 100);
  };
};

/**
 * @param {string} email
 * @param {function} onComplete
 * @returns {function(...[*]=)}
 */
export const builderSendTest = (email, onComplete = null) => {
  return (dispatch, getState) => {
    if (!email || email.indexOf('@') === -1) {
      dispatch(uiNotice('error', 'Invalid email address'));
      return;
    }

    const { builder } = getState();
    const body = {
      eid:     builder.id,
      version: builder.emailVersion,
      email
    };

    api.post(router.generate('build_export_send_link'), body)
      .then((resp) => {
        dispatch(uiNotice('success', resp));
      })
      .catch((error) => {
        console.error(error);
        dispatch(uiNotice('error', error.toString()));
      })
      .finally(() => {
        if (onComplete) {
          onComplete();
        }
      });
  };
};

/**
 * @returns {{type: string}}
 */
export const builderToggleColorScheme = () => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { iframe, colorScheme } = builder;

    const scheme = colorScheme === 'light' ? 'dark' : 'light';
    const doc = browser.iFrameDocument(iframe);
    HTMLUtils.switchPreferredColorScheme(doc, scheme);

    dispatch({
      type:        BUILDER_SET_COLOR_SCHEME,
      colorScheme: scheme
    });
  };
};

/**
 * @param visible
 * @returns {(function(*): void)|*}
 */
export const builderToggleVariantVisibility = (visible) => {
  return (dispatch) => {
    dispatch({
      type: BUILDER_TOGGLE_VARIANT_VISIBILITY,
      visible,
    });
  };
};

/**
 * @param isFirstRulesEdit
 * @returns {{isFirstRulesEdit, type: string}}
 */
export const builderSetFirstRulesEdit = (isFirstRulesEdit) => ({
  type: BUILDER_SET_FIRST_RULES_EDIT,
  isFirstRulesEdit,
});

/**
 * @returns {(function(*, *): void)|*}
 */
export const builderWatchShortKeys = () => {
  return (dispatch, getState) => {
    const { iframe } = getState().builder;

    /**
     * @param e
     */
    const handleKeyDown = (e) => {
      let key = '';
      if (e.ctrlKey) {
        key += 'Control-';
      } else if (e.shiftKey) {
        key += 'Shift-';
      } else if (e.altKey) {
        key += 'Alt-';
      }
      key += e.key;

      switch (key) {
        case 'Control-z':
          console.log('Control-z');
          e.preventDefault();
          dispatch(builderUndo());

          // eslint-disable-next-line no-case-declarations
          const doc = iFrameDocument(getState().builder.iframe);
          doc.removeEventListener('keydown', handleKeyDown);
          doc.addEventListener('keydown', handleKeyDown, false);
          break;
      }
    };

    /**
     *
     */
    const handleFrameLoad = () => {
      const doc = iFrameDocument(iframe);
      doc.removeEventListener('keydown', handleKeyDown);
      doc.addEventListener('keydown', handleKeyDown, false);
    };

    iframe.addEventListener('load', handleFrameLoad);
    handleFrameLoad();

    ContentEditable.eventDispatcher.on('emit', (html, blockID, attribs) => {
      setTimeout(() => {
        try {
          dispatch({
            type: BUILDER_PUSH_HTML,
            blockID,
            attribs,
            html,
          });
          // eslint-disable-next-line no-empty
        } catch (error) { }
      }, 1);
    });

    ContentEditable.onUndo = (e) => {
      e.preventDefault();
      e.stopPropagation();
    };
  };
};

/**
 * @param blockId
 * @return {{blockId, type: string}}
 */
export const builderScrollToBlock = (blockId) => {
  return (dispatch) => {
    dispatch({
      type: BUILDER_SCROLL_TO_BLOCK,
      blockId,
    });
  };
};

export const actions = {
  setState:                builderSetState,
  open:                    builderOpen,
  clearState:              builderClearState,
  updateBlocks:            builderUpdateBlocks,
  updateBlock:             builderUpdateBlock,
  toggleGrid:              builderToggleGrid,
  emailSettings:           builderEmailSettings,
  toggleColorScheme:       builderToggleColorScheme,
  hoverID:                 builderHoverID,
  dropZoneID:              builderDropZoneID,
  dragEnd:                 builderDragEnd,
  dragStart:               builderDragStart,
  draggingBlock:           builderDraggingBlock,
  contentEditing:          builderContentEditing,
  uploadTemplate:          builderUploadTemplate,
  undo:                    builderUndo,
  redo:                    builderRedo,
  save:                    builderSave,
  saveTemplate:            builderSaveTemplate,
  editing:                 builderEditing,
  deselectAll:             builderDeselectAll,
  exportEmail:             builderExportEmail,
  cancelEditing:           builderCancelEditing,
  watchShortKeys:          builderWatchShortKeys,
  toggleVariantVisibility: builderToggleVariantVisibility,
  pinsUpgradeAll:          builderPinsUpgradeAll,
  iframeRefresh:           builderIFrameRefresh,
  setHTML:                 builderSetHTML,
  setFirstRulesEdit:       builderSetFirstRulesEdit,
  emailUpgrade:            builderEmailUpgrade,
  uploadNewVersion:        builderUploadNewVersion,
  pinGroupSave:            builderPinGroupSave,
  libraryDelete:           builderLibraryDelete,
  libraryUpdate:           builderLibraryUpdate,
  pinGroupUpdate:          builderPinGroupUpdate,
  pinGroupDelete:          buildPinGroupDelete,
  scrollToBlock:           builderScrollToBlock
};

/**
 * @returns {{}}
 */
export const useBuilderActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
