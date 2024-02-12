import { createStore, applyMiddleware, compose } from 'redux';
import thunk from 'redux-thunk';
import createRootReducer from '../reducers/rootReducer';

// See: https://github.com/zalmoxisus/redux-devtools-extension
const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

let store = null;

export default function configureStore(initialState = {}) {
  if (store !== null) {
    return store;
  }

  store = createStore(
    createRootReducer(),
    initialState,
    composeEnhancers(
      applyMiddleware(thunk)
    )
  );

  return store;
}
