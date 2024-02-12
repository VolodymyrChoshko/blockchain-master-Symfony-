import React from 'react';
import PropTypes from 'prop-types';
import { Container } from './styles';

const Input = React.forwardRef(({ id, name, type, value, error, onChange, ...props }, ref) => {
  return (
    <Container
      ref={ref}
      type={type}
      id={id || `input-${name}`}
      name={name}
      value={value}
      onChange={onChange}
      error={error}
      {...props}
    />
  );
});

Input.propTypes = {
  id:       PropTypes.string,
  name:     PropTypes.string.isRequired,
  type:     PropTypes.oneOf(['text', 'email', 'password', 'checkbox']),
  value:    PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  error:    PropTypes.bool,
  onChange: PropTypes.func
};

Input.defaultProps = {
  type:  'text',
  error: false,
};

export default Input;
