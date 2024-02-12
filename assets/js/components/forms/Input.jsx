import React from 'react';
import PropTypes from 'prop-types';
import { Icon } from 'components';
import classNames from 'classnames';

const Input = ({ id, name, size, icon, style, className, innerRef, ...props }) => {
  if (icon) {
    return (
      <div className={classNames('form-control form-control-w-icon', className)} style={style}>
        <Icon name={icon} />
        <input
          id={id}
          name={name || id}
          size={size}
          ref={innerRef}
          {...props}
        />
      </div>
    );
  }

  return (
    <input
      id={id}
      name={name || id}
      size={size}
      style={style}
      ref={innerRef}
      className={classNames('form-control', className)}
      {...props}
    />
  );
};

Input.propTypes = {
  id:        PropTypes.string.isRequired,
  size:      PropTypes.number,
  name:      PropTypes.string,
  icon:      PropTypes.string,
  style:     PropTypes.object,
  className: PropTypes.string,
  innerRef:  PropTypes.object
};

Input.defaultProps = {
  name:      '',
  icon:      '',
  style:     {},
  className: ''
};

export default Input;
