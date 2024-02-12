export const USER_INITIAL_STATE = 'USER_INITIAL_STATE';

/**
 * @param is
 * @returns {{initialState, type: string}}
 */
export const userInitialState = is => ({
  type:         USER_INITIAL_STATE,
  initialState: is,
});
