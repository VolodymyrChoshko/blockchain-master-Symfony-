import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const ButtonGroup = ({ className, children, ...props }) => {
  return (
    <div className={classNames('btn-group', className)} {...props}>
      {children}
    </div>
  );
};

ButtonGroup.propTypes = {
  className: PropTypes.string,
  children:  PropTypes.node
};

ButtonGroup.defaultProps = {
  className: '',
  children:  ''
};

export default ButtonGroup;
