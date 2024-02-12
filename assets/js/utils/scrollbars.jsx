import React from 'react';

export const renderTrackHorizontal = ({ style, ...props }) => {
  const finalStyle = {
    ...style,
    right:        2,
    bottom:       0,
    left:         2,
    height:       8,
    borderRadius: 0
  };
  return <div style={finalStyle} {...props} />;
};

export const renderTrackVertical = ({ style, ...props }) => {
  const finalStyle = {
    ...style,
    right:        4,
    bottom:       2,
    top:          2,
    width:        8,
    borderRadius: 0
  };
  return <div style={finalStyle} {...props} />;
};

export const renderThumbHorizontal = ({ style, ...props }) => {
  const finalStyle = {
    ...style,
    cursor:          'pointer',
    borderRadius:    'inherit',
    backgroundColor: 'rgba(0, 0, 0, .5)'
  };
  return <div style={finalStyle} {...props} />;
};

const renderThumbVertical = ({ style, ...props }) => {
  const finalStyle = {
    ...style,
    cursor:          'pointer',
    borderRadius:    'inherit',
    backgroundColor: 'rgba(0, 0, 0, .5)'
  };
  return <div style={finalStyle} {...props} />;
};

export default {
  renderTrackHorizontal,
  renderThumbHorizontal,
  renderTrackVertical,
  renderThumbVertical
};
