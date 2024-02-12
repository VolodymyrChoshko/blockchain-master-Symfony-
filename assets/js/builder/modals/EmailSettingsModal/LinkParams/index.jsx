import api from 'lib/api';
import router from 'lib/router';
import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import Flex from 'components/Flex';
import { Input } from 'components/forms';
import Switch from 'components/Switch';
import { useSelector } from 'react-redux';
import { loading } from 'utils';
import browser from 'utils/browser';

const LinkParams = ({ id, onUpdate, closeModal }) => {
  const formRef = useRef(null);
  const tpaEnabled = useSelector(state => state.builder.tpaEnabled);
  const tmpAliasEnabled = useSelector(state => state.builder.tmpAliasEnabled);
  const templateLinkParams = useSelector(state => state.builder.templateLinkParams);
  const emailLinkParams = useSelector(state => state.builder.emailLinkParams);
  const epaEnabledState = useSelector(state => state.builder.epaEnabled);
  const emaAliasEnabledState = useSelector(state => state.builder.emaAliasEnabled);
  const emailVersion = useSelector(state => state.builder.emailVersion);
  const [params, setParams] = useState({});
  const [epaEnabled, setEpaEnabled] = useState(epaEnabledState);
  const [emaAliasEnabled, setEmaAliasEnabled] = useState(emaAliasEnabledState);

  /**
   * @param formValues
   */
  const handleUpdate = (formValues = {}) => {
    const sanitized = {
      emailLinkParams: {},
      epaEnabled,
      emaAliasEnabled,
    };
    Object.keys(formValues).forEach((key) => {
      if (key.indexOf('tpa_') === 0) {
        sanitized.emailLinkParams[key.substr(4)] = formValues[key];
      }
    });

    loading(true, false);
    const url = `${router.generate('build_email_settings', { id })}?version=${emailVersion}`;
    api.post(url, sanitized)
      .then(() => {
        if (onUpdate) {
          onUpdate(sanitized);
        }
        closeModal();
      })
      .finally(() => {
        loading(false);
      });
  };

  /**
   * @param {Event} e
   */
  const handleModalSubmit = (e) => {
    e.preventDefault();
    handleUpdate(browser.serializeForm(formRef.current));
  };

  /**
   *
   */
  useEffect(() => {
    if (templateLinkParams && templateLinkParams.length > 0) {
      const newParams = {};
      templateLinkParams.forEach((tpa) => {
        newParams[tpa] = emailLinkParams[tpa] || '';
      });
      setParams(newParams);
    }
  }, [templateLinkParams, emailLinkParams]);

  /**
   *
   */
  useEffect(() => {
    setEpaEnabled(epaEnabledState);
    setEmaAliasEnabled(emaAliasEnabledState);
  }, [epaEnabledState, emaAliasEnabledState]);

  /**
   *
   */
  useEffect(() => {
    document.addEventListener('emailSettingsModalSubmit.parameters', handleModalSubmit, false);

    return () => document.removeEventListener('emailSettingsModalSubmit.parameters', handleModalSubmit);
  }, [epaEnabled, emaAliasEnabled]);

  /**
   * @param e
   * @param tpa
   */
  const handleChange = (e, tpa) => {
    const newParams = { ...params };
    newParams[tpa] = e.target.value;
    setParams(newParams);
  };

  if (!tpaEnabled && !tmpAliasEnabled) {
    return null;
  }

  return (
    <form ref={formRef}>
      <Flex>
        <Flex className="mb-3 mr-3" alignCenter>
          <label className="mr-2">Enable Parameters</label>
          <Switch
            id="params-enabled"
            name="epaEnabled"
            checked={tpaEnabled && epaEnabled}
            disabled={!tpaEnabled}
            onChange={e => setEpaEnabled(e.target.checked)}
          />
        </Flex>
        <Flex className="mb-3" alignCenter>
          <label className="mr-2">Enable Alias Tag</label>
          <Switch
            id="alias-enabled"
            name="emaAliasEnabled"
            checked={tmpAliasEnabled && emaAliasEnabled}
            disabled={!tmpAliasEnabled}
            onChange={e => setEmaAliasEnabled(e.target.checked)}
          />
        </Flex>
      </Flex>

      <table>
        <tbody>
          {templateLinkParams.map(tpa => (
            <tr key={tpa}>
              <td className="pr-2 pb-1 pt-1" style={{ verticalAlign: 'middle' }}>
                <label htmlFor={`email-settings-input-tpa-${tpa}`}>
                  {tpa}
                </label>
              </td>
              <td className="pb-1 pt-1" style={{ verticalAlign: 'middle' }}>
                <Input
                  name={`tpa_${tpa}`}
                  value={params[tpa] || ''}
                  id={`email-settings-input-tpa-${tpa}`}
                  onChange={e => handleChange(e, tpa)}
                  disabled={!tpaEnabled || !epaEnabled}
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </form>
  );
};

LinkParams.propTypes = {
  id:         PropTypes.number,
  onUpdate:   PropTypes.func,
  closeModal: PropTypes.func,
};

export default LinkParams;
