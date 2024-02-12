import cloneDeep from 'clone-deep';
import { iFrameSrc } from 'utils/browser';
import HTMLUtils from './HTMLUtils';

const defaultSettings = {
  undoAction:  'UNDO',
  redoAction:  'REDO',
  htmlAction:  'HTML',
  clearAction: 'CLEAR_UNDO',
  maxLength:   30,
  include:     [],
  filter:      () => {
    return true;
  }
};

class UndoController {
  htmlHistory = [];

  /**
   * @param initialState
   * @param handlers
   * @param config
   * @returns {(function(*=, *=): (T))|*}
   */
  createUndoReducer = (initialState, handlers, config) => {
    this.settings = {
      ...defaultSettings,
      ...config,
    };

    const initial = cloneDeep(initialState);
    if (initialState.history === undefined) {
      initialState.history = [];
    }
    if (initialState.future === undefined) {
      initialState.future = [];
    }

    return (state = initial, action = {}) => {
      if (action.type === this.settings.clearAction) {
        return {
          ...state,
          history: [],
          future:  [],
        };
      }
      if (action.type === this.settings.undoAction) {
        if (state.history.length > 0) {
          return this.handleUndo(state);
        }
        return state;
      }
      if (action.type === this.settings.redoAction) {
        if (state.future.length > 0) {
          return this.handleRedo(state);
        }
        return state;
      }
      if (action.type === this.settings.htmlAction) {
        this.handlePushHtml(state, action);
      } else if (this.settings.include.indexOf(action.type) !== -1 && this.settings.filter(action)) {
        this.handlePushAction(state);
      }

      if (handlers[action.type]) {
        return handlers[action.type].call(null, state, action);
      }

      return state;
    };
  };

  /**
   * @param state
   * @returns {T|*}
   */
  handleUndo = (state) => {
    const history = Array.from(state.history);
    const future = Array.from(state.future);

    future.push({ ...state });
    if (future.length > this.settings.maxLength) {
      future.shift();
    }
    const prev = history.pop();
    if (prev.__html) {
      return this.handlePopHtml(state, history, future, prev, 'undo');
    }

    prev.history = history;
    prev.future = future;

    return prev;
  };

  /**
   * @param state
   * @returns {unknown}
   */
  handleRedo = (state) => {
    const history = Array.from(state.history);
    const future = Array.from(state.future);

    history.push({ ...state });
    if (history.length > this.settings.maxLength) {
      history.shift();
    }
    const next = future.pop();
    if (next.__html) {
      return this.handlePopHtml(state, history, future, next, 'redo');
    }

    next.history = history;
    next.future = future;

    return next;
  };

  /**
   * @param state
   * @param history
   * @param future
   * @param popped
   * @param type
   * @returns {*}
   */
  handlePopHtml = (state, history, future, popped, type) => {
    state.history = history;
    state.future = future;

    const block = state.blocks.getByID(popped.__blockID);
    if (block) {
      if (
        type === 'undo'
        && popped.__html === block.element.innerHTML
        && JSON.stringify(popped.__attribs) === JSON.stringify(HTMLUtils.getAttributes(block.element))
        && history.length !== 0
      ) {
        popped = history.pop();
        state.history = history;
      } else if (
        type === 'redo'
        && popped.__html === block.element.innerHTML
        && JSON.stringify(popped.__attribs) === JSON.stringify(HTMLUtils.getAttributes(block.element))
        && future.length !== 0
      ) {
        popped = future.pop();
        state.future = future;
      }

      if (popped.__html) {
        block.element.innerHTML = popped.__html;
        Object.keys(popped.__attribs).forEach((key) => {
          block.element.setAttribute(key, popped.__attribs[key]);
        });
      } else {
        // state.history = history;
      }
    }

    state.html = iFrameSrc(state.iframe);

    return state;
  };

  /**
   * @param state
   * @param action
   */
  handlePushHtml = (state, action) => {
    const history = Array.from(state.history);
    if (
      history.length === 0
      || history[history.length - 1].__html === undefined
      || history[history.length - 1].__html !== action.html
    ) {
      history.push({
        __html:    action.html,
        __attribs: { ...action.attribs },
        __blockID: action.blockID
      });
      state.history = history;
      state.future = [];
    }
  };

  /**
   * @param state
   */
  handlePushAction = (state) => {
    const history = Array.from(state.history);
    const curr = { ...state };
    history.push(curr);
    if (history.length > this.settings.maxLength) {
      history.shift();
    }
    state.history = history;
    state.future = [];
  };
}

export default new UndoController();
