import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const Flex = ({
  alignStart,
  alignEnd,
  alignCenter,
  alignBaseline,
  alignStretch,
  justifyStart,
  justifyEnd,
  justifyCenter,
  justifyBetween,
  justifyAround,
  row,
  column,
  className,
  children,
  ...props
}) => {
  const classes = classNames('d-flex', className, {
    'align-items-start':       alignStart,
    'align-items-end':         alignEnd,
    'align-items-center':      alignCenter,
    'align-items-baseline':    alignBaseline,
    'align-items-stretch':     alignStretch,
    'justify-content-start':   justifyStart,
    'justify-content-end':     justifyEnd,
    'justify-content-center':  justifyCenter,
    'justify-content-between': justifyBetween,
    'justify-content-around':  justifyAround,
    'flex-row':                row,
    'flex-column':             column
  });

  return (
    <div className={classes} {...props}>
      {children}
    </div>
  );
};

Flex.propTypes = {
  alignStart:     PropTypes.bool,
  alignEnd:       PropTypes.bool,
  alignCenter:    PropTypes.bool,
  alignBaseline:  PropTypes.bool,
  alignStretch:   PropTypes.bool,
  justifyStart:   PropTypes.bool,
  justifyEnd:     PropTypes.bool,
  justifyCenter:  PropTypes.bool,
  justifyBetween: PropTypes.bool,
  justifyAround:  PropTypes.bool,
  row:            PropTypes.bool,
  column:         PropTypes.bool,
  className:      PropTypes.string,
  children:       PropTypes.node
};

Flex.defaultProps = {
  alignStart:     false,
  alignEnd:       false,
  alignCenter:    false,
  alignBaseline:  false,
  alignStretch:   false,
  justifyStart:   false,
  justifyEnd:     false,
  justifyCenter:  false,
  justifyBetween: false,
  justifyAround:  false,
  row:            false,
  column:         false,
  className:      '',
  children:       ''
};

export default Flex;
