import { useMemo } from 'react';
import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';
import { loading } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import { uiAlert, uiNotice } from 'builder/actions/uiActions';

export const actions = {};
export const types = {
  LOAD:        'BILLING_LOAD',
  REMOVE_CARD: 'BILLING_REMOVE_CARD',
};

/**
 * @returns {(function(*): Promise<void>)|*}
 */
actions.load = () => {
  return async (dispatch) => {
    try {
      loading(true);
      const resp = await api.get(router.generate('api_v1_billing'));
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
 * @returns {(function(*): Promise<void>)|*}
 */
actions.upgrade = () => {
  return async (dispatch) => {
    try {
      loading(true);
      const resp = await api.post(router.generate('api_v1_billing_upgrade'));
      if (resp.error) {
        dispatch(uiAlert('Error', resp.error));
      } else if (resp.success) {
        dispatch(uiAlert('success', resp.success));
        dispatch(actions.load());
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
 * @returns {(function(*): Promise<void>)|*}
 */
actions.downgrade = () => {
  return async (dispatch) => {
    try {
      loading(true);
      const resp = await api.post(router.generate('api_v1_billing_downgrade'));
      if (resp.error) {
        dispatch(uiAlert('Error', resp.error));
      } else if (resp.success) {
        dispatch(uiNotice('success', resp.success));
        dispatch(actions.load());
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
 * @returns {(function(*): Promise<void>)|*}
 */
actions.removeCreditCard = () => {
  return async (dispatch) => {
    try {
      loading(true);
      const resp = await api.post(router.generate('api_v1_billing_cards_remove'));
      if (resp.error) {
        dispatch(uiAlert('Error', resp.error));
      } else if (resp.success) {
        dispatch(uiNotice('success', resp.success));
        dispatch({
          type: types.REMOVE_CARD,
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
 * @returns {{}}
 */
export const useBillingActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
