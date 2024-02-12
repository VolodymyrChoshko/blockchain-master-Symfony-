import { useMemo } from 'react';
import { bindActionCreators } from 'redux';
import { useDispatch } from 'react-redux';
import { loading } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import { findEmailLocation, findFolderLocation } from 'dashboard/utils';
import { builderUploadTemplate, builderUpgrade } from 'builder/actions/builderActions';
import { uiAlert, uiNotice } from 'builder/actions/uiActions';
import { pushHistoryState } from 'lib/history';

export const actions = {};
export const types = {
  OPEN:                   'TEMPLATE_OPEN',
  UPDATE:                 'TEMPLATE_UPDATE',
  DELETE:                 'TEMPLATE_DELETE',
  RESTORE:                'TEMPLATE_RESTORE',
  DRAGGING:               'TEMPLATE_DRAGGING',
  EMAILS:                 'TEMPLATE_EMAILS',
  EMAILS_LOADING:         'TEMPLATE_EMAILS_LOADING',
  EMAIL_CREATE:           'TEMPLATE_EMAIL_CREATE',
  EMAIL_DUPLICATE:        'TEMPLATE_EMAIL_DUPLICATE',
  EMAIL_DELETE:           'TEMPLATE_EMAIL_DELETE',
  EMAIL_UPDATE:           'TEMPLATE_EMAIL_UPDATE',
  FOLDER_DELETE:          'TEMPLATE_FOLDER_DELETE',
  FOLDER_CREATE:          'TEMPLATE_FOLDER_CREATE',
  FOLDER_DROP:            'TEMPLATE_FOLDER_DROP',
  FOLDER_UPDATE:          'TEMPLATE_FOLDER_UPDATE',
  SEARCH_EMAILS:          'TEMPLATE_SEARCH_EMAILS',
  CLOSE_FU_NOTICE:        'TEMPLATE_CLOSE_FU_NOTICE',
  TEMPLATE_PEOPLE:        'TEMPLATE_TEMPLATE_PEOPLE',
  UPDATE_TEMPLATE_PEOPLE: 'TEMPLATE_UPDATE_TEMPLATE_PEOPLE',
  RESET_TEMPLATE_PEOPLE:  'TEMPLATE_RESET_TEMPLATE_PEOPLE',
  LAST_TEMPLATE_ID:       'TEMPLATE_LAST_TEMPLATE_ID',
  SET_BILLING_PLAN:       'TEMPLATE_SET_BILLING_PLAN',
  SET_HIGHLIGHTED_PATH:   'TEMPLATE_SET_HIGHLIGHTED_PATH',
};

/**
 * @param {function} cb
 * @param {string} previewHash
 * @returns {{type: string}}
 */
actions.templateOpen = (cb = null, previewHash = '') => {
  return async (dispatch) => {
    let path = router.generate('api_v1_templates');
    if (previewHash) {
      path = `${router.generate('api_v1_templates')}?preview_notice=${previewHash}`;
    }

    const {
      templates,
      layouts,
      people,
      hasSources,
      firstUseNotice,
      notices,
      billingPlan,
      lastTemplateID
    } = await api.get(path);

    dispatch({
      type: types.OPEN,
      templates,
      layouts,
      people,
      hasSources,
      billingPlan,
      notices,
      firstUseNotice,
      lastTemplateID
    });
    if (cb) {
      cb();
    }
  };
};

/**
 * @param tid
 * @param isLoading
 * @returns {{isLoading, type: string, tid}}
 */
actions.setEmailsLoading = (tid, isLoading) => ({
  type: types.EMAILS_LOADING,
  isLoading,
  tid,
});

/**
 * @param tid
 * @returns {(function(*): Promise<*>)|*}
 */
actions.fetchEmails = (tid) => {
  return async (dispatch) => {
    try {
      dispatch(actions.setEmailsLoading(tid, true));
      const resp = await api.get(router.generate('templates_emails', { id: tid }));
      dispatch({
        type:    types.EMAILS,
        emails:  resp.emails,
        folders: resp.folders,
        tid,
      });
    } catch (error) {
      console.error(error);
    } finally {
      dispatch(actions.setEmailsLoading(tid, false));
    }
  };
};

/**
 * @param id
 * @param title
 * @returns {(function(*): Promise<void>)|*}
 */
