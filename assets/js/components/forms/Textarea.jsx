import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const Textarea = ({ id, name, className, ...props }) => {
  return (
    <textarea
      id={id}
      name={name || id}
      className={classNames('form-control', className)}
      {...props}
    />
  );
};

Textarea.propTypes = {
  id:        PropTypes.string.isRequired,
  name:      PropTypes.string,
  className: PropTypes.string
};

Textarea.defaultProps = {
  name:      '',
  className: ''
};

export default Textarea;
