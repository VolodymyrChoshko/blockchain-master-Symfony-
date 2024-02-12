import { createReducer } from 'utils';
import { types } from '../actions/billingActions';

const initialState = {
  billingPlan:     null,
  nextInvoice:     null,
  invoices:        [],
  creditCard:      null,
  stripePublicKey: '',
};

/**
 * @param state
 * @param action
 * @returns {*&{billingPlan}}
 */
const onLoad = (state, action) => {
  const { billingPlan, nextInvoice, invoices, creditCard, stripePublicKey } = action;

  return {
    ...state,
    billingPlan,
    nextInvoice,
    invoices,
    creditCard,
    stripePublicKey,
  };
};

/**
 * @param state
 * @returns {*&{creditCard: null}}
 */
const onRemoveCard = (state) => {
  return {
    ...state,
    creditCard: null,
  };
};

const handlers = {
  [types.LOAD]:        onLoad,
  [types.REMOVE_CARD]: onRemoveCard,
};

export default createReducer(initialState, handlers);
