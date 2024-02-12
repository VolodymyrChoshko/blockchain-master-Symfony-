import React from 'react';
import PropTypes from 'prop-types';

const Loading = ({ fixed, ellipsis, style }) => {
  if (ellipsis) {
    return (
      <img src="/assets/images/ellipsis.svg" alt="Loading animation" style={style} />
    );
  }

  return (
    <div className={`fancybox-loading ${fixed ? 'position-fixed' : 'position-absolute'}`} style={style} />
  );
};

Loading.propTypes = {
  fixed:    PropTypes.bool,
  ellipsis: PropTypes.bool,
  style:    PropTypes.object
};

Loading.defaultProps = {
  fixed:    true,
  ellipsis: false,
  style:    {},
};

export default Loading;
