import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import Switch from 'components/Switch';
import Flex from 'components/Flex';
import Widget from 'components/forms/Widget';
import Input from 'components/forms/Input';
import Button from 'components/Button';
import Icon from 'components/Icon';

const ParameterSettings = ({ settings, dispatcher, visible, onEnabled }) => {
  const [value, setValue] = useState('');
  const [enabled, setEnabled] = useState(false);
  const [tmpAliasEnabled, setTmpAliasEnabled] = useState(false);
  const [params, setParams] = useState([]);
  const [error, setError] = useState('');
  const inputRef = useRef(null);

  useEffect(() => {
    if (settings) {
      setParams(settings.parameters);
      setEnabled(settings.tpaEnabled);
      setTmpAliasEnabled(settings.tmpAliasEnabled);
      onEnabled(settings.tpaEnabled);
    }
  }, [settings]);

  /**
   *
   */
  const handleSave = () => {
    dispatcher.trigger('saved-params', {
      parameters: params,
      tpaEnabled: enabled,
      tmpAliasEnabled
    });
  };

  /**
   *
   */
  useEffect(() => {
    return dispatcher.on('save', handleSave);
  }, [params, enabled, tmpAliasEnabled]);

  /**
   *
   */
  const handleAddClick = () => {
    const param = value.trim();
    if (param) {
      /* if (param.indexOf(' ') !== -1) {
        setError('Cannot contain spaces.');
        return;
      } */

      let found = false;
      params.forEach((p) => {
        if (p === param) {
          found = true;
        }
      });
      if (!found) {
        const newParams = Array.from(params);
        newParams.push(param);
        setParams(newParams);
      }
      setValue('');
      setError('');
      inputRef.current.focus();
    }
  };

  /**
   * @param {KeyboardEvent} e
   */
  const handleKeyUp = (e) => {
    if (e.key === 'Enter') {
      handleAddClick();
    }
  };

  /**
   * @param {MouseEvent} e
   * @param {string} param
   */
  const handleRemoveClick = (e, param) => {
    if (!enabled) {
      return;
    }

    const newParams = Array.from(params);
    const index = newParams.indexOf(param);
    if (index !== -1) {
      newParams.splice(index, 1);
      setParams(newParams);
    }
  };

  if (!visible) {
    return null;
  }

  return (
    <div>
      <Flex>
        <Flex className="mb-3 mr-3" alignCenter>
          <label className="mr-2">Enable Parameters</label>
          <Switch
            id="params-enabled"
            name="params-enabled"
            checked={enabled}
            onChange={e => {
              setEnabled(e.target.checked);
              onEnabled(e.target.checked);
            }}
          />
        </Flex>
        <Flex className="mb-3" alignCenter>
          <label className="mr-2">Enable Alias Tag</label>
          <Switch
            id="alias-enabled"
            name="alias-enabled"
            checked={tmpAliasEnabled}
            onChange={e => setTmpAliasEnabled(e.target.checked)}
          />
        </Flex>
      </Flex>
      <Widget label="Link Parameter" htmlFor="input-link-param" error={error !== ''}>
        {error && (
          <div className="text-danger">
            {error}
          </div>
        )}
        <Flex>
          <Input
            id="input-link-param"
            value={value}
            className="mr-1"
            innerRef={inputRef}
            onChange={e => setValue(e.target.value)}
            onKeyUp={handleKeyUp}
            disabled={!enabled}
          />
          <Button variant="alt" onClick={handleAddClick} disabled={!enabled}>
            Add
          </Button>
        </Flex>
      </Widget>
      <ul className="modal-template-settings-link-param-list">
        {params.map(param => (
          <li key={param} className={enabled ? '' : 'disabled'}>
            <Icon
              name="be-symbol-delete"
              className="mr-1"
              title="Remove"
              onClick={e => handleRemoveClick(e, param)}
            />
            {param}
          </li>
        ))}
      </ul>
    </div>
  );
};

ParameterSettings.propTypes = {
  settings:   PropTypes.object,
  dispatcher: PropTypes.object.isRequired,
  visible:    PropTypes.bool.isRequired,
  onEnabled:  PropTypes.func.isRequired,
};

export default ParameterSettings;
