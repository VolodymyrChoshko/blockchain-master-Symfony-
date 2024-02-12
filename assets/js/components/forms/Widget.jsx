import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import Label from './Label';

const Widget = ({ label, htmlFor, underlined, error, children, className, ...props }) => {
  return (
    <div
      className={classNames('form-widget', { underlined, 'form-widget-error': error }, className)}
      {...props}
    >
      {label && (
        <Label htmlFor={htmlFor}>
          {label}
        </Label>
      )}
      {children}
    </div>
  );
};

Widget.propTypes = {
  label:      PropTypes.string,
  error:      PropTypes.bool,
  htmlFor:    PropTypes.string,
  underlined: PropTypes.bool,
  className:  PropTypes.string,
  children:   PropTypes.node
};

Widget.defaultProps = {
  label:      '',
  htmlFor:    '',
  error:      false,
  underlined: false,
  className:  '',
  children:   ''
};

export default Widget;
