import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const Box = ({ wide, fluid, narrow, white, shadow, className, style, children }) => {
  const classes = classNames('container', className, {
    'container-wide':   wide,
    'container-fluid':  fluid,
    'container-narrow': narrow,
    'container-shadow': shadow,
    'white':            white
  });

  return (
    <div className={classes} style={style}>
      {children}
    </div>
  );
};

Box.propTypes = {
  wide:      PropTypes.bool,
  fluid:     PropTypes.bool,
  narrow:    PropTypes.bool,
  white:     PropTypes.bool,
  shadow:    PropTypes.bool,
  style:     PropTypes.object,
  className: PropTypes.string,
  children:  PropTypes.node.isRequired
};

Box.defaultProps = {
  wide:      false,
  fluid:     false,
  white:     false,
  shadow:    false,
  style:     {},
  className: ''
};

export default Box;
