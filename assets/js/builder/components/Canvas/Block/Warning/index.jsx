import React from 'react';
import PropTypes from 'prop-types';
import { Container } from './styles';

const Warning = ({ children }) => {
  return (
    <Container className="warning">
      {children}
    </Container>
  );
};

Warning.propTypes = {
  children: PropTypes.node.isRequired
};

export default Warning;
