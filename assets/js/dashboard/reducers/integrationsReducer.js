import { createReducer } from 'utils';
import { findIndexByID } from 'utils/arrays';
import { types } from '../actions/integrationsActions';

const initialState = {
  sources:                [],
  integrations:           [],
  integrationPrices:      {},
  isIntegrationsDisabled: false,
  billingPlan:            null,
  nonce:                  '',
};

/**
 * @param state
 * @param action
 * @returns {*&{sources, isIntegrationsDisabled, integrationPrices, integrations}}
 */
const onLoad = (state, action) => {
  const { integrations, sources, integrationPrices, isIntegrationsDisabled, billingPlan, nonce } = action;

  return {
    ...state,
    sources,
    integrations,
    integrationPrices,
    isIntegrationsDisabled,
    billingPlan,
    nonce,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{sources: unknown[]}}
 */
const onRemove = (state, action) => {
  const sources = Array.from(state.sources);
  const { source } = action;

  const index = findIndexByID(sources, source.id);
  if (index !== -1) {
    sources.splice(index, 1);
  }

  return {
    ...state,
    sources,
  };
};

/**
 * @param state
 * @param action
 * @returns {*&{sources: unknown[]}}
 */
const onAdd = (state, action) => {
  const sources = Array.from(state.sources);
  const { source } = action;

  sources.push(source);

  return {
    ...state,
    sources,
  };
};

const handlers = {
  [types.LOAD]:   onLoad,
  [types.REMOVE]: onRemove,
  [types.ADD]:    onAdd,
};

export default createReducer(initialState, handlers);
