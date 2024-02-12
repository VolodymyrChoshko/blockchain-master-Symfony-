import React, { useState } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { useSelector } from 'react-redux';
import arrays from 'utils/arrays';

const EmptyBlock = ({ block, editing, draggableID }) => {
  const [over, setOver] = useState(false);
  const components = useSelector(state => state.builder.components);

  const classes = classNames('builder-block-empty', {
    'block-section-empty-editing': editing,
    'builder-block-empty-hover':   over
  });

  const rect = block.element.getBoundingClientRect();

  /**
   *
   */
  const handleMouseEnter = () => {
    if (draggableID !== -1) {
      const draggable = arrays.findByID(components, draggableID);
      if (!draggable) {
        setOver(true);
      }
    }
  };

  /**
   *
   */
  const handleMouseLeave = () => {
    if (draggableID !== -1) {
      setOver(false);
    }
  };

  return (
    <div
      className={classes}
      style={{ top: rect.top, left: rect.left, width: rect.width, height: rect.height }}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
    />
  );
};

EmptyBlock.propTypes = {
  block:       PropTypes.object.isRequired,
  editing:     PropTypes.bool.isRequired,
  draggableID: PropTypes.number.isRequired
};

export default EmptyBlock;
