import { useMemo } from 'react';
import { bindActionCreators } from 'redux';
import { useDispatch } from 'react-redux';
import router from 'lib/router';
import browser from 'utils/browser';
import { Socket } from 'lib/Socket';
import { uiAlert, uiToggleActivity } from 'builder/actions/uiActions';
import { actions as commentActions } from 'builder/actions/commentActions';
import { actions as userActions } from 'dashboard/actions/usersActions';
import { builderCancelEditing, builderSetHTML, builderUpdateRoom, builderReloadHistory } from './builderActions';

const editTimeout = 3600;
const ticker      = new Worker(router.asset('build/ticker.js'));
let lastDateMoved = new Date();
let socket;
let alert = -1;

/**
 * @returns {(function(*, *): void)|*}
 */
const socketEditing = () => {
  return (dispatch, getState) => {
    const { builder } = getState();
    const { iframe } = builder;

    document.addEventListener('mousemove', () => {
      lastDateMoved = new Date();
    });
    browser.iFrameDocument(iframe).addEventListener('mousemove', () => {
      lastDateMoved = new Date();
    });

    // A web worker is used to mark the intervals because setInterval() does not
    // always work in inactive tabs, but web workers do work. A 'message' event
    // is triggered every second by the web worker.
    ticker.addEventListener('message', async () => {
      const now       = new Date();
      const remaining = Math.floor(editTimeout - ((now.getTime() - lastDateMoved.getTime()) / 1000));

      if (remaining < 0) {
        if (alert !== -1) {
          dispatch(uiAlert(false, '', '', alert));
          alert = -1;
        }
        ticker.postMessage('stop');
        dispatch(builderCancelEditing());
      } else if (remaining < 60) {
        const msg = `Your session will expire in ${remaining} seconds. Unsaved work with be lost.`;
        alert = await dispatch(uiAlert('Session expiring', msg, 'danger', alert));
      } else if (alert !== -1) {
        dispatch(uiAlert(false, '', '', alert));
        alert = -1;
      }
    });

    ticker.postMessage('start');
  };
};

/**
 *
 */
export const socketDisconnect = () => {
  socket.disconnect();
};

/**
 * @return {(function(*, *): Promise<void>)|*}
 */
export const socketConnect = () => {
  return async (dispatch, getState) => {
    if (socket) {
      return;
    }

    const config = getState().socket;
    if (!config || !config.url) {
      console.error('Socket configuration not set.');
      return;
    }

    socket = new Socket(config);
    socket.connect();
  };
};

/**
 * @return {(function(*, *): void)|*}
 */
export const socketSubNotifications = () => {
  return (dispatch, getState) => {
    socket.onConnect((s) => {
      const me = getState().users.me;
      if (me) {
        s.emit('subNotifications', me.id);
      }
      s.on('notification', (notification) => {
        dispatch(userActions.setNewNotification(notification));
      });

      s.on('notification-delete', (id) => {
        dispatch(userActions.removeNotification(id));
      });
    });
  };
};

/**
 * @returns {(function(*, *): void)|*}
 */
