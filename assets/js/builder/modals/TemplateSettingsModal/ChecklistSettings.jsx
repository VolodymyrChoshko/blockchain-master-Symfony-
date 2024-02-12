import React, { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import PropTypes from 'prop-types';
import Switch from 'components/Switch';
import { Scrollbars } from 'react-custom-scrollbars';
import renderScrollbars from 'utils/scrollbars';
import { SwitchWrap } from './styles';
import ChecklistItem from './ChecklistItem';

// @see src/Repository/EmailRepository.php
const ChecklistSettings = ({ settings, tpaEnabled, dispatcher, visible }) => {
  const user = useSelector(state => state.users.me);
  const checklistSettings = settings && settings.checklistSettings ? settings.checklistSettings : {};
  let checklistSettingsCopy;
  let hasBeenEditedFlag = false;

  function compareSettings(object1, object2) {
    object1 = JSON.stringify(object1);
    object2 = JSON.stringify(object2);
    if (JSON.stringify(object1) !== JSON.stringify(object2)) {
      return false;
    }
    return true;
  }

  const [values, setValues] = useState({
    enabled: checklistSettings.enabled || false,
    altText: checklistSettings.altText || false,
    links: checklistSettings.links || false,
    trackingParams: checklistSettings.trackingParams || false,
    previewText: checklistSettings.previewText || false,
  });
  const [items, setItems] = useState([{
    id: 0,
    title: '',
    description: '',
  }]);

  /**
   *
   */
  useEffect(() => {
    if (JSON.stringify(checklistSettings) !== '{}') {
      setValues(checklistSettings);
      checklistSettingsCopy = JSON.parse(JSON.stringify(checklistSettings));
    }
    if (checklistSettings.items) {
      const newItems = Array.from(checklistSettings.items);
      newItems.push({
        id:          0,
        title:       '',
        description: '',
      });
      setItems(newItems);
    }
  }, [checklistSettings]);

  /**
   *
   */
  useEffect(() => {
    return dispatcher.on('save', () => {
      if (!compareSettings(values, checklistSettingsCopy)) {
        hasBeenEditedFlag = true;
      }
      const body = {
        checklistItems: items.filter(v => v.title),
        checklistSettings: values,
        userID: user.id,
        editedTemplateSettings: hasBeenEditedFlag
      };
      dispatcher.trigger('saved-checklist', body);
    });
  }, [values, items]);

  /**
   *
   */
  useEffect(() => {
    if (!tpaEnabled) {
      const newValues = { ...values };
      newValues.trackingParams = false;
      setValues(newValues);
    }
  }, [tpaEnabled]);

  /**
   * @param e
   */
  const handleValueChange = (e) => {
    const newVars = { ...values };
    newVars[e.target.name] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    setValues(newVars);
  };

  /**
   * @param e
   * @param id
   */
  const handleTitleChange = (e, id) => {
    const index = items.findIndex(i => i.id === id);
    if (index !== -1) {
      const newItems = Array.from(items);
      newItems[index].title = e.target.value;
      setItems(newItems);
    }
  };

  /**
   * @param e
   * @param id
   */
  const handleDescriptionChange = (e, id) => {
    const index = items.findIndex(i => i.id === id);
    if (index !== -1) {
      const newItems = Array.from(items);
      newItems[index].description = e.target.value;
      setItems(newItems);
    }
  };

  /**
   *
   */
  const handleAdd = () => {
    const newItems = Array.from(items);
    const id = newItems.length;
    let found = false;

    for (let i = 0; i < newItems.length; i++) {
      if (newItems[i].id === 0 && newItems[i].title !== '') {
        newItems[i].id = id;
        found = true;
      }
    }
    newItems.push({
      id: 0,
      title: '',
      description: '',
    });
    if (found) {
      setItems(newItems);
    }
  };

  /**
   * @param e
   * @param id
   */
  const handleRemove = (e, id) => {
    const newItems = Array.from(items);
    const index = newItems.findIndex((i) => i.id === id);
    if (index !== -1) {
      newItems.splice(index, 1);
      setItems(newItems);
    }
  };

  if (!visible) {
    return null;
  }

  let tabIndex = 100;

  return (
    <Scrollbars
      autoHide
      style={{ maxHeight: 500, minHeight: 400 }}
      renderTrackHorizontal={renderScrollbars.renderTrackHorizontal}
      renderThumbHorizontal={renderScrollbars.renderThumbHorizontal}
    >
      <div className="mb-3">
        Important reminders for your emails. Changes you make to items apply to newly created
        emails moving forward and will not change any current email&apos;s items.
      </div>
      <SwitchWrap className="mb-3 d-flex align-items-center justify-content-start no-underline">
        <label className="mr-2">Enable checklist of items</label>
        <Switch
          id="input-checklist"
          name="enabled"
          checked={values.enabled}
          onChange={handleValueChange}
        />
      </SwitchWrap>

      <SwitchWrap className="mb-2">
        <div className="d-flex align-items-center justify-content-between">
          <label>Alt text on images</label>
          <Switch
            id="input-alt-text-images"
            name="altText"
            checked={values.altText}
            onChange={handleValueChange}
            disabled={!values.enabled}
          />
        </div>
        <small>
          Images should have necessary descriptive text.
        </small>
      </SwitchWrap>

      <SwitchWrap className="mb-2">
        <div className="d-flex align-items-center justify-content-between">
          <label>Links</label>
          <Switch
            id="input-links"
            name="links"
            checked={values.links}
            onChange={handleValueChange}
            disabled={!values.enabled}
          />
        </div>
        <small>
          Check for blank links and test for broken links.
        </small>
      </SwitchWrap>

      <SwitchWrap className="mb-2">
        <div className="d-flex align-items-center justify-content-between">
          <label>Tracking parameters on links</label>
          <Switch
            id="input-link-tracking-params"
            name="trackingParams"
            checked={values.trackingParams}
            onChange={handleValueChange}
            disabled={!values.enabled || !tpaEnabled}
          />
        </div>
        <small>
          Links should have referral parameters added.
        </small>
      </SwitchWrap>

      {/* <SwitchWrap className="mb-2">
        <label>Subject and preview text</label>
        <Switch
          id="input-preview-text"
          name="previewText"
          checked={values.previewText}
          onChange={handleValueChange}
          disabled={!values.enabled}
        />
      </SwitchWrap> */}

      {items.map(item => (
        <ChecklistItem
          key={item.id}
          item={item}
          onAdd={handleAdd}
          onRemove={handleRemove}
          onTitleChange={handleTitleChange}
          onDescriptionChange={handleDescriptionChange}
          disabled={!values.enabled}
          tabIndex={tabIndex += 2}
        />
      ))}
    </Scrollbars>
  );
};

ChecklistSettings.propTypes = {
  settings:   PropTypes.object,
  tpaEnabled: PropTypes.bool.isRequired,
  dispatcher: PropTypes.object.isRequired,
  visible:    PropTypes.bool.isRequired
};

export default ChecklistSettings;
