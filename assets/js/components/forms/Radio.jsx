import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const Radio = ({ id, name, label, disabled, className, ...props }) => {
  return (
    <label htmlFor={id} className={classNames('d-flex align-items-center mb-0', className)}>
      <input id={id} type="radio" disabled={disabled} className="mt-0 ml-0" {...props} />
      {label}
    </label>
  );
};

Radio.propTypes = {
  id:        PropTypes.string.isRequired,
  name:      PropTypes.string,
  label:     PropTypes.string,
  className: PropTypes.string,
  disabled:  PropTypes.bool,
};

Radio.defaultProps = {
  name:      '',
  label:     '',
  className: '',
  disabled:  false,
};


export default Radio;
