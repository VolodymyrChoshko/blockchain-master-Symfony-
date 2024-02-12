import { createReducer } from 'utils';
import * as types from '../actions/mediaActions';

const initialState = {
  isCropping: false,
  cropData:   null
};

/**
 * @param {*} state
 * @param {*} action
 */
const onCrop = (state, action) => {
  const { isCropping, cropData } = action;

  return {
    ...state,
    isCropping,
    cropData
  };
};

const handlers = {
  [types.MEDIA_CROP]: onCrop
};

export default createReducer(initialState, handlers);
