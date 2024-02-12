import { createReducer } from 'utils';
import objects from 'utils/objects';
import * as types from '../actions/userActions';

const initialState = objects.merge({
  email:        '',
  dashboardUrl: ''
}, window.initialState.user);

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onInitialState = (state, action) => {
  const { initialState: is } = action;

  return {
    ...state,
    ...is,
  };
};

const handlers = {
  [types.USER_INITIAL_STATE]: onInitialState,
};

export default createReducer(initialState, handlers);
