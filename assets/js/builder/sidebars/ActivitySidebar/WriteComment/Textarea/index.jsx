import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import ContentEditable from 'react-contenteditable';

const Textarea = ({ value, expanded, shrinking, expandedComplete, innerRef, onChange, onClick, onBlur, onKeyDown }) => {
  return (
    <ContentEditable
      className={classNames('form-control', { expanded, shrinking, 'expanded-complete': expandedComplete })}
      html={value}
      innerRef={innerRef}
      onChange={onChange}
      onClick={onClick}
      onKeyDown={onKeyDown}
      onBlur={onBlur}
    />
  );
};

Textarea.propTypes = {
  value:     PropTypes.string.isRequired,
  innerRef:  PropTypes.object.isRequired,
  onClick:   PropTypes.func.isRequired,
  onChange:  PropTypes.func.isRequired,
  onKeyDown: PropTypes.func.isRequired,
  onBlur:    PropTypes.func.isRequired,
  expanded:  PropTypes.bool.isRequired,
  shrinking: PropTypes.bool.isRequired,
  expandedComplete: PropTypes.bool.isRequired,
};

Textarea.defaultProps = {};

export default Textarea;
