import cloneDeep from 'clone-deep';
import { createReducer } from 'utils';
import { findEmailLocation, findFolderLocation } from 'dashboard/utils';
import arrays, { findIndexByID } from 'utils/arrays';
import { types } from '../actions/templateActions';

let initialState = {
  templates:       {},
  layouts:         {},
  emails:          {},
  folders:         {},
  people:          {},
  searchResults:   [],
  notices:         [],
  hasSources:      false,
  firstUseNotice:  null,
  isDragging:      false,
  templatePeople:  null,
  lastTemplateID:  0,
  billingPlan:     {},
  isLoaded:        false,
  isEmailsLoading: {},
  highlightedPath: [],
};
if (window.initialState && window.initialState.template) {
  initialState = { ...initialState, ...window.initialState.template };
}

/**
 * @param {*} state
 * @param {*} action
 */
const onOpen = (state, action) => {
  // eslint-disable-next-line max-len
  const { templates, layouts, people, hasSources, firstUseNotice, notices, billingPlan, lastTemplateID } = action;

  return {
    ...state,
    templates,
    layouts,
    people,
    notices,
    hasSources,
    firstUseNotice,
    lastTemplateID,
    billingPlan,
    isLoaded: true,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onUpdate = (state, action) => {
  const templates = cloneDeep(state.templates);
  const { id, title } = action;

  templates[id].title = title;

  return {
    ...state,
    templates
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onDelete = (state, action) => {
  const newState = cloneDeep(state);
  const { id } = action;

  delete newState.templates[id];
  delete newState.layouts[id];
  delete newState.emails[id];
  delete newState.folders[id];
  delete newState.people[id];

  return newState;
};

/**
 * @param {*} state
 * @param {*} action
 */
const onDragging = (state, action) => {
  const { isDragging } = action;

  return {
    ...state,
    isDragging
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onEmailsLoading = (state, action) => {
  const newIsEmailsLoading = cloneDeep(state.isEmailsLoading);
  const { tid, isLoading } = action;

  newIsEmailsLoading[tid] = isLoading;

  return {
    ...state,
    isEmailsLoading: newIsEmailsLoading
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onEmails = (state, action) => {
  const newEmails = cloneDeep(state.emails);
  const newFolders = cloneDeep(state.folders);
  const { emails, folders, tid } = action;

  newEmails[tid] = emails;
  newFolders[tid] = folders;

  const flds = newFolders[tid];
  for (let i = 0; i < flds.length; i++) {
    const { pid } = flds[i];
    if (pid) {
      let orphaned = true;
      for (let y = 0; y < flds.length; y++) {
        if (flds[y].id === pid) {
          orphaned = false;
          break;
        }
      }

      if (orphaned || flds[i].pid === flds[i].id) {
        flds[i].pid = 0;
      }
    }
  }

  return {
    ...state,
    emails:  newEmails,
    folders: newFolders,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onEmailCreate = (state, action) => {
  const emails = cloneDeep(state.emails);
  const { email, id, idIsEmail } = action;

  let tid = id;
  if (idIsEmail) {
    ({ tid } = findEmailLocation(emails, id));
  }

  const index = emails[tid].findIndex(e => e.id === -1);
  if (index !== -1) {
   emails[tid].splice(index, 1);
  }
  if (email) {
    emails[tid].push(email);
  }

  return {
    ...state,
    emails,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onEmailDuplicate = (state, action) => {
  const emails = cloneDeep(state.emails);
  const { email } = action;

  const index = emails[email.tid].findIndex(e => e.id === -1);
  if (index !== -1) {
    emails[email.tid].splice(index, 1);
  }
  if (email) {
    emails[email.tid].push(email);
  }

  return {
    ...state,
    emails,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onEmailDelete = (state, action) => {
  const emails = cloneDeep(state.emails);
  const { eid } = action;

  const { tid, index } = findEmailLocation(emails, eid);
  if (index !== -1) {
    emails[tid].splice(index, 1);
  }

  return {
    ...state,
    emails
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onEmailUpdate = (state, action) => {
  const emails = cloneDeep(state.emails);
  const { id, eid, title } = action;

  const index = emails[id].findIndex(e => e.id === eid);
  if (index !== -1) {
    emails[id][index].title = title;
  }

  return {
    ...state,
    emails,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onFolderCreate = (state, action) => {
  const folders = cloneDeep(state.folders);
  const { id, folder } = action;

  if (!folders[id]) {
    folders[id] = [];
  }

  const index = folders[id].findIndex(f => f.id === -1);
  if (index !== -1) {
    folders[id].splice(index, 1);
  }
  if (folder) {
    folders[id].push(folder);
  }

  return {
    ...state,
    folders
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onFolderDelete = (state, action) => {
  const folders = cloneDeep(state.folders);
  const { fid } = action;

  const { tid, index } = findFolderLocation(folders, fid);
  if (index !== -1) {
    folders[tid].splice(index, 1);
  }

  return {
    ...state,
    folders
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onFolderUpdate = (state, action) => {
  const folders = cloneDeep(state.folders);
  const { id, fid, title } = action;

  const index = folders[id].findIndex(f => f.id === fid);
  if (index !== -1) {
    folders[id][index].name = title;
  }

  return {
    ...state,
    folders,
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onFolderDrop = (state, action) => {
  const emails = cloneDeep(state.emails);
  const folders = cloneDeep(state.folders);
  const { action: a, fid, eid, cfid, tid } = action;

  if (a === 'append_folder') {
    if (eid) {
      const tEmails = emails[tid];
      const email   = arrays.findByID(tEmails, eid);
      email.fid     = fid;
    } else {
      const tFolders = folders[tid];
      const folder   = arrays.findByID(tFolders, cfid);
      folder.pid     = fid;
    }
  } else if (a === 'detach_email') {
    const tEmails = emails[tid];
    const email   = arrays.findByID(tEmails, eid);
    email.fid     = 0;
  } else if (a === 'detach_folder') {
    const tFolders = folders[tid];
    const folder   = arrays.findByID(tFolders, fid);
    folder.pid = 0;
  }

  return {
    ...state,
    folders,
    emails
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onSearchEmails = (state, action) => {
  const { searchResults } = action;

  return {
    ...state,
    searchResults
  };
};

/**
 * @param {*} state
 * @param {*} action
 */
const onCloseFirstUseNotice = (state, action) => {
  const notices = cloneDeep(state.notices);
  const { id } = action;

  if (id) {
    const index = findIndexByID(notices, id);
    if (index !== -1) {
      notices.splice(index, 1);
    }

    return {
      ...state,
      notices,
    };
  }

  return {
    ...state,
    firstUseNotice: true
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{templatePeople}}
 */
const onTemplatePeople = (state, action) => {
  return {
    ...state,
    templatePeople: {
      tmpTitle:         action.tmpTitle,
      users:            action.users,
      accountUsers:     action.accountUsers,
      invites:          action.invites,
      billingPlan:      action.billingPlan,
      isOwner:          action.isOwner,
      showUpgradeError: action.showUpgradeError,
    },
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{templatePeople: (*)}}
 */
const onUpdateTemplatePeople = (state, action) => {
  const people = cloneDeep(state.people);

  const templatePeople = {
    ...state.templatePeople,
    users:        action.templatePeople.users,
    accountUsers: action.templatePeople.accountUsers,
    invites:      action.templatePeople.invites,
  };

  const thisTemplatePeople = people[action.id];
  if (thisTemplatePeople) {
    thisTemplatePeople.forEach((person, i) => {
      const index = findIndexByID(templatePeople.users, person.id);
      if (index === -1) {
        thisTemplatePeople.splice(i, 1);
      }
    });

    templatePeople.users.forEach((person) => {
      const index = findIndexByID(thisTemplatePeople, person.id);
      if (index === -1) {
        thisTemplatePeople.push(person);
      }
    });
  }


  return {
    ...state,
    people,
    templatePeople,
  };
};

/**
 * @param state
 * @returns {*&{templatePeople: null}}
 */
const onResetTemplatePeople = (state) => {
  return {
    ...state,
    templatePeople: null,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{lastTemplateID}}
 */
const onLastTemplateID = (state, action) => {
  const { id } = action;

  return {
    ...state,
    lastTemplateID: id,
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onRestore = (state, action) => {
  const { origState } = action;

  return {
    ...state,
    ...origState,
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetBillingPlan = (state, action) => {
  const { billingPlan } = action;

  return {
    ...state,
    billingPlan,
  };
};

/**
 * @param state
 * @param action
 * @returns {*}
 */
const onSetHighlightedPath = (state, action) => {
  return {
    ...state,
    highlightedPath: action.highlightedPath,
  };
};

const handlers = {
  [types.OPEN]:                   onOpen,
  [types.UPDATE]:                 onUpdate,
  [types.DELETE]:                 onDelete,
  [types.RESTORE]:                onRestore,
  [types.DRAGGING]:               onDragging,
  [types.EMAILS]:                 onEmails,
  [types.EMAILS_LOADING]:         onEmailsLoading,
  [types.EMAIL_CREATE]:           onEmailCreate,
  [types.EMAIL_DUPLICATE]:        onEmailDuplicate,
  [types.EMAIL_DELETE]:           onEmailDelete,
  [types.EMAIL_UPDATE]:           onEmailUpdate,
  [types.FOLDER_DELETE]:          onFolderDelete,
  [types.FOLDER_CREATE]:          onFolderCreate,
  [types.FOLDER_DROP]:            onFolderDrop,
  [types.FOLDER_UPDATE]:          onFolderUpdate,
  [types.SEARCH_EMAILS]:          onSearchEmails,
  [types.CLOSE_FU_NOTICE]:        onCloseFirstUseNotice,
  [types.TEMPLATE_PEOPLE]:        onTemplatePeople,
  [types.UPDATE_TEMPLATE_PEOPLE]: onUpdateTemplatePeople,
  [types.RESET_TEMPLATE_PEOPLE]:  onResetTemplatePeople,
  [types.LAST_TEMPLATE_ID]:       onLastTemplateID,
  [types.SET_BILLING_PLAN]:       onSetBillingPlan,
  [types.SET_HIGHLIGHTED_PATH]:   onSetHighlightedPath,
};

export default createReducer(initialState, handlers);
