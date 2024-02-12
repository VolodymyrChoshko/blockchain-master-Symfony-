import api from 'lib/api';
import router from 'lib/router';
import { useMemo } from 'react';
import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';
import { actions as commentActions } from './commentActions';

export const actions = {};
export const types = {
  SET_SETTINGS: 'CHECKLIST_SET_SETTINGS',
  CHECK: 'CHECKLIST_CHECK'
};

/**
 * @param settings
 * @param items
 * @return {{settings, type: string, items}}
 */
actions.setSettings = (settings, items) => {
  return {
    type: types.SET_SETTINGS,
    settings,
    items,
  };
};

/**
 * @return {{type: string}}
 */
actions.check = (key, checked) => {
  return async (dispatch, getState) => {
    const { builder, users } = getState();
    const { me } = users;
    const { id } = builder;

    dispatch({
      type: types.CHECK,
      key,
      user: me,
      checked,
    });
    const resp = await api.post(router.generate('build_checklist_check', { id, key }), {
      checked,
    });
    dispatch(commentActions.set(resp.comments));
  };
};

actions.addTemplateItem = (tid, title, description) => {
  return (dispatch) => {

  };
};

/**
 * @returns {{}}
 */
export const useChecklistActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
