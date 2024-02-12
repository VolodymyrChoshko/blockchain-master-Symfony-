import { useMemo } from 'react';
import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';
import { loading } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import { actions as uiActions } from 'builder/actions/uiActions';

export const actions = {};
export const types = {
  UPDATE_ME:            'USERS_UPDATE_ME',
  ACCOUNT:              'USERS_ACCOUNT',
  UPDATE_ORG:           'USERS_UPDATE_ORG',
  REVOKE_POWERS:        'USERS_REVOKE_POWERS',
  INVITE_USER:          'USERS_INVITE_USER',
  LOGIN:                'USERS_LOGIN',
  ID_PROVIDERS:         'USERS_ID_PROVIDERS',
  SWITCH_THEME:         'USERS_SWITCH_THEME',
  NOTIFICATION_STATUS:  'USERS_NOTIFICATION_STATUS',
  NOTIFICATIONS_STATUS: 'USERS_NOTIFICATIONS_STATUS',
  NEW_NOTIFICATIONS:    'USERS_NEW_NOTIFICATIONS',
  NEW_NOTIFICATION:     'USERS_NEW_NOTIFICATION',
  DELETE_NOTIFICATION:  'USERS_DELETE_NOTIFICATION',
  SET_SKIN_TONE:        'USERS_SET_SKIN_TONE',
};

/**
 * @param values
 * @param ref
 * @returns {(function(): Promise<void>)|*}
 */
