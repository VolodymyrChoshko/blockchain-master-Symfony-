import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const Link = ({ href, variant, lg, sm, active, className, children, ...props }) => {
  const classes = classNames('btn', {
    [`btn-${variant}`]: variant,
    'btn-lg':           lg,
    'btn-sm':           sm,
    'btn-active':       active
  }, className);

  return (
    <a href={href} className={classes} {...props}>
      {children}
    </a>
  );
};

Link.propTypes = {
  href:      PropTypes.string.isRequired,
  variant:   PropTypes.oneOf(['main', 'alt', 'edit', 'danger', 'link', '']),
  lg:        PropTypes.bool,
  sm:        PropTypes.bool,
  active:    PropTypes.bool,
  className: PropTypes.string,
  children:  PropTypes.node
};

Link.defaultProps = {
  variant:   '',
  lg:        false,
  sm:        false,
  active:    false,
  className: '',
  children:  ''
};

export default Link;
