import { useMemo } from 'react';
import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';

export const actions = {};
export const types = {
  SET: 'HISTORY_SET',
};

/**
 * @param history
 * @returns {{history, type: string}}
 */
actions.set = (history) => {
  return {
    type: types.SET,
    history,
  };
};

/**
 * @returns {{}}
 */
export const useHistoryActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
