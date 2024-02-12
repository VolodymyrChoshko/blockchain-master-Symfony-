import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const Icon = ({ name, fas, far, mr, fixed, size, active, spinning, className, ...props }) => {
  if (name.indexOf('be-') === 0) {
    return (
      <svg
        width="18"
        height="18"
        viewBox="0 0 18 18"
        className={classNames('icon', { 'mr-2': mr }, className)}
        {...props}
      >
        <use xlinkHref={`#${name}`} />
      </svg>
    );
  }

  return (
    <span className={classNames(`btn-icon ${name}`, { 'mr-2': mr }, className)} {...props} />
  );
};

Icon.propTypes = {
  name:      PropTypes.string.isRequired,
  fas:       PropTypes.bool,
  far:       PropTypes.bool,
  fixed:     PropTypes.bool,
  mr:        PropTypes.bool,
  size:      PropTypes.number,
  active:    PropTypes.bool,
  spinning:  PropTypes.bool,
  className: PropTypes.string
};

Icon.defaultProps = {
  fas:       false,
  far:       false,
  fixed:     true,
  size:      1,
  mr:        false,
  active:    false,
  spinning:  false,
  className: ''
};

export default Icon;
