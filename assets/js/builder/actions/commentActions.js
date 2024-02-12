import { useMemo } from 'react';
import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';
import { v4 as uuidv4 } from 'uuid';
import { actions as uiActions } from 'builder/actions/uiActions';
import { socketSendComment, socketUpdateComment, socketDeleteComment } from 'builder/actions/socketActions';
import router from 'lib/router';
import api from 'lib/api';

export const actions = {};
export const types = {
  SCROLL:           'COMMENT_SCROLL',
  SET:              'COMMENT_SET',
  ADD:              'COMMENT_ADD',
  UPDATE:           'COMMENT_UPDATE',
  REPLACE:          'COMMENT_REPLACE',
  APPEND:           'COMMENT_APPEND',
  DELETE:           'COMMENT_DELETE',
  ADD_EMOJI:        'COMMENT_ADD_EMOJI',
  REMOVE_EMOJI:     'COMMENT_REMOVE_EMOJI',
  UPDATE_EMOJIS:    'COMMENT_UPDATE_EMOJIS',
  UPDATE_SKIN_TONE: 'COMMENT_UPDATE_SKIN_TONE',
  ATTACH_BLOCK:     'COMMENT_ATTACH_BLOCK'
};

/**
 * Comments are added to the state before the request to the backend to save the
 * comment is made. Comments are added to the state with a temp id but other operations
 * will need to wait for the real id to become available.
 *
 * @param tempId
 * @param getState
 * @return {Promise<unknown>}
 */
const getRealId = (tempId, getState) => {
  return new Promise((resolve) => {
    let counter = 0;
    const i = setInterval(() => {
      const { comment } = getState();
      for (let j = 0; j < comment.comments.length; j++) {
        const c = comment.comments[j];
        if (c.tempId && c.tempId === tempId && c.id !== c.tempId) {
          clearInterval(i);
          resolve(c.id);
        }
      }
      if (counter++ > 10) {
        resolve(0);
      }
    }, 250);
  });
};

/**
 * @return {number}
 */
const getTempId = () => {
  return Math.floor(Math.random() * (-1000000 - -500000 - 1) + -500000);
};

/**
 * @param isScrolling
 * @return {{type: string, isScrolling}}
 */
actions.scroll = (isScrolling) => ({
  type: types.SCROLL,
  isScrolling,
});

/**
 * @param comments
 * @return {{comments, type: string}}
 */
actions.set = (comments) => ({
  type: types.SET,
  comments,
});

/**
 * @param content
 * @return {(function(*, *): Promise<void>)|*}
 */
actions.addComment = (content) => {
  return async (dispatch, getState) => {
    const { builder, users, comment: commentState } = getState();

    const tempId = getTempId();
    const comment = {
      tempId,
      id:          tempId,
      user:        users.me,
      content,
      emojis:      [],
      dateCreated: Date.now() / 1000,
      blockId:     commentState.attachedBlock ? commentState.attachedBlock.id : 0,
      parent:      null,
      status:      ''
    };

    dispatch({
      type: types.ADD,
      comment,
    });

    const response = await api.post(router.generate('build_comments_add', { id: builder.id }), {
      content,
      blockId: commentState.attachedBlock ? commentState.attachedBlock.id : 0,
    });
    dispatch({
      type:    types.REPLACE,
      comment: response,
      tempId,
    });
    dispatch(socketSendComment(response));
  };
};

/**
 * @param cid
 * @param content
 * @return {(function(*, *): Promise<void>)|*}
 */
actions.addReply = (cid, content) => {
  return async (dispatch, getState) => {
    const { builder, users } = getState();

    const tempId = getTempId();
    const comment = {
      tempId,
      id:          tempId,
      user:        users.me,
      content,
      emojis:      [],
      dateCreated: Date.now() / 1000,
      blockId:     0,
      parent:      cid,
      status:      ''
    };

    dispatch({
      type: types.ADD,
      comment,
    });

    const response = await api.post(router.generate('build_comments_reply', { id: builder.id, cid }), {
      content,
    });
    dispatch({
      type:    types.REPLACE,
      comment: response,
      tempId,
    });
    dispatch(socketSendComment(response));
  };
};

