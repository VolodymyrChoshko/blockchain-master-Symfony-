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
import { Checkboxes } from './styles';

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

/**
 * @param {HTMLElement} el
 */
const containsLinks = (el) => {
  if (el.childNodes) {
    for (let i = 0; i < el.childNodes.length; i++) {
      if (el.childNodes[i].nodeName && el.childNodes[i].nodeName === 'A') {
        return true;
      }
    }
  }

  return false;
};

/**
 * @param {HTMLElement} el
 */
const getLinkStyles = (el) => {
  if (el.children) {
    for (let i = 0; i < el.children.length; i++) {
      if (el.children[i].tagName === 'A' && el.children[i].getAttribute('data-style')) {
        return el.children[i].getAttribute('data-style');
      }
    }
  }

  return '';
};

const BlockEdit = () => {
  const activeEdits = useSelector(state => state.rules.activeEdits);
  const ruleActions = useRuleActions();
  const zones = useSelector(state => state.rules.zones);
  const [isDisabled, setDisabled] = useState(false);
  const [values, setValues] = useState({
    editable:       false,
    variable:       '',
    allowBold:      true,
    allowItalic:    true,
    allowLink:      true,
    autoHeight:     true,
    canResize:      true,
    canRemove:      true,
    canText:        true,
    canSuperscript: false,
    canSubscript:   false,
    linkStyles:     false,
    linkVariable:   '',
    minChars:       '',
    maxChars:       '',
    minWidth:       '',
    maxWidth:       '',
    minHeight:      '',
    maxHeight:      '',
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

    const el = activeEdits[0];
    const editable = el.classList.contains('block-edit');
    let variable = el.getAttribute(constants.DATA_BLOCK) || '';
    if (!editable && !variable) {
      if (el.tagName === 'IMG') {
        variable = `image-${Data.get(el, constants.BLOCK_DATA_IMG_ID)}`;
      } else {
        variable = hyphenate(el.innerText).toLowerCase();
      }
    }

    setValues({
      editable,
      variable,
      allowBold:      rules.canBold,
      allowItalic:    rules.canItalic,
      allowLink:      rules.canLink,
      canRemove:      rules.canRemove,
      canSuperscript: rules.canSubscript,
      canSubscript:   rules.canSubscript,
      minChars:       rules.minChars === 0 ? '' : rules.minChars.toString(),
      maxChars:       rules.maxChars === 0 ? '' : rules.maxChars.toString(),
      minHeight:      rules.minHeight === 0 ? '' : rules.minHeight.toString(),
      maxHeight:      rules.maxHeight === 0 ? '' : rules.maxHeight.toString(),
      minWidth:       rules.minWidth === 0 ? '' : rules.minWidth.toString(),
      maxWidth:       rules.maxWidth === 0 ? '' : rules.maxWidth.toString(),
      autoHeight:     rules.isAutoHeight,
      canResize:      rules.canResize,
      canText:        rules.canText,
      linkStyles:     !!getLinkStyles(el),
      linkVariable:   getLinkStyles(el),
    });
  }, [activeEdits, rules]);

  /**
   * @param e
   */
  const handleChange = (e) => {
    const el = activeEdits[0];

    const newVars = { ...values };
    newVars[e.target.name] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    if (e.target.name === 'editable' && e.target.checked && !values.variable) {
      if (el.tagName === 'IMG') {
        newVars.variable = `image-${Data.get(el, constants.BLOCK_DATA_IMG_ID)}`;
      } else {
        newVars.variable = hyphenate(activeEdits[0].innerText).toLowerCase();
      }
    }
    if (e.target.name === 'variable') {
      newVars.variable = hyphenate(newVars.variable);
    }
    if (e.target.name === 'canText') {
      newVars.canText = !newVars.canText;
    }
    setValues(newVars);
  };

  /**
   * @param e
   */
  const handleLinkStyleChange = (e) => {
    const newVars = { ...values };
    if (e.target.name === 'linkStyles') {
      newVars.linkStyles = e.target.checked;
    } else if (e.target.name === 'linkVariable') {
      newVars.linkVariable = e.target.value;
    }

    setValues(newVars);
  };

  /**
   *
   */
  const handleUpdateClick = () => {
    setDisabled(true);
    const el = activeEdits[0];

    el.classList[values.editable ? 'add' : 'remove'](constants.CLASS_BLOCK_EDIT);
    if (values.editable && values.variable) {
      el.setAttribute(constants.DATA_BLOCK, values.variable);
    } else {
      el.removeAttribute(constants.DATA_BLOCK);
    }

    rules.canBold = values.editable ? values.allowBold : true;
    rules.canItalic = values.editable ? values.allowItalic : true;
    rules.canLink = values.editable ? values.allowLink : true;
    rules.minChars = values.editable ? values.minChars : 0;
    rules.maxChars = values.editable ? values.maxChars : 0;
    rules.minWidth = values.editable ? values.minWidth : 0;
    rules.maxWidth = values.editable ? values.maxWidth : 0;
    rules.minHeight = values.editable ? values.minHeight : 0;
    rules.maxHeight = values.editable ? values.maxHeight : 0;
    rules.isAutoHeight = values.editable ? values.autoHeight : false;
    rules.canResize = values.editable ? values.canResize : false;
    rules.canRemove = values.editable ? values.canRemove : false;
    rules.canText = values.editable ? values.canText : true;
    rules.canSuperscript = values.editable ? values.canSuperscript : false;
    rules.canSubscript = values.editable ? values.canSubscript : false;

    if (values.linkStyles) {
      for (let i = 0; i < el.children.length; i++) {
        if (el.children[i].tagName && el.children[i].tagName === 'A') {
          if (values.linkVariable) {
            el.children[i].setAttribute(constants.DATA_STYLE, values.linkVariable);
          } else {
            el.children[i].removeAttribute(constants.DATA_STYLE);
          }
        }
      }
    } else {
      for (let i = 0; i < el.children.length; i++) {
        el.children[i].removeAttribute(constants.DATA_STYLE);
      }
    }

    const id = Data.get(activeEdits[0], constants.BLOCK_DATA_ELEMENT_ID);
    Object.keys(zones).forEach((key) => {
      if (key.toString() === id.toString()) {
        zones[key].isEditable = values.editable;
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
      <p className="mb-2">
        Select content pieces to add editing options for them.
      </p>
    );
  }

  return (
    <>
      <p className="mb-2">
        Set editing options for the piece of content.
      </p>
      <SwitchWrap className="mb-3">
        <label>Editable</label>
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

      <Checkboxes className="mb-3">
        {activeEdits[0].tagName !== 'IMG' && (
          <Checkbox
            id="input-allow-bold"
            label="Allow bold"
            name="allowBold"
            checked={values.allowBold}
            onChange={handleChange}
            disabled={!values.editable}
          />
        )}
        {activeEdits[0].tagName !== 'IMG' && (
          <Checkbox
            id="input-allow-italic"
            label="Allow italic"
            name="allowItalic"
            checked={values.allowItalic}
            onChange={handleChange}
            disabled={!values.editable}
          />
        )}
        <Checkbox
          id="input-allow-link"
          label="Allow link"
          name="allowLink"
          checked={values.allowLink}
          onChange={handleChange}
          disabled={!values.editable}
        />
        {activeEdits[0].tagName === 'IMG' && (
          <Checkbox
            id="input-auto-height"
            label="Auto height"
            name="autoHeight"
            checked={values.autoHeight}
            onChange={handleChange}
            disabled={!values.editable}
          />
        )}
        {activeEdits[0].tagName === 'IMG' && (
          <Checkbox
            id="input-resizable"
            label="Resizable"
            name="canResize"
            checked={values.canResize}
            onChange={handleChange}
            disabled={!values.editable}
          />
        )}
        <Checkbox
          id="input-removable"
          label="Can remove"
          name="canRemove"
          checked={values.canRemove}
          onChange={handleChange}
          disabled={!values.editable}
        />
        {activeEdits[0].tagName === 'A' && (
          <Checkbox
            id="input-no-text"
            label="Link only"
            name="canText"
            checked={!values.canText}
            onChange={handleChange}
            disabled={!values.editable}
          />
        )}
        {activeEdits[0].tagName !== 'IMG' && (
          <Checkbox
            id="input-can-superscript"
            label="Allow superscript"
            name="canSuperscript"
            checked={values.canSuperscript}
            onChange={handleChange}
            disabled={!values.editable}
          />
        )}
        {activeEdits[0].tagName !== 'IMG' && (
          <Checkbox
            id="input-can-subscript"
            label="Allow subscript"
            name="canSubscript"
            checked={values.canSubscript}
            onChange={handleChange}
            disabled={!values.editable}
          />
        )}
      </Checkboxes>

      {activeEdits[0].tagName === 'IMG' && values.canResize && (
        <div className="d-flex align-items-center mb-2">
          <div className="mr-2">
            <label htmlFor="input-min-width">
              Minimum width
            </label>
            <Input
              id="input-min-width"
              name="minWidth"
              type="number"
              value={values.minWidth}
              onChange={handleChange}
              disabled={!values.editable}
            />
          </div>
          <div>
            <label htmlFor="input-max-width">
              Maximum width
            </label>
            <Input
              id="input-max-width"
              name="maxWidth"
              type="number"
              value={values.maxWidth}
              onChange={handleChange}
              disabled={!values.editable}
            />
          </div>
        </div>
      )}
      {activeEdits[0].tagName === 'IMG' && values.canResize && (
        <div className="d-flex align-items-center mb-4">
          <div className="mr-2">
            <label htmlFor="input-min-height">
              Minimum height
            </label>
            <Input
              id="input-min-height"
              name="minHeight"
              type="number"
              value={values.minHeight}
              onChange={handleChange}
              disabled={!values.editable}
            />
          </div>
          <div>
            <label htmlFor="input-max-height">
              Maximum height
            </label>
            <Input
              id="input-max-height"
              name="maxHeight"
              type="number"
              value={values.maxHeight}
              onChange={handleChange}
              disabled={!values.editable}
            />
          </div>
        </div>
      )}

      {activeEdits[0].tagName !== 'IMG' && (
        <div className="d-flex align-items-center mb-4">
          <div className="mr-2">
            <label htmlFor="input-min-chars">
              Minimum characters
            </label>
            <Input
              id="input-min-chars"
              name="minChars"
              type="number"
              value={values.minChars}
              onChange={handleChange}
              disabled={!values.editable}
            />
          </div>
          <div>
            <label htmlFor="input-max-chars">
              Maximum characters
            </label>
            <Input
              id="input-max-chars"
              name="maxChars"
              type="number"
              value={values.maxChars}
              onChange={handleChange}
              disabled={!values.editable}
            />
          </div>
        </div>
      )}

      {containsLinks(activeEdits[0]) && (
        <div className="mt-4 mb-4">
          <Checkbox
            id="input-link-styles"
            label="Enable link style"
            name="linkStyles"
            className="mb-2"
            checked={values.linkStyles}
            onChange={handleLinkStyleChange}
          />

          <div className="mb-3">
            <label htmlFor="input-link-variable">
              Variable name
            </label>
            <Input
              id="input-link-variable"
              name="linkVariable"
              value={values.linkVariable}
              onChange={handleLinkStyleChange}
              disabled={!values.editable || !values.linkStyles}
            />
          </div>
        </div>
      )}

      <Button variant="main" onClick={handleUpdateClick} disabled={isDisabled}>
        Update
      </Button>
    </>
  );
};

export default BlockEdit;
