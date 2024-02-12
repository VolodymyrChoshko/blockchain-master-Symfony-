import { createReducer } from 'utils';

let initialState = {
  url:  '',
  path: ''
};
if (window.initialState && window.initialState.socket) {
  initialState = window.initialState.socket;
}


const handlers = {};

export default createReducer(initialState, handlers);
