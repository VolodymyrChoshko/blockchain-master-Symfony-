import cloneDeep from 'clone-deep';
import { createReducer } from 'utils';
import { types } from '../actions/checklistActions';

const initialState = {
  settings: {},
  items: [],
};

/**
 * @param state
 * @param action
 * @return {*}
 */
const onSetSettings = (state, action) => {
  return {
    ...state,
    settings: cloneDeep(action.settings),
    items: cloneDeep(action.items),
  };
};

/**
 * @param state
 * @param action
 * @return {*}
 */
const onCheck = (state, action) => {
  const items = Array.from(state.items);
  const { key, user, checked } = action;

  const index = items.findIndex(i => i.key === key);
  if (index !== -1) {
    items[index].checked = checked;
    if (checked) {
      items[index].user = user;
    } else {
      items[index].user = null;
    }
  }

  return {
    ...state,
    items,
  };
};

const handlers = {
  [types.SET_SETTINGS]: onSetSettings,
  [types.CHECK]: onCheck,
};

export default createReducer(initialState, handlers);
