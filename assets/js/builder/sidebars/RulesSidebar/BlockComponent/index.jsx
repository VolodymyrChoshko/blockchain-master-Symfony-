import React, { useEffect, useMemo, useState } from 'react';
import Data from 'builder/engine/Data';
import Button from 'components/Button';
import { useSelector } from 'react-redux';
import Input from 'components/forms/Input';
import Switch from 'components/Switch';
import { useRuleActions } from 'builder/actions/ruleActions';
import * as constants from 'builder/engine/constants';
import Rules from 'builder/engine/Rules';
import { SwitchWrap } from '../styles';


/**
 * @param {string} text
 * @returns {string}
 */
const hyphenate = (text) => {
  text = text.trim().replace(/\s/g, '-').replace(/[^\w\d_-]/g, '').replace(/--+/g, '-');
  let parts = text.split('-');
  if (parts.length > 5) {
    parts = parts.splice(0, 5);
    text = parts.join('-');
  }

  return text;
};

const BlockComponent = () => {
  const activeEdits = useSelector(state => state.rules.activeEdits);
  const ruleActions = useRuleActions();
  const zones = useSelector(state => state.rules.zones);
  const [isDisabled, setDisabled] = useState(false);
  const [values, setValues] = useState({
    section:  false,
    variable: '',
    title:    '',
    bgColor:  false,
  });

  /**
   * @type {Rules}
   */
  const rules = useMemo(() => {
    if (activeEdits.length) {
      return new Rules(activeEdits[0]);
    }

    return null;
  }, [activeEdits]);

  /**
   *
   */
  useEffect(() => {
    return () => ruleActions.setActiveEdit([]);
  }, []);

  /**
   *
   */
  useEffect(() => {
    if (activeEdits.length === 0) {
      return;
    }

    const el       = activeEdits[0];
    const section = el.classList.contains(constants.CLASS_BLOCK_COMPONENT);
    const variable = el.getAttribute(constants.DATA_GROUP) || '';
    const title    = el.getAttribute(constants.DATA_TITLE) || '';
    const bgColor  = el.classList.contains(constants.CLASS_BLOCK_BG_COLOR);

    setValues({
      title,
      section,
      variable,
      bgColor,
    });
  }, [activeEdits, rules]);

  /**
   * @param e
   */
  const handleChange = (e) => {
    const newVars = { ...values };
    newVars[e.target.name] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    if (e.target.name === 'section' && e.target.checked && !values.variable) {
      newVars.variable = hyphenate(activeEdits[0].innerText).toLowerCase();
    }
    if (e.target.name === 'variable') {
      newVars.variable = hyphenate(newVars.variable);
    }
    setValues(newVars);
  };

  /**
   *
   */
  const handleUpdateClick = () => {
    setDisabled(true);
    activeEdits[0].classList[values.section ? 'add' : 'remove'](constants.CLASS_BLOCK_COMPONENT);
    activeEdits[0].classList[values.bgColor ? 'add' : 'remove'](constants.CLASS_BLOCK_BG_COLOR);
    if (values.section && values.variable) {
      activeEdits[0].setAttribute(constants.DATA_GROUP, values.variable);
    } else {
      activeEdits[0].removeAttribute(constants.DATA_GROUP);
    }
    if (values.title) {
      activeEdits[0].setAttribute(constants.DATA_TITLE, values.title);
    } else {
      activeEdits[0].removeAttribute(constants.DATA_TITLE);
    }

    const id = Data.get(activeEdits[0], constants.BLOCK_DATA_ELEMENT_ID);
    Object.keys(zones).forEach((key) => {
      if (key.toString() === id.toString()) {
        zones[key].isComponent = values.section;
      }
    });
    ruleActions.setZones(zones);

    setTimeout(() => {
      setDisabled(false);
      ruleActions.setChanged(true);
    }, 200);
  };

  if (activeEdits.length === 0) {
    return (
      <div>
        <p className="mb-2">
          Enable components as drag and drop standalone elements (ex: titles and buttons).
        </p>
        <img src="/assets/images/components-graphic.png" alt="" style={{ width: '100%' }} />
      </div>
    );
  }

  return (
    <>
      <p className="mb-2">
        Select stackable sections to build an email layout from.
      </p>
      <SwitchWrap className="mb-3">
        <label>Component</label>
        <Switch
          id="input-section"
          name="section"
          checked={values.section}
          onChange={handleChange}
        />
      </SwitchWrap>
      <div className="mb-2">
        <label htmlFor="input-var-name">
          Variable name
        </label>
        <Input
          id="input-var-name"
          name="variable"
          value={values.variable}
          onChange={handleChange}
          disabled={!values.section}
        />
      </div>

      <div className="mb-3">
        <label htmlFor="input-title">
          Title
        </label>
        <Input
          id="input-title"
          name="title"
          value={values.title}
          onChange={handleChange}
          disabled={!values.section}
        />
      </div>

      {/* {activeEdits[0].tagName === 'TD' && (
        <div className="mb-4">
          <Checkbox
            id="input-bg-color"
            label="Allow changing bg color"
            name="bgColor"
            checked={values.bgColor}
            onChange={handleChange}
            disabled={!values.section}
          />
        </div>
      )} */}

      <Button variant="main" onClick={handleUpdateClick} disabled={isDisabled}>
        Update
      </Button>
    </>
  );
};


export default BlockComponent;
