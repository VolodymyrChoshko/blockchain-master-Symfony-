import React from 'react';
import PropTypes from 'prop-types';
import { Icon } from 'components';

const CodeArrow = ({ dimensions }) => {
  const styles = {
    opacity: 1,
    top:     Math.floor(dimensions.top),
    left:    Math.floor(dimensions.left + ((dimensions.width / 2) - 7))
  };

  return (
    <div className="builder-code-block-arrow" style={styles}>
      <Icon name="be-symbol-arrow-down" />
    </div>
  );
};

CodeArrow.propTypes = {
  dimensions: PropTypes.object.isRequired
};

CodeArrow.defaultProps = {};

export default CodeArrow;
