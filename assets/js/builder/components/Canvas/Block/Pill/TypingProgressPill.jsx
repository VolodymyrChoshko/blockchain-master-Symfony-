import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import Pill from './Pill';

const TypingProgressPill = ({ block, dimensions }) => {
  const { element }          = block;
  const { maxChars }         = block.rules;
  const [chars, updateChars] = useState(block.element.innerText.length);

  /**
   *
   */
  const handleKeyUp = () => {
    let text = element.innerText;
    if (element.innerHTML.substring(element.innerHTML.length - 6) === '&nbsp;') {
      text = text.substring(0, text.length - 1);
    }
    updateChars(text.length);
  };

  /**
   * @param e
   */
  const handleKeyDown = (e) => {
    let text = element.innerText;
    if (element.innerHTML.substring(element.innerHTML.length - 6) === '&nbsp;') {
      text = text.substring(0, text.length - 1);
    }
    if (text.length >= maxChars && e.keyCode !== 46 && e.keyCode !== 8) {
      e.preventDefault();
    }
  };

  /**
   *
   */
  useEffect(() => {
    handleKeyUp();
  }, []);

  /**
   *
   */
  useEffect(() => {
    element.addEventListener('keyup', handleKeyUp, false);
    element.addEventListener('keydown', handleKeyDown, false);

    return () => {
      element.removeEventListener('keyup', handleKeyUp, false);
      element.removeEventListener('keydown', handleKeyDown, false);
    };
  }, [chars]);

  const percent = Math.floor((chars / maxChars) * 100);
  let classes = 'builder-pill-typing-progress';
  if (percent >= 100) {
    classes += ' builder-pill-typing-progress-chars-100';
  } else if (percent >= 75) {
    classes += ' builder-pill-typing-progress-chars-75';
  } else if (percent >= 50) {
    classes += ' builder-pill-typing-progress-chars-50';
  }

  return (
    <Pill dimensions={dimensions} className={classes}>
      <div className="builder-pill-typing-progress-chars">
        <span>{maxChars - chars} characters left</span>
      </div>
    </Pill>
  );
};

TypingProgressPill.propTypes = {
  block:      PropTypes.object.isRequired,
  dimensions: PropTypes.object.isRequired
};

export default TypingProgressPill;
