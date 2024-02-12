import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

const DropZone = ({ /** @type Zone */ zone, active, visible, innerRef }) => {
  if (!zone.styles.top || !visible) {
    return null;
  }

  const classes = classNames(`builder-block-dropzone builder-block-dropzone-${zone.type}`, {
    empty: zone.empty,
    active
  });

  if (innerRef) {
    return (
      <div
        ref={innerRef}
        className={classes}
        style={zone.styles}
      />
    );
  }

  return (
    <div
      className={classes}
      style={zone.styles}
    />
  );
};

DropZone.propTypes = {
  zone:     PropTypes.object.isRequired,
  active:   PropTypes.bool.isRequired,
  visible:  PropTypes.bool.isRequired,
  innerRef: PropTypes.object
};

export default DropZone;
