import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const Select = ({ id, name, options, value, className, ...props }) => {
  return (
    <select id={id} name={name || id} value={value} className={classNames('form-control', className)} {...props}>
      {Array.isArray(options) ? (
        options.map(item => (
          <option key={item.value} value={item.value}>
            {item.label}
          </option>
        ))
      ) : (
        Object.keys(options).map(key => (
          <option key={key} value={key}>
            {options[key]}
          </option>
        ))
      )}
    </select>
  );
};

Select.propTypes = {
  id:        PropTypes.string.isRequired,
  name:      PropTypes.string,
  value:     PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  options:   PropTypes.oneOfType([PropTypes.array, PropTypes.object]).isRequired,
  className: PropTypes.string
};

Select.defaultProps = {
  name:      '',
  value:     '',
  className: ''
};

export default Select;
