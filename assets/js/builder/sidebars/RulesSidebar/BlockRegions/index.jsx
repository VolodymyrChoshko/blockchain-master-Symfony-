import React, { useEffect, useMemo, useState } from 'react';
import Data from 'builder/engine/Data';
import Rules from 'builder/engine/Rules';
import { useRuleActions } from 'builder/actions/ruleActions';
import * as constants from 'builder/engine/constants';
import Switch from 'components/Switch';
import Checkbox from 'components/forms/Checkbox';
import Input from 'components/forms/Input';
import Button from 'components/Button';
import { useSelector } from 'react-redux';
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

const BlockRegions = () => {
  const activeEdits = useSelector(state => state.rules.activeEdits);
  const ruleActions = useRuleActions();
  const zones = useSelector(state => state.rules.zones);
  const [isDisabled, setDisabled] = useState(false);
  const [values, setValues] = useState({
    editable:  false,
    bgColor:   false,
    variable:  '',
    canRemove: true,
    canRepeat: true,
    maxRepeat: '',
  });

  let varKey = constants.DATA_GROUP;
  if (activeEdits[0] && activeEdits[0].hasAttribute(constants.DATA_BLOCK)) {
    varKey = constants.DATA_BLOCK;
  }

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

    const el = activeEdits[0];
    const editable = el.classList.contains(constants.CLASS_BLOCK_REGION);
    const bgColor  = el.classList.contains(constants.CLASS_BLOCK_BG_COLOR);
    let variable = el.getAttribute(varKey) || '';
    if (!editable && !variable) {
      variable = hyphenate(el.innerText).toLowerCase();
    }

    setValues({
      editable,
      variable,
      bgColor,
      canRemove: rules.canRemove,
      maxRepeat: rules.maxRepeat === 0 ? '' : rules.maxRepeat.toString(),
      canRepeat: rules.canRepeat
    });
  }, [activeEdits, rules]);

  /**
   * @param e
   */
  const handleChange = (e) => {
    const newVars = { ...values };
    newVars[e.target.name] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    if (e.target.name === 'editable' && e.target.checked && !values.variable) {
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
    activeEdits[0].classList[values.editable ? 'add' : 'remove'](constants.CLASS_BLOCK_REGION);
    activeEdits[0].classList[values.bgColor ? 'add' : 'remove'](constants.CLASS_BLOCK_BG_COLOR);

    if (values.editable && values.variable) {
      activeEdits[0].setAttribute(varKey, values.variable);
    } else {
      activeEdits[0].removeAttribute(varKey);
    }

    rules.maxRepeat = values.editable ? values.maxRepeat : 0;
    rules.canRemove = values.editable ? values.canRemove : false;
    rules.canRepeat = values.editable ? values.canRepeat : false;

    const id = Data.get(activeEdits[0], constants.BLOCK_DATA_ELEMENT_ID);
    Object.keys(zones).forEach((key) => {
      if (key.toString() === id.toString()) {
        zones[key].isRegion = values.editable;
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
          Enable regions for groups of content you want to repeat or remove.
        </p>
        <img src="/assets/images/regions-graphic.png" alt="" style={{ width: '100%' }} />
      </div>
    );
  }

  return (
    <>
      <p className="mb-2">
        Set options for this region.
      </p>
      <SwitchWrap className="mb-3">
        <label>Region</label>
        <Switch
          id="input-editable"
          name="editable"
          checked={values.editable}
          onChange={handleChange}
        />
      </SwitchWrap>

      <div className="mb-3">
        <label htmlFor="input-var-name">
          Variable name
        </label>
        <Input
          id="input-var-name"
          name="variable"
          value={values.variable}
          onChange={handleChange}
          disabled={!values.editable}
        />
      </div>

      <div className="d-flex align-items-center justify-content-between flex-wrap mb-2">
        <Checkbox
          id="input-allow-duplicate"
          label="Allow duplicating"
          name="canRepeat"
          checked={values.canRepeat}
          onChange={handleChange}
          disabled={!values.editable}
        />
        <Checkbox
          id="input-removable"
          label="Can be removed"
          name="canRemove"
          checked={values.canRemove}
          onChange={handleChange}
          disabled={!values.editable}
        />
      </div>

      {/* {activeEdits[0].tagName === 'TD' && (
        <div className="mb-2">
          <Checkbox
            id="input-bg-color"
            label="Allow changing bg color"
            name="bgColor"
            checked={values.bgColor}
            onChange={handleChange}
            disabled={!values.editable}
          />
        </div>
      )} */}

      <div className="d-flex align-items-center mb-4">
        <div className="w-50">
          <label htmlFor="input-duplicates-limit">
            Duplicates limit
          </label>
          <Input
            id="input-duplicates-limit"
            name="maxRepeat"
            type="number"
            value={values.maxRepeat}
            onChange={handleChange}
            disabled={!values.editable}
          />
        </div>
      </div>

      <Button variant="main" onClick={handleUpdateClick} disabled={isDisabled}>
        Update
      </Button>
    </>
  );
};

export default BlockRegions;