actions.updateTemplate = (id, title) => {
  return async (dispatch, getState) => {
    const { templates } = getState().template;
    const template = templates[id];

    try {
      dispatch({
        type: types.UPDATE,
        id,
        title
      });

      const body = {
        title
      };
      await api.post(router.generate('templates_update', { id }), body);
    } catch (error) {
      dispatch({
        type:  types.UPDATE,
        title: template['title'],
        id,
      });
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    }
  };
};

/**
 * @param {FormData} formData
 * @param {function} cb
 * @returns {(function(*): Promise<void>)|*}
 */
actions.uploadTemplate = (formData, cb) => {
  return async (dispatch) => {
    dispatch(builderUploadTemplate(formData, (id) => {
      dispatch(actions.templateOpen(() => {
        cb(id);
      }));
    }));
  };
};

/**
 * @param id
 * @returns {(function(*, *): Promise<void>)|*}
 */
actions.deleteTemplate = (id) => {
  return async (dispatch, getState) => {
    try {
      loading(true, false);
      await api.req('DELETE', router.generate('templates_update', { id }));
      dispatch({
        type: types.DELETE,
        id,
      });

      const st = localStorage.getItem('dashboard.template.selected');
      if (st && parseInt(st, 10) !== id) {
        const i = parseInt(st, 10);
        pushHistoryState(`/t/${i}`);
      } else {
        const { template }  = getState();
        const { templates } = template;
        const keys = Object.keys(templates);
        if (keys.length > 0 && parseInt(keys[0], 10) !== id) {
          const i = parseInt(keys[0], 10);
          pushHistoryState(`/t/${i}`);
        } else {
          pushHistoryState('/');
        }
      }
    } catch (error) {
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param id
 * @returns {(function(*): Promise<void>)|*}
 */
actions.upgradeCheck = (id) => {
  return async (dispatch) => {
    const needsUpgrade = await api.get(router.generate('build_template_upgrade_check', { id }));
    if (needsUpgrade) {
      await dispatch(builderUpgrade(id));
    }
  };
};

/**
 * @param {number} id
 * @param {string} title
 * @returns {(function(*): Promise<void>)|*}
 */
actions.createEmail = (id, title) => {
  return async (dispatch, getState) => {
    const { users } = getState();
    const { me } = users;

    try {
      dispatch({
        type:  types.EMAIL_CREATE,
        email: {
          title,
          id:            -1,
          fid:           0,
          createdAt:     Math.floor(Date.now() / 1000),
          updatedAt:     0,
          createdUserID: me.id,
        },
        id,
      });
      loading(true, false);
      const body = {
        title
      };
      const resp = await api.put(router.generate('templates_emails_create', { id }), body);
      dispatch({
        type:  types.EMAIL_CREATE,
        email: resp,
        id,
      });
    } catch (error) {
      dispatch({
        type:  types.EMAIL_CREATE,
        email: null,
        id,
      });
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param {number} id
 * @param {string} title
 * @returns {(function(): Promise<void>)|*}
 */
actions.duplicateEmail = (id, title) => {
  return async (dispatch, getState) => {
    const { users, template } = getState();
    const { emails } = template;
    const { me } = users;

    try {
      dispatch({
        type:  types.EMAIL_CREATE,
        email: {
          title,
          id:            -1,
          fid:           0,
          createdAt:     Math.floor(Date.now() / 1000),
          updatedAt:     0,
          createdUserID: me.id,
        },
        id,
        idIsEmail: true,
      });

      // const { tid } = findEmailLocation(emails, id);
      /* const needsUpgrade = await api.get(router.generate('build_template_upgrade_check', { id: tid }));
      if (needsUpgrade) {
        await dispatch(builderUpgrade(tid));
      } */

      loading(true, false);
      const body = {
        title
      };
      const route = router.generate('build_duplicate_email', { id });
      const resp = await api.put(route, body);
      dispatch({
        type:  types.EMAIL_DUPLICATE,
        email: resp,
      });
    } catch (error) {
      dispatch({
        type:  types.EMAIL_DUPLICATE,
        email: null,
        id,
      });
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param {number} eid
 * @returns {(function(*): Promise<*>)|*}
 */
actions.deleteEmail = (eid) => {
  return async (dispatch, getState) => {
    const { template } = getState();
    const { emails } = template;
    const { email } = findEmailLocation(emails, eid);

    try {
      dispatch({
        type: types.EMAIL_DELETE,
        eid,
      });
      const route = router.generate('builder_delete_email', { id: eid });
      await api.req('DELETE', route);
    } catch (error) {
      if (email) {
        dispatch({
          type: types.EMAIL_CREATE,
          id:   email['tid'],
          email,
        });
      }
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    }
  };
};

/**
 * @param id
 * @param eid
 * @param title
 * @param cb
 * @returns {(function(*): Promise<void>)|*}
 */
actions.renameEmail = (id, eid, title, cb) => {
  return async (dispatch, getState) => {
    const { template } = getState();
    const { emails } = template;
    const { email } = findEmailLocation(emails, eid);

    try {
      dispatch({
        type: types.EMAIL_UPDATE,
        title,
        id,
        eid,
      });
      cb();
      setTimeout(async () => {
        const route = router.generate('templates_emails_update', { id, eid });
        await api.post(route, { title });
      }, 1);
    } catch (error) {
      dispatch({
        type:  types.EMAIL_UPDATE,
        title: email['title'],
        id,
        eid,
      });
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    } finally {
      // loading(false);
    }
  };
};

/**
 * @param {number} id
 * @param {string} name
 * @returns {(function(*): Promise<void>)|*}
 */
actions.createFolder = (id, name) => {
  return async (dispatch) => {
    try {
      dispatch({
        type:   types.FOLDER_CREATE,
        folder: {
          id:        -1,
          pid:       0,
          createdAt: Math.floor(Date.now() / 1000),
          updatedAt: 0,
          name,
        },
        id,
      });

      loading(true, false);
      const route = router.generate('templates_folders_create', { id });
      const folder = await api.put(route, { name });
      if (folder.error) {
        dispatch(uiAlert('Error', folder.message));
        return;
      }
      dispatch({
        type: types.FOLDER_CREATE,
        folder,
        id,
      });
      window.dispatchEvent(new Event('be.dropped'));
    } catch (error) {
      dispatch({
        type:   types.FOLDER_CREATE,
        folder: null,
        id,
      });
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param id
 * @param fid
 * @param title
 * @param cb
 * @returns {(function(*): Promise<void>)|*}
 */
actions.renameFolder = (id, fid, title, cb) => {
  return async (dispatch, getState) => {
    const { template } = getState();
    const { folders } = template;
    const { folder } = findFolderLocation(folders, fid);

    try {
      dispatch({
        type: types.FOLDER_UPDATE,
        title,
        id,
        fid,
      });
      cb();

      const route = router.generate('templates_folders_update', { id, fid });
      await api.post(route, { title });
    } catch (error) {
      dispatch({
        type:  types.FOLDER_UPDATE,
        title: folder['name'],
        id,
        fid,
      });
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    }
  };
};

/**
 * @param {number} tid
 * @param {number} fid
 * @param {number} cfid
 * @param {number} eid
 * @param {string} action
 * @returns {(function(*): Promise<void>)|*}
 */
actions.dropInFolder = (tid, fid, cfid, eid, action) => {
  return async (dispatch, getState) => {
    const body = {
      fid,
      cfid,
      eid,
      action
    };

    const origState = getState().template;
    const orig = JSON.stringify(origState);
    dispatch({
      type: types.FOLDER_DROP,
      ...body,
      tid
    });
    const next = JSON.stringify(getState().template);
    if (orig === next) {
      return;
    }

    try {
      loading(true, false);
      const resp = await api.post(router.generate('templates_folders_move', { id: tid }), body);
      if (resp.error) {
        dispatch(uiAlert('Error', resp.error));
        console.error(resp.error);
        dispatch({
          type: types.RESTORE,
          origState,
        });
        return;
      }

      setTimeout(() => {
        window.dispatchEvent(new Event('be.dropped'));
      }, 1000);
    } catch (error) {
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param {number} tid
 * @param {number} fid
 * @returns {(function(*): Promise<*>)|*}
 */
actions.deleteFolder = (tid, fid) => {
  return async (dispatch, getState) => {
    const { template } = getState();
    const { folders } = template;
    const { folder } = findFolderLocation(folders, fid);

    try {
      dispatch({
        type: types.FOLDER_DELETE,
        fid,
      });
      window.dispatchEvent(new Event('be.dropped'));
      const route = router.generate('templates_folder_delete', { id: tid, fid });
      await api.req('DELETE', route);
    } catch (error) {
      dispatch({
        type: types.FOLDER_CREATE,
        id:   tid,
        folder,
      });
      window.dispatchEvent(new Event('be.dropped'));
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    }
  };
};

const searchTimeout = 0;
let searchCancelToken = api.getCancelToken();

/**
 * @param {string|boolean} term
 * @returns {(function(*): Promise<void>)|*}
 */
actions.searchEmails = (term) => {
  return async (dispatch) => {
    if (typeof term === 'boolean') {
      dispatch({
        type:          types.SEARCH_EMAILS,
        searchResults: [],
      });
      loading(false);
      return;
    }

    clearTimeout(searchTimeout);
    setTimeout(async () => {
      try {
        loading(true);
        if (api.isPostBusy('templates_search')) {
          searchCancelToken.cancel();
          searchCancelToken = api.getCancelToken();
          clearTimeout(searchTimeout);
        }

        const searchResults = await api.post(
          router.generate('templates_search'),
          { term },
          {},
          searchCancelToken,
          'templates_search',
        );
        if (Array.isArray(searchResults)) {
          dispatch({
            type: types.SEARCH_EMAILS,
            searchResults,
          });
          loading(false);
        }
      } catch (error) {
        console.error(error);
        loading(false);
      }
    }, 150);
  };
};

/**
 * @returns {{type: string}}
 */
actions.closeNotice = (id = 0) => {
  return async (dispatch) => {
    dispatch({
      type: types.CLOSE_FU_NOTICE,
      id
    });
    await api.post(router.generate('templates_fu_notice'), { id });
  };
};

/**
 * @param isDragging
 * @returns {{type: string, isDragging}}
 */
actions.dragging = isDragging => ({
  type: types.DRAGGING,
  isDragging,
});

/**
 * @param id
 * @returns {(function(*): Promise<void>)|*}
 */
actions.loadTemplatePeople = (id) => {
  return async (dispatch) => {
    try {
      loading(true, false);
      const resp = await api.get(router.generate('api_v1_people', { id }));
      dispatch({
        type: types.TEMPLATE_PEOPLE,
        ...resp,
      });
    } catch (error) {
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param id
 * @param uid
 * @param name
 * @param email
 * @returns {(function(*): Promise<void>)|*}
 */
actions.invitePerson = (id, uid, name = '', email = '') => {
  return async (dispatch) => {
    try {
      loading(true, false);
      const resp = await api.put(router.generate('api_v1_people_invite', { id }), { uid, name, email });
      if (resp.error) {
        dispatch(uiAlert('Error', resp.error));
      } else if (resp.success) {
        dispatch(uiNotice('success', resp.success));
        dispatch({
          type:           types.UPDATE_TEMPLATE_PEOPLE,
          templatePeople: resp.templatePeople,
          id,
        });
      }
    } catch (error) {
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param id
 * @param rid
 * @param iid
 * @returns {(function(*): Promise<void>)|*}
 */
actions.removePerson = (id, rid, iid = 0) => {
  return async (dispatch) => {
    try {
      loading(true, false);
      const resp = await api.req('DELETE', `${router.generate('api_v1_people_invite_remove', { id, rid })}?iid=${iid}`);
      if (resp.error) {
        dispatch(uiAlert('Error', resp.error));
      } else if (resp.success) {
        dispatch(uiNotice('success', resp.success));
        dispatch({
          type:           types.UPDATE_TEMPLATE_PEOPLE,
          id,
          templatePeople: resp.templatePeople,
        });
      }
    } catch (error) {
      dispatch(uiAlert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @returns {{type: string}}
 */
actions.resetTemplatePeople = () => ({
  type: types.RESET_TEMPLATE_PEOPLE,
});

/**
 * @param id
 * @returns {(function(): Promise<void>)|*}
 */
actions.setLastTemplate = (id) => {
  return async (dispatch) => {
    await api.post(router.generate('templates_last'), { id });
    dispatch({
      type: types.LAST_TEMPLATE_ID,
      id,
    });
  };
};

/**
 * @param billingPlan
 * @returns {{billingPlan, type: *}}
 */
actions.setBillingPlan = (billingPlan) => {
  return {
    type: types.SET_BILLING_PLAN,
    billingPlan,
  };
};

/**
 * @param highlightedPath
 * @returns {{highlightedPath, type: string}}
 */
actions.setHighlightedPath = (highlightedPath) => {
  return {
    type: types.SET_HIGHLIGHTED_PATH,
    highlightedPath,
  };
};

/**
 * @returns {{}}
 */
export const useTemplateActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
