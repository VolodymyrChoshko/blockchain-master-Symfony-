import { createReducer } from 'utils';
import { types } from '../actions/historyActions';

const initialState = {
  history: [],
};

/**
 * @param state
 * @param action
 */
const onSet = (state, action) => {
  const { history } = action;

  return {
    ...state,
    history,
  };
};

const handlers = {
  [types.SET]: onSet,
};

export default createReducer(initialState, handlers);
