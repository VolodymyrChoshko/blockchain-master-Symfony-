import { useMemo } from 'react';
import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';
import { loading } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import { uiAlert, uiNotice } from 'builder/actions/uiActions';

export const actions = {};
export const types = {
  ADD:    'INTEGRATIONS_ADD',
  LOAD:   'INTEGRATIONS_LOAD',
  REMOVE: 'INTEGRATIONS_REMOVE',
};

/**
 * @returns {(function(*): Promise<void>)|*}
 */
actions.load = () => {
  return async (dispatch) => {
    try {
      loading(true);
      const resp = await api.get(router.generate('api_v1_integrations'));
      if (resp.error) {
        dispatch(uiAlert('Error', resp.error));
      } else {
        dispatch({
          type: types.LOAD,
          ...resp,
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
 * @param source
 * @returns {(function(*): Promise<void>)|*}
 */
actions.removeSource = (source) => {
  return async (dispatch) => {
    try {
      loading(true);
      const resp = await api.post(router.generate('integrations_remove', { iid: source.id }));
      if (resp.error) {
        dispatch(uiAlert('Error', resp.error));
      } else {
        dispatch({
          type: types.REMOVE,
          source,
        });
        dispatch(uiNotice('success', 'Integration removed!'));
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
 * @param slug
 * @param cb
 * @returns {(function(*): Promise<void>)|*}
 */
actions.add = (slug, cb) => {
  return async (dispatch) => {
    try {
      loading(true);
      const source = await api.post(router.generate('integrations_add', { slug }));
      if (source.error) {
        dispatch(uiAlert('Error', source.error));
      } else {
        dispatch({
          type: types.ADD,
          source,
        });
        cb(source);
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
 * @returns {{}}
 */
export const useIntegrationsActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
