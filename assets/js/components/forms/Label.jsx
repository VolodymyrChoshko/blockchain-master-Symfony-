import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const Label = ({ htmlFor, className, children, ...props }) => {
  return (
    <label htmlFor={htmlFor} className={classNames('form-label', className)} {...props}>
      {children}
    </label>
  );
};

Label.propTypes = {
  htmlFor:   PropTypes.string.isRequired,
  className: PropTypes.string,
  children:  PropTypes.node
};

Label.defaultProps = {
  className: '',
  children:  ''
};

export default Label;
