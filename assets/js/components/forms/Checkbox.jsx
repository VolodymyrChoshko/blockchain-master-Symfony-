import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const Checkbox = ({ id, name, label, disabled, className, ...props }) => {
  return (
    <label htmlFor={id} className={classNames('mb-0 d-flex align-items-center', className)} style={{ marginLeft: -4 }}>
      <input id={id} name={name} type="checkbox" disabled={disabled} {...props} />
      {label}
    </label>
  );
};

Checkbox.propTypes = {
  id:        PropTypes.string.isRequired,
  name:      PropTypes.string,
  label:     PropTypes.node,
  className: PropTypes.string,
  disabled:  PropTypes.bool,
};

Checkbox.defaultProps = {
  name:      '',
  label:     '',
  className: '',
  disabled:  false,
};


export default Checkbox;
