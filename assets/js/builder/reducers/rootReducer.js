import { combineReducers } from 'redux';
import users from 'dashboard/reducers/usersReducer';
import template from 'dashboard/reducers/templateReducer';
import ui from './uiReducer';
import media from './mediaReducer';
import source from './sourceReducer';
import socket from './socketReducer';
import builder from './builderReducer';
import editable from './editableReducer';

const logReducer = (reducer) => {
  return (state, action) => {
    console.log(action);
    return reducer(state, action);
  };
};

/**
 * @returns {Reducer}
 */
export default function createRootReducer() {
  return combineReducers({
    ui,
    users,
    media,
    source,
    socket,
    builder,
    editable,
    template
  });
}
