import cloneDeep from 'clone-deep';
import { createReducer } from 'utils';
import { setCookie, eraseCookie } from 'dashboard/utils/cookies';
import arrays from 'utils/arrays';
import { types } from '../actions/usersActions';

let initialState = {
  me:            null,
  account:       null,
  idProviders:   [],
  notifications: []
};
if (window.initialState && window.initialState.users) {
  initialState = cloneDeep(window.initialState.users);
  if (initialState.me && initialState.me.theme === 'dark') {
    setCookie('isDarkMode', '1');
  }
}
if (window.initialState && window.initialState.notifications) {
  initialState.notifications = window.initialState.notifications;
}

/**
 * @param state
 * @param action
 * @returns {*&{me}}
 */
const onUpdateMe = (state, action) => {
  let me = cloneDeep(state.me);
  const { values } = action;

  me = {
    ...me,
    ...values,
  };

  if (values.theme !== undefined) {
    me.isDarkMode = values.theme === 'dark';
    if (values.theme === 'auto') {
      me.isDarkMode = null;
      eraseCookie('isDarkMode');
    } else {
      setCookie('isDarkMode', me.isDarkMode ? '1' : '0');
    }
  }

  return {
    ...state,
    me,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{account}}
 */
const onAccount = (state, action) => {
  const { account } = action;

  return {
    ...state,
    account,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{organization}}
 */
const onUpdateOrg = (state, action) => {
  const me = cloneDeep(state.me);
  const organization = { ...state.account.organization };
  const { oid, values } = action;

  Object.keys(values).forEach((key) => {
    organization[key] = values[key];
  });
  me.organizations.forEach((org) => {
    if (org.id === oid) {
      Object.keys(values).forEach((key) => {
        org[key] = values[key];
      });
    }
  });

  return {
    ...state,
    organization,
    me,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{account}}
 */
const onRevokePowers = (state, action) => {
  const account = { ...state.account };
  const { uid, role } = action;

  // eslint-disable-next-line import/no-named-as-default-member
  const index = arrays.findIndexByID(account[role], uid);
  if (index !== -1) {
    account[role].splice(index, 1);
  }

  return {
    ...state,
    account,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{account}}
 */
const onInviteUser = (state, action) => {
  const account = { ...state.account };
  const { owners, admins, editors } = action;

  account.owners = owners;
  account.admins = admins;
  account.editors = editors;

  return {
    ...state,
    account,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{me}}
 */
const onLogin = (state, action) => {
  const { users } = action;

  return {
    ...state,
    me: users.me,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{idProviders}}
 */
const onIdProviders = (state, action) => {
  const { idProviders } = action;

  return {
    ...state,
    idProviders,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{isDarkMode}}
 */
const onSwitchTheme = (state, action) => {
  const me = { ...state.me };
  const { isDarkMode } = action;

  me.isDarkMode = isDarkMode;
  setCookie('isDarkMode', isDarkMode ? '1' : '0');

  return {
    ...state,
    me,
  };
};

/**
 * @param state
 * @param action
 * @return {*&{notifications: unknown[]}}
 */
const onNotificationStatus = (state, action) => {
  const notifications = Array.from(state.notifications);

  const index = notifications.findIndex((n) => n.id === action.id);
  if (index !== -1) {
    notifications[index].status = action.status;
  }

  return {
    ...state,
    notifications,
  };
};

/**
 * @param state
 * @param action
 * @return {*&{notifications: unknown[]}}
 */
const onNotificationsStatus = (state, action) => {
  const notifications = Array.from(state.notifications);

  for (let i = 0; i < notifications.length; i++) {
    notifications[i].status = action.status;
  }

  return {
    ...state,
    notifications,
  };
};

/**
 * @param state
 * @param action
 * @return {*&{notifications: (*)}}
 */
const onNewNotifications = (state, action) => {
  let notifications = cloneDeep(state.notifications);
  const { newNotifications } = action;

  notifications = notifications.concat(newNotifications);
  notifications = notifications.sort((a, b) => a.dateCreated > b.dateCreated ? -1 : 1);

  return {
    ...state,
    notifications,
  };
};

/**
 * @param state
 * @param action
 * @return {*&{notifications: (*)}}
 */
const onNewNotification = (state, action) => {
  let notifications = cloneDeep(state.notifications);
  const { notification } = action;

  notifications.push(notification);
  notifications = notifications.sort((a, b) => a.dateCreated > b.dateCreated ? -1 : 1);

  return {
    ...state,
    notifications,
  };
};

/**
 * @param state
 * @param action
 * @return {*&{notifications: unknown[]}}
 */
const onDeleteNotification = (state, action) => {
  const notifications = Array.from(state.notifications);

  const index = notifications.findIndex((n) => n.id === action.id);
  if (index !== -1) {
    notifications.splice(index, 1);
  }

  return {
    ...state,
    notifications,
  };
};

/**
 * @param state
 * @param action
 * @return {*&{me: (*)}}
 */
const onSkinTone = (state, action) => {
  const me = cloneDeep(state.me);

  me.skinTone = action.skinTone;

  return {
    ...state,
    me,
  };
};

const handlers = {
  [types.UPDATE_ME]:            onUpdateMe,
  [types.ACCOUNT]:              onAccount,
  [types.UPDATE_ORG]:           onUpdateOrg,
  [types.REVOKE_POWERS]:        onRevokePowers,
  [types.INVITE_USER]:          onInviteUser,
  [types.LOGIN]:                onLogin,
  [types.ID_PROVIDERS]:         onIdProviders,
  [types.SWITCH_THEME]:         onSwitchTheme,
  [types.NOTIFICATION_STATUS]:  onNotificationStatus,
  [types.NOTIFICATIONS_STATUS]: onNotificationsStatus,
  [types.NEW_NOTIFICATIONS]:    onNewNotifications,
  [types.NEW_NOTIFICATION]:     onNewNotification,
  [types.DELETE_NOTIFICATION]:  onDeleteNotification,
  [types.SET_SKIN_TONE]:        onSkinTone,
};

export default createReducer(initialState, handlers);
