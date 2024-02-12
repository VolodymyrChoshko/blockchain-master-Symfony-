import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import { useUIActions } from 'builder/actions/uiActions';
import { Input } from 'components/forms';
import Icon from 'components/Icon';
import Button from 'components/Button';
import { TemplateSelect, OptionButton } from './styles';

const TemplateSelector = ({ template, templates, initialValue, initialRenaming, onChange }) => {
  const uiActions = useUIActions();
  const templateActions = useTemplateActions();
  const [isRenaming, setRenaming] = useState(initialRenaming);
  const [renameValue, setRenameValue] = useState('');
  const titleRef = useRef(null);

  /**
   *
   */
  useEffect(() => {
    if (template) {
      setRenameValue(template.title);
    }
  }, [template]);

  /**
   *
   */
  useEffect(() => {
    setRenaming(initialRenaming);
    if (initialRenaming) {
      setTimeout(() => {
        titleRef.current.focus();
      }, 500);
    }
  }, [initialRenaming]);

  /**
   *
   */
  const handleRenameClick = () => {
    if (template) {
      if (!isRenaming) {
        setRenameValue(template.title);
      }
      setRenaming(!isRenaming);
    }
  };

  /**
   *
   */
  const handleRenameSaveClick = () => {
    const title = renameValue.trim();
    if (!title) {
      uiActions.alert('Error', 'The template needs a title.');
      return;
    }
    if (template) {
      templateActions.updateTemplate(template.id, title);
      setTimeout(() => {
        setRenaming(false);
      }, 500);
    }
  };

  /**
   *
   */
  const handleSettingsClick = () => {
    if (template) {
      uiActions.uiModal('templateSettings', true, {
        id: template.id
      });
    }
  };

  /**
   *
   */
  const handleCancelClick = () => {
    if (template) {
      setRenaming(false);
      if (initialRenaming) {
        templateActions.deleteTemplate(template.id);
      }
    }
  };

  const options = templates.map(t => (
    { value: t.id, label: t.title }
  ));

  return (
    <div className="d-flex align-items-center">
      {isRenaming ? (
        <Input
          innerRef={titleRef}
          id="dashboard-template-select"
          label="Templates."
          className="dashboard mr-2"
          value={renameValue}
          placeholder="Template Name"
          onChange={e => setRenameValue(e.target.value)}
          style={{ width: 300, height: 43 }}
        />
      ) : (
        <TemplateSelect
          id="dashboard-template-select"
          label="Templates."
          className="dashboard mr-2"
          minWidth={300}
          initialValue={initialValue}
          options={options}
          onChange={onChange}
        />
      )}
      {isRenaming ? (
        <>
          <Button variant="main" className="mr-1" type="button" onClick={handleRenameSaveClick}>
            Save
          </Button>
          <Button variant="alt" type="button" onClick={handleCancelClick}>
            Cancel
          </Button>
        </>
      ) : (
        <>
          <OptionButton variant="transparent" onClick={handleRenameClick}>
            <Icon name="be-symbol-edit" />
          </OptionButton>
          <OptionButton variant="transparent" onClick={handleSettingsClick}>
            <Icon name="be-symbol-preferences" />
          </OptionButton>
        </>
      )}
    </div>
  );
};

TemplateSelector.propTypes = {
  template:        PropTypes.object,
  templates:       PropTypes.array.isRequired,
  initialValue:    PropTypes.number.isRequired,
  initialRenaming: PropTypes.bool,
  onChange:        PropTypes.func.isRequired
};

export default TemplateSelector;
