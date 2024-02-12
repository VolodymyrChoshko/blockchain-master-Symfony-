import React from 'react';
import PropTypes from 'prop-types';
import { Container } from './styles';

const Highlight = ({ block }) => {
  return (
    <Container
      style={{
        top: block.rect.top,
        left: block.rect.left,
        width: block.rect.width,
        height: block.rect.height,
      }}
    />
  );
};

Highlight.propTypes = {
  block: PropTypes.object.isRequired,
};

export default Highlight;
