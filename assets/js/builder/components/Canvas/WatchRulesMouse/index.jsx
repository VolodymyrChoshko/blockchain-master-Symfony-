import { useEffect, useRef } from 'react';
import { useSelector } from 'react-redux';
import browser from 'utils/browser';
import Data from 'builder/engine/Data';
import { BLOCK_DATA_ELEMENT_ID } from 'builder/engine/constants';
import { useRuleActions } from 'builder/actions/ruleActions';

function getParentElement(element) {
  do {
    if (element.classList && Data.get(element, BLOCK_DATA_ELEMENT_ID)) {
      return element;
    }
    element = element.parentNode;
  } while (element);

  return false;
}

const WatchRulesMouse = () => {
  const ruleActions = useRuleActions();
  const iframe = useSelector(state => state.builder.iframe);
  const rules = useSelector(state => state.rules);
  const isChanged = useSelector(state => state.rules.isChanged);
  const isEditingHtml = useSelector(state => state.rules.isEditingHtml);
  const isSaving = useSelector(state => state.rules.isSaving);
  const canvas = useRef();
  const timeout = useRef(0);
  const doc = browser.iFrameDocument(iframe);

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  const handleMove = (pageX, pageY) => {
    if (isSaving) {
      return;
    }

    const el = doc.elementFromPoint(pageX, pageY);
    if (el && Data.get(el, BLOCK_DATA_ELEMENT_ID)) {
      ruleActions.setHoverEdits([el]);
    } else if (el) {
      const parent = getParentElement(el);
      if (parent) {
        ruleActions.setHoverEdits([parent]);
      } else {
        ruleActions.setHoverEdits([]);
      }
    } else {
      ruleActions.setHoverEdits([]);
    }
  };

  /**
   * @param {MouseEvent} e
   */
  const handleFrameMouseMove = (e) => {
    const { pageX, pageY } = e;

    handleMove(pageX, pageY);
  };

  /**
   * @param {MouseEvent} e
   */
  const handleFrameClick = (e) => {
    if (isSaving) {
      return;
    }

    const el = doc.elementFromPoint(e.pageX, e.pageY);
    if (el && el.tagName === 'A') {
      e.preventDefault();
    }

    const active = [];
    if (el && Data.get(el, BLOCK_DATA_ELEMENT_ID)) {
      // ruleActions.setActiveEdit([el]);
      active.push(el);
    } else if (el) {
      const parent = getParentElement(el);
      if (parent) {
        // ruleActions.setActiveEdit([parent]);
        active.push(parent);
      } else {
       // ruleActions.setActiveEdit([]);
      }
    } else {
     // ruleActions.setActiveEdit([]);
     //  ruleActions.setActiveSections([]);
    }

    ruleActions.setActiveEdit(active);
  };

  /**
   * @param e
   */
    // eslint-disable-next-line consistent-return
  const handleBeforeUnload = (e) => {
    console.log(isChanged);
    if (isChanged) {
      e.returnValue = 'Are you sure you want to leave? Changes may not be saved.';
      return 'Are you sure you want to leave? Changes may not be saved.';
    }
  };

  /**
   *
   */
  useEffect(() => {
    if (!doc || isSaving) {
      return () => {};
    }

    timeout.current = setTimeout(() => {
      canvas.current = document.querySelector('.builder-canvas');
      doc.addEventListener('mousemove', handleFrameMouseMove, false);
      doc.addEventListener('mousedown', handleFrameClick, false);
    }, 1000);

    return () => {
      clearTimeout(timeout.current);
      if (doc) {
        doc.removeEventListener('mousemove', handleFrameMouseMove);
        doc.removeEventListener('mousedown', handleFrameClick);
      }
    };
  }, [doc, isSaving, isEditingHtml, rules.isFilteringHtml]);

  /**
   *
   */
  useEffect(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
    };
  }, [isChanged]);

  return null;
};

export default WatchRulesMouse;