export const socketSubRoom = () => {
  return (dispatch, getState) => {
    const { builder, users } = getState();
    // eslint-disable-next-line prefer-const
    let { mode, emailVersion, templateVersion } = builder;
    if (mode === 'email_preview') {
      mode = 'email';
    }
    let room = `${mode}-${templateVersion}-${builder.token}`;
    if (emailVersion) {
      room = `${room}-${emailVersion}`;
    }

    socket.onConnect((s) => {
      s.emit('join', {
        room,
        user: users.me,
        html: builder.html
      });
    });

    /**
     *
     */
    socket.on('joined', (msg) => {
      const joined = [];
      msg.users.forEach((u) => {
        if (u.email !== users.me.email) {
          joined.push(u);
        }
      });
      dispatch(builderUpdateRoom(joined));
    });

    /**
     *
     */
    socket.on('updateUsers', (msg) => {
      const joined = [];
      msg.forEach((u) => {
        if (u.email !== users.me.email) {
          joined.push(u);
        }
      });
      dispatch(builderUpdateRoom(joined));
    });

    /**
     *
     */
    socket.on('left', (msg) => {
      const left = [];
      msg.forEach((u) => {
        if (u.email !== users.me.email) {
          left.push(u);
        }
      });
      dispatch(builderUpdateRoom(left));
    });

    /**
     *
     */
    socket.on('html', (html) => {
      dispatch(builderSetHTML(html));
      dispatch(builderReloadHistory());
    });

    /**
     *
     */
    socket.on('sendComment', (comment) => {
      if (comment.user.id !== users.me.id) {
        dispatch(commentActions.appendComment(comment));
        if (comment.parent) {
          const parent = getState().comment.comments.find((c) => c.id === comment.parent);
          if (parent && parent.user.id === users.me.id) {
            dispatch(uiToggleActivity(true));
          }
        }
      }
    });

    /**
     *
     */
    socket.on('updateComment', (comment) => {
      dispatch(commentActions.appendComment(comment));
    });

    /**
     *
     */
    socket.on('deleteComment', (id) => {
      dispatch(commentActions.removeComment(id));
    });

    /**
     *
     */
    socket.on('updateSkinTone', (data) => {
      if (data.id !== users.me.id) {
        dispatch(commentActions.updateSkinTone(data.id, data.skinTone));
        dispatch(userActions.setSkinTone(data.skinTone));
      }
    });

    /**
     *
     */
    socket.on('switchRoom', (msg) => {
      socketDisconnect();
      document.location = router.generate('build_email', { tid: msg.tid, id: msg.id });
    });

    /**
     *
     */
    socket.on('kick', () => {
      dispatch(builderCancelEditing());
      document.location = '/';
    });
  };
};

/**
 * @returns {(function(*, *): void)|*}
 */
export const socketSwitchRoom = () => {
  return (dispatch, getState) => {
    const { builder } = getState();
    // eslint-disable-next-line prefer-const
    let { mode, templateVersion, emailVersion } = builder;
    if (mode === 'email_preview') {
      mode = 'email';
    }
    let room = `${mode}-${templateVersion}-${builder.token}`;
    if (emailVersion) {
      room = `${room}-${emailVersion}`;
    }

    socket.emit('switchRoom', {
      room,
      id:   builder.id,
      tid:  builder.tid,
      html: builder.html
    });
  };
};

/**
 * @param {string} state
 * @returns {(function(): void)|*}
 */
export const socketUpdateState = (state) => {
  return (dispatch) => {
    socket.emit('updateState', state);

    if (state === 'editing') {
      dispatch(socketEditing());
    } else {
      ticker.postMessage('stop');
    }
  };
};

/**
 * @param comment
 * @return {(function(): void)|*}
 */
export const socketSendComment = (comment) => {
  return () => {
    socket.emit('sendComment', comment);
  };
};

/**
 * @param comment
 * @return {(function(): void)|*}
 */
export const socketUpdateComment = (comment) => {
  return () => {
    socket.emit('updateComment', comment);
  };
};

/**
 * @param id
 * @return {(function(): void)|*}
 */
export const socketDeleteComment = (id) => {
  return () => {
    socket.emit('deleteComment', id);
  };
};

/**
 * @param id
 * @param skinTone
 * @return {(function(): Promise<void>)|*}
 */
export const socketUpdateSkinTone = (id, skinTone) => {
  return async () => {
    socket.emit('updateSkinTone', { id, skinTone });
  };
};

/**
 * @param {string} html
 */
export const socketHTML = (html) => {
  return () => {
    socket.emit('html', html);
  };
};

const actions = {
  connect: socketConnect,
  subNotifications: socketSubNotifications,
  updateSkinTone: socketUpdateSkinTone,
};

/**
 * @returns {{}}
 */
export const useSocketActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
