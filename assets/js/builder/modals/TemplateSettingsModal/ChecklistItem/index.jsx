import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import Input from 'components/forms/Input';
import Button from 'components/Button';
import Icon from 'components/Icon';
import { SwitchWrap } from '../styles';

const ChecklistItem = ({ item, disabled, tabIndex, onTitleChange, onDescriptionChange, onAdd, onRemove }) => {
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');

  /**
   *
   */
  useEffect(() => {
    if (item) {
      setTitle(item.title);
      setDescription(item.description);
    }
  }, [item]);

  return (
    <SwitchWrap className="d-flex align-items-end justify-content-start mb-2">
      <div className="d-flex flex-column flex-grow-1">
        <div className="d-flex align-items-center justify-content-center mb-1">
          <div style={{ width: 120 }}>
            Title:
          </div>
          <Input
            id="input-title-1"
            name="title"
            value={title}
            onChange={(e) => {
              setTitle(e.target.value);
              onTitleChange(e, item ? item.id : 0);
            }}
            disabled={disabled}
            tabIndex={tabIndex}
          />
        </div>
        <div className="d-flex align-items-center justify-content-center">
          <div style={{ width: 120 }}>
            Description:
          </div>
          <Input
            id="input-description-1"
            name="description"
            value={description}
            onChange={(e) => {
              setDescription(e.target.value);
              onDescriptionChange(e, item ? item.id : 0);
            }}
            disabled={disabled}
            tabIndex={tabIndex + 1}
          />
        </div>
      </div>
      <div className="d-flex align-items-stretch h-100 pl-2" style={{ width: 130 }}>
        {item.id === 0 && (
          <Button
            variant="alt"
            onClick={(e) => {
              if (!disabled) {
                onAdd(e);
              }
            }}
          >
            Add
          </Button>
        )}
        {item.id !== 0 && (
          <Button
            style={{ color: '#000' }}
            onClick={(e) => {
              if (!disabled) {
                onRemove(e, item.id);
              }
            }}
          >
            <Icon name="be-symbol-delete" className="mr-2" />
            Remove
          </Button>
        )}
      </div>
    </SwitchWrap>
  );
};

ChecklistItem.propTypes = {
  item: PropTypes.object,
  disabled: PropTypes.bool.isRequired,
  tabIndex: PropTypes.number.isRequired,
  onTitleChange: PropTypes.func.isRequired,
  onDescriptionChange: PropTypes.func.isRequired,
  onAdd: PropTypes.func.isRequired,
  onRemove: PropTypes.func.isRequired,
};

export default ChecklistItem;
