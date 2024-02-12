import React from 'react';
import PropTypes from 'prop-types';

const HeadLabel = ({ dimensions }) => {
  const styles = {
    opacity: 1,
    top:     Math.floor(dimensions.top + dimensions.height - 1),
    left:    dimensions.left
  };

  return (
    <div className="builder-code-block-label" style={styles}>
      Head
    </div>
  );
};

HeadLabel.propTypes = {
  dimensions: PropTypes.object.isRequired
};

HeadLabel.defaultProps = {};

export default HeadLabel;
