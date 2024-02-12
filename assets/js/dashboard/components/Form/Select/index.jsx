import React from 'react';
import PropTypes from 'prop-types';
import { Container } from '../Input/styles';

const Select = React.forwardRef(({ id, name, value, values, error, onChange, ...props }, ref) => {
  return (
    <Container
      as="select"
      ref={ref}
      id={id || `input-${name}`}
      name={name}
      value={value}
      onChange={onChange}
      error={error}
      {...props}
    >
      {Object.keys(values).map(key => (
        <option key={key} value={key}>{values[key]}</option>
      ))}
    </Container>
  );
});

Select.propTypes = {
  id:       PropTypes.string,
  name:     PropTypes.string.isRequired,
  value:    PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  values:   PropTypes.object.isRequired,
  error:    PropTypes.bool,
  onChange: PropTypes.func
};

Select.defaultProps = {
  error: false,
};

export default Select;
