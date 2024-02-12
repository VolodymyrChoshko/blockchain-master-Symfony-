import api from 'lib/api';
import router from 'lib/router';

/**
 * @param {IntegrationSource|IntegrationSource[]} sources
 * @param {string} hook
 * @returns {boolean}
 */
export const hasIntegrationHook = (sources, hook) => {
  let hasHook = false;
  if (!Array.isArray(sources)) {
    sources = [sources];
  }

  for (let i = 0; i < sources.length; i++) {
    const source = sources[i];
    if (source.settings && source.settings.hooks && source.settings.hooks.indexOf(hook) !== -1) {
      hasHook = true;
      break;
    }
  }

  return hasHook;
};

/**
 * @param {IntegrationSource} source
 * @param {string} rule
 * @returns {boolean}
 */
export const hasIntegrationRule = (source, rule) => {
  return !!(source && source.settings && source.settings.rules && source.settings.rules[rule]);
};

/**
 * @param {string} name
 * @param {number} id
 * @param {IntegrationSource|null} source
 * @returns {Promise<*>|Promise}
 */
export const dispatchHook = (name, id, source = null) => {
  const body = {};
  if (source) {
    body.sid = source.id;
  }

  return api.post(router.generate('integrations_email_hook', { id, name }), body);
};
