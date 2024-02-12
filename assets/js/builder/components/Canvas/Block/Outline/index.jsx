import React, { useMemo } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Container } from './styles';

const Outline = ({ block, dimensions, active, visible, hover, lineWidth }) => {
  /**
   *
   */
  const classes = useMemo(() => {
    return classNames(`builder-block-b-${block.type}`, {
      'active':                 active,
      'builder-block-editable': block.rules.isEditable && block.children.length === 0
    });
  }, [block, active]);

  /**
   * @type {unknown}
   */
  const styles = useMemo(() => {
    const s = {
      top:    {},
      right:  {},
      bottom: {},
      left:   {},
    };
    if (!visible) {
      return s;
    }

    s.top    = {
      left:        dimensions.left,
      top:         dimensions.top - lineWidth,
      width:       dimensions.width,
      borderWidth: lineWidth,
      borderTop:   0,
    };
    s.right  = {
      left:        dimensions.left + dimensions.width,
      top:         dimensions.top - lineWidth,
      height:      dimensions.height + lineWidth,
      borderWidth: lineWidth,
      borderRight: 0,
    };
    s.bottom = {
      left:         dimensions.left,
      top:          dimensions.top + dimensions.height - lineWidth,
      width:        dimensions.width + lineWidth,
      borderWidth:  lineWidth,
      borderBottom: 0,
    };
    s.left   = {
      left:        dimensions.left,
      top:         dimensions.top - lineWidth,
      height:      dimensions.height,
      borderWidth: lineWidth,
      borderLeft:  0,
    };

    return s;
  }, [block, visible, dimensions, block.id]);

  if (!visible) {
    return null;
  }

  return (
    <>
      <Container
        style={styles.top}
        className={classNames(classes, { hover })}
      />
      <Container
        style={styles.right}
        className={classNames(classes, { hover })}
      />
      <Container
        style={styles.bottom}
        className={classNames(classes, { hover })}
      />
      <Container
        style={styles.left}
        className={classNames(classes, { hover })}
      />
    </>
  );
};

Outline.propTypes = {
  dimensions: PropTypes.shape({
    left:   PropTypes.number,
    top:    PropTypes.number,
    width:  PropTypes.number,
    height: PropTypes.number
  }).isRequired,
  block:     PropTypes.object.isRequired,
  active:    PropTypes.bool,
  visible:   PropTypes.bool,
  hover:     PropTypes.bool,
  lineWidth: PropTypes.number,
};

Outline.defaultProps = {
  active:    false,
  visible:   true,
  hover:     false,
  lineWidth: 1,
};

export default Outline;