actions.createAccount = (values, ref = '') => {
  return async (dispatch) => {
    try {
      loading(true);

      const resp = await api.post(`${router.generate('api_v1_signup')}?ref=${encodeURIComponent(ref)}`, values);
      if (resp.error) {
        dispatch(uiActions.alert('Error', resp.message));
      } else if (resp.redirect) {
        document.location = resp.redirect;
      }
      // document.location = '/';
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param email
 * @param password
 * @param cb
 * @returns {(function(*): Promise<void>)|*}
 */
actions.login = (email, password, cb) => {
  return async (dispatch) => {
    try {
      loading(true);
      const body = {
        email,
        password,
      };
      const resp = await api.post(router.generate('api_v1_auth_login'), body);
      if (resp.error) {
        dispatch(uiActions.alert('Error', resp.error));
      } else if (resp.redirect) {
        document.location = resp.redirect;
      } else {
        cb();
      }
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param oid
 * @returns {(function(*): Promise<void>)|*}
 */
actions.loadIdProviders = (oid) => {
  return async (dispatch) => {
    try {
      const idProviders = await api.post(router.generate('api_v1_auth_id_providers'), { oid });
      if (idProviders && Array.isArray(idProviders)) {
        dispatch({
          type: types.ID_PROVIDERS,
          idProviders
        });
      }
    } catch (error) {
      console.error(error);
    }
  };
};

/**
 * @param values
 * @returns {(function(*): Promise<void>)|*}
 */
actions.updateMe = (values) => {
  return async (dispatch) => {
    try {
      loading(true);
      await api.post(router.generate('api_v1_profile'), values);
      dispatch({
        type: types.UPDATE_ME,
        values
      });
      dispatch(uiActions.notice('success', 'Profile updated!'));
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param values
 * @return {(function(*): Promise<void>)|*}
 */
actions.updateNotificationSettings = (values) => {
  return async (dispatch) => {
    try {
      loading(true);
      await api.post(router.generate('api_v1_profile_save_notifications'), values);
      dispatch({
        type: types.UPDATE_ME,
        values
      });
      dispatch(uiActions.notice('success', 'Notifications updated!'));
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param isDarkMode
 * @returns {{isDarkMode, type: string}}
 */
actions.switchTheme = isDarkMode => ({
  type: types.SWITCH_THEME,
  isDarkMode,
});

/**
 * @param password
 * @param cb
 * @returns {(function(): Promise<void>)|*}
 */
actions.updatePassword = (password, cb) => {
  return async (dispatch) => {
    try {
      loading(true);
      const resp = await api.post(router.generate('change_password'), { password });
      if (resp.error) {
        dispatch(uiActions.alert('Error', resp.error));
      } else {
        dispatch(uiActions.notice('success', 'Password updated!'));
        cb();
      }
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param file
 * @param cb
 * @returns {(function(): Promise<void>)|*}
 */
actions.uploadAvatar = (file, cb) => {
  return async (dispatch) => {
    try {
      loading(true);
      const formData = new FormData();
      formData.append('file', file);
      const url = await api.post(router.generate('profile_upload_avatar'), formData);
      if (url.error) {
        dispatch(uiActions.alert('Error', url.error));
        return;
      }
      cb(url);
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @returns {(function(*): Promise<*>)|*}
 */
actions.fetchAccount = () => {
  return async (dispatch) => {
    try {
      loading(true);
      const account = await api.get(router.generate('api_v1_account'));
      if (account.error) {
        dispatch(uiActions.alert('Error', account.error));
      } else {
        dispatch({
          type: types.ACCOUNT,
          account
        });
      }
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param values
 * @returns {(function(*): Promise<void>)|*}
 */
actions.updateOrganization = (values) => {
  return async (dispatch) => {
    try {
      loading(true);
      const oid = await api.post(router.generate('api_v1_account_organization'), values);
      if (oid.error) {
        dispatch(uiActions.alert('Error', oid.error));
      } else {
        dispatch({
          type: types.UPDATE_ORG,
          values,
          oid,
        });
        dispatch(uiActions.notice('success', 'Organization updated!'));
      }
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param uid
 * @param role
 * @returns {(function(*): Promise<void>)|*}
 */
actions.revokePowers = (uid, role) => {
  return async (dispatch) => {
    try {
      loading(true);
      const resp = await api.req('DELETE', router.generate('api_v1_account_organization_revoke', { id: uid }));
      if (resp.error) {
        dispatch(uiActions.alert('Error', resp.error));
      } else {
        dispatch({
          type: types.REVOKE_POWERS,
          role,
          uid,
        });
        dispatch(uiActions.notice('success', 'Organization updated!'));
      }
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param name
 * @param email
 * @param access
 * @returns {(function(*): Promise<void>)|*}
 */
actions.inviteUser = (name, email, access) => {
  return async (dispatch) => {
    try {
      loading(true);
      const body = {
        name,
        email,
        access,
      };
      const resp = await api.put(router.generate('api_v1_account_organization_invite'), body);
      if (resp.error) {
        dispatch(uiActions.alert('Error', resp.error));
      } else {
        dispatch(uiActions.notice('success', resp.success));
        dispatch({
          type:    types.INVITE_USER,
          owners:  resp.owners,
          admins:  resp.admins,
          editors: resp.editors,
        });
      }
    } catch (error) {
      dispatch(uiActions.alert('Error', error.toString()));
      console.error(error);
    } finally {
      loading(false);
    }
  };
};

/**
 * @param id
 * @param status
 * @return {{id, type: string, status}}
 */
actions.setNotificationStatus = (id, status) => {
  return async (dispatch) => {
    dispatch({
      type: types.NOTIFICATION_STATUS,
      id,
      status,
    });
    await api.post(router.generate('build_notifications_status', { id }), { status });
  };
};

/**
 * @param status
 * @return {(function(*): Promise<void>)|*}
 */
actions.setNotificationsStatus = (status) => {
  return async (dispatch) => {
    dispatch({
      type: types.NOTIFICATIONS_STATUS,
      status,
    });
    await api.post(router.generate('build_notifications_all_status'), { status });
  };
};

/**
 * @param notification
 * @return {{notification, type: string}}
 */
actions.setNewNotification = (notification) => ({
  type: types.NEW_NOTIFICATION,
  notification,
});

/**
 * @param id
 * @return {(function(*): Promise<void>)|*}
 */
actions.deleteNotification = (id) => {
  return async (dispatch) => {
    dispatch({
      type: types.DELETE_NOTIFICATION,
      id,
    });
    await api.req('DELETE', router.generate('build_notifications_delete', { id }));
  };
};

/**
 * @param id
 * @return {{id, type: string}}
 */
actions.removeNotification = (id) => ({
  type: types.DELETE_NOTIFICATION,
  id,
});

/**
 * @param skinTone
 * @return {(function(*): Promise<void>)|*}
 */
actions.setSkinTone = (skinTone) => {
  return async (dispatch) => {
    dispatch({
      type: types.SET_SKIN_TONE,
      skinTone,
    });
    await api.post(router.generate('profile_set_skin_tone'), {
      skinTone,
    });
  };
};

/**
 * @returns {{}}
 */
export const useUsersActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
