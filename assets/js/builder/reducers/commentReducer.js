import cloneDeep from 'clone-deep';
import { createReducer } from 'utils';
import { types } from '../actions/commentActions';

const initialState = {
  comments:    [],
  isLoaded:    false,
  isScrolling: false,
  attachedBlock: null
};

/**
 * @param state
 * @param action
 */
const onScroll = (state, action) => {
  return {
    ...state,
    isScrolling: action.isScrolling,
  };
};

/**
 * @param state
 * @param action
 */
const onSet = (state, action) => {
  return {
    ...state,
    comments: action.comments,
    isLoaded: true,
  };
};

/**
 * @param state
 * @param action
 */
const onAdd = (state, action) => {
  const comments = Array.from(state.comments);
  comments.push(action.comment);

  return {
    ...state,
    comments,
    attachedBlock: null
  };
};

/**
 * @param state
 * @param action
 */
const onReplace = (state, action) => {
  const comments = Array.from(state.comments);
  const { comment, tempId } = action;

  const index = comments.findIndex((c) => c.id === tempId);
  if (index !== -1) {
    comments[index] = comment;
    comments[index].tempId = tempId;
  }

  return {
    ...state,
    comments,
  };
};

/**
 * @param state
 * @param action
 */
const onAppend = (state, action) => {
  const comments = cloneDeep(state.comments);
  const { comment } = action;

  const index = comments.findIndex((c) => c.id === comment.id);
  if (index === -1) {
    comments.push(comment);
  } else {
    comments[index] = comment;
  }

  return {
    ...state,
    comments,
  };
};

/**
 * @param state
 * @param action
 */
const onUpdate = (state, action) => {
  const comments = Array.from(state.comments);

  const index = comments.findIndex((c) => c.id === action.id);
  if (index !== -1) {
    comments[index].content = action.content;
  }

  return {
    ...state,
    comments,
    attachedBlock: null
  };
};

/**
 * @param state
 * @param action
 */
const onDelete = (state, action) => {
  const comments = Array.from(state.comments);

  const index = comments.findIndex((c) => c.id === action.id);
  if (index !== -1) {
    comments.splice(index, 1);
  }

  return {
    ...state,
    comments,
    attachedBlock: null
  };
};

/**
 * @param state
 * @param action
 */
const onAddEmoji = (state, action) => {
  const comments = Array.from(state.comments);
  const { id, uuid, code, user } = action;

  const index = comments.findIndex((c) => c.id === id);
  if (index !== -1) {
    const { emojis } = comments[index];
    const found = emojis.findIndex((e) => e.uuid === uuid);
    if (found === -1) {
      emojis.push({
        uuid,
        timeAdded: Date.now() / 1000,
        code,
        user,
      });
    }
  }

  return {
    ...state,
    comments,
  };
};

/**
 * @param state
 * @param action
 */
const onRemoveEmoji = (state, action) => {
  const comments = Array.from(state.comments);
  const { commentId, uuid } = action;

  const index = comments.findIndex((c) => c.id === commentId);
  if (index !== -1) {
    const eIndex = comments[index].emojis.findIndex((e) => e.uuid === uuid);
    if (eIndex !== -1) {
      comments[index].emojis.splice(eIndex, 1);
    }
  }

  return {
    ...state,
    comments,
  };
};

/**
 * @param state
 * @param action
 */
const onUpdateEmojis = (state, action) => {
  const comments = Array.from(state.comments);
  const { id, emojis } = action;

  const index = comments.findIndex((c) => c.id === id);
  if (index !== -1) {
    comments[index].emojis = emojis;
  }

  return {
    ...state,
    comments,
  };
};

/**
 * @param state
 * @param action
 * @return {*&{comments: (*)}}
 */
const onUpdateSkinTone = (state, action) => {
  const comments = cloneDeep(state.comments);
  const { id, skinTone } = action;

  for (let i = 0; i < comments.length; i++) {
    for (let j = 0; j < comments[i].emojis.length; j++) {
      if (comments[i].emojis[j].user.id === id) {
        comments[i].emojis[j].user.skinTone = skinTone;
      }
    }
  }

  return {
    ...state,
    comments,
  };
};

/**
 * @param state
 * @param action
 * @return {*&{attachedBlock}}
 */
const onAttachBlock = (state, action) => {
  return {
    ...state,
    attachedBlock: action.block,
  };
};

const handlers = {
  [types.SCROLL]:           onScroll,
  [types.SET]:              onSet,
  [types.ADD]:              onAdd,
  [types.UPDATE]:           onUpdate,
  [types.REPLACE]:          onReplace,
  [types.DELETE]:           onDelete,
  [types.APPEND]:           onAppend,
  [types.ADD_EMOJI]:        onAddEmoji,
  [types.REMOVE_EMOJI]:     onRemoveEmoji,
  [types.UPDATE_EMOJIS]:    onUpdateEmojis,
  [types.UPDATE_SKIN_TONE]: onUpdateSkinTone,
  [types.ATTACH_BLOCK]:     onAttachBlock,
};

export default createReducer(initialState, handlers);
