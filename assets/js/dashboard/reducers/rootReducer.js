import { combineReducers } from 'redux';
import ui from '../../builder/reducers/uiReducer';
import user from '../../builder/reducers/userReducer';
import source from '../../builder/reducers/sourceReducer';
import builder from '../../builder/reducers/builderReducer';
import media from '../../builder/reducers/mediaReducer';
import socket from '../../builder/reducers/socketReducer';
import editable from '../../builder/reducers/editableReducer';
import template from './templateReducer';
import users from './usersReducer';
import billing from './billingReducer';
import integrations from './integrationsReducer';
import history from '../../builder/reducers/historyReducer';
import comment from '../../builder/reducers/commentReducer';
import rules from '../../builder/reducers/rulesReducer';
import checklist from '../../builder/reducers/checklistReducer';

const logReducer = (reducer) => {
  return (state, action) => {
    console.error(action);
    return reducer(state, action);
  };
};

export default function createRootReducer() {
  return combineReducers({
    ui,
    user,
    users,
    source,
    builder,
    template,
    comment,
    rules,
    media,
    socket,
    history,
    editable,
    billing,
    checklist,
    integrations,
  });
}