/**
 * @param comment
 * @return {{comment, type: string}}
 */
actions.appendComment = (comment) => ({
  type: types.APPEND,
  comment,
});

/**
 * @param id
 * @param content
 * @return {(function(*): Promise<void>)|*}
 */
actions.updateComment = (id, content) => {
  return async (dispatch, getState) => {
    if (id < 0) {
      id = await getRealId(id, getState);
    }

    const comments = getState().comment.comments;
    const comment = comments.find((c) => c.id === id);
    if (comment) {
      dispatch(socketUpdateComment({
        ...comment,
        content,
      }));
    }

    dispatch({
      type: types.UPDATE,
      content,
      id,
    });

    await api.put(router.generate('build_comments_update', { id }), {
      content,
    });
  };
};

/**
 * @param id
 * @return {(function(*, *): Promise<void>)|*}
 */
actions.deleteComment = (id) => {
  return async (dispatch, getState) => {
    if (id < 0) {
      id = await getRealId(id, getState);
    }
    dispatch({
      type: types.DELETE,
      id,
    });
    dispatch(socketDeleteComment(id));
    await api.req('DELETE', router.generate('build_comments_delete', { id }));
  };
};

/**
 * @param id
 * @return {{id, type: string}}
 */
actions.removeComment = (id) => ({
  type: types.DELETE,
  id,
});

/**
 * @param id
 * @param code
 * @return {(function(*): Promise<*>)|*}
 */
actions.addEmoji = (id, code) => {
  return async (dispatch, getState) => {
    if (id < 0) {
      id = await getRealId(id, getState);
    }

    const { users } = getState();
    const uuid = uuidv4();

    const comments = getState().comment.comments;
    const comment = comments.find((c) => c.id === id);
    if (comment) {
      for (let i = 0; i < comment.emojis.length; i++) {
        if (comment.emojis[i].code === code && comment.emojis[i].user.id === users.me.id) {
          return;
        }
      }

      const freshComment = { ...comment };
      const emoji = {
        uuid,
        user:      users.me,
        timeAdded: Date.now() / 1000,
        code,
      };
      freshComment.emojis.push(emoji);
      dispatch(socketUpdateComment(freshComment));
    }

    dispatch({
      type: types.ADD_EMOJI,
      user: users.me,
      uuid,
      code,
      id,
    });

    await api.post(router.generate('build_comments_add_emoji', { id }), {
      uuid,
      code,
    });
  };
};

/**
 * @param commentId
 * @param uuid
 * @return {(function(*): Promise<void>)|*}
 */
actions.removeEmoji = (commentId, uuid) => {
  return async (dispatch, getState) => {
    if (commentId < 0) {
      commentId = await getRealId(commentId, getState);
    }

    const comments = getState().comment.comments;
    const comment = comments.find((c) => c.id === commentId);
    if (comment) {
      const freshComment = { ...comment };
      const index = freshComment.emojis.findIndex((e) => e.uuid === uuid);
      if (index !== -1) {
        freshComment.emojis.splice(index, 1);
        dispatch(socketUpdateComment(freshComment));
      }
    }

    dispatch({
      type: types.REMOVE_EMOJI,
      commentId,
      uuid,
    });

    await api.req(
      'DELETE',
      router.generate('build_comments_delete_emoji', { id: commentId, uuid })
    );
  };
};

/**
 * @param id
 * @param skinTone
 * @return {{skinTone, id, type: string}}
 */
actions.updateSkinTone = (id, skinTone) => ({
  type: types.UPDATE_SKIN_TONE,
  skinTone,
  id,
});

/**
 * @param block
 * @return {{blockId, type: string}}
 */
actions.attachBlock = (block) => {
  return (dispatch, getState) => {
    if (block !== null && getState().comment.attachedBlock === null) {
      dispatch(uiActions.toggleActivity(true));
    }
    setTimeout(() => {
      dispatch({
        type: types.ATTACH_BLOCK,
        block,
      });
    }, 250);
  };
};

/**
 * @returns {{}}
 */
export const useCommentActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
