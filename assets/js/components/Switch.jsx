import React, { forwardRef } from 'react';
import PropTypes from 'prop-types';

const Switch = forwardRef(({ id, name, checked, disabled, onChange }, ref) => {
  return (
    <div className={`onoffswitch ${disabled ? 'disabled' : ''}`}>
      <input
        id={id}
        ref={ref}
        name={name}
        type="checkbox"
        className="onoffswitch-checkbox"
        checked={checked}
        disabled={disabled}
        onChange={e => onChange(e, e.target.checked)}
      />
      <label
        htmlFor={id}
        className="onoffswitch-label pointer"
      >
        <div className="onoffswitch-inner" />
        <div className="onoffswitch-switch" />
      </label>
    </div>
  );
});

Switch.displayName = 'Switch';

Switch.propTypes = {
  id:       PropTypes.string.isRequired,
  name:     PropTypes.string.isRequired,
  disabled: PropTypes.bool,
  onChange: PropTypes.func,
};

Switch.defaultProps = {
  disabled: false,
  onChange: () => {}
};

export default Switch;
