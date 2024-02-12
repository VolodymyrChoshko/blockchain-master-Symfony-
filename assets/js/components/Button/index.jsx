import React from 'react';
import PropTypes from 'prop-types';
import { Container } from './styles';

const Button = ({
  as,
  variant,
  lg,
  sm,
  wide,
  active,
  type,
  innerRef,
  disabled,
  className,
  children,
  onClick,
  ...props
}) => {
  return (
    <Container
      as={as}
      ref={innerRef}
      className={className}
      type={type}
      disabled={disabled}
      {...props}
      variant={variant}
      wide={wide}
      sm={sm}
      lg={lg}
      onClick={onClick}
    >
      {children}
    </Container>
  );
};

Button.propTypes = {
  as:        PropTypes.string,
  variant:   PropTypes.oneOf(['main', 'alt', 'edit', 'danger', 'link', 'transparent', 'save', 'darkmode', '']),
  lg:        PropTypes.bool,
  sm:        PropTypes.bool,
  wide:      PropTypes.bool,
  type:      PropTypes.string,
  active:    PropTypes.bool,
  disabled:  PropTypes.bool,
  innerRef:  PropTypes.object,
  className: PropTypes.string,
  children:  PropTypes.node,
  onClick:   PropTypes.func
};

Button.defaultProps = {
  as:        'button',
  variant:   '',
  type:      'button',
  wide:      false,
  lg:        false,
  sm:        false,
  active:    false,
  disabled:  false,
  className: '',
  children:  ''
};

export default Button;
