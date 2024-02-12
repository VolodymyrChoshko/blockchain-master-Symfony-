import React from 'react';
import PropTypes from 'prop-types';
import { Container } from './styles';

const Widget = ({ className, children }) => {
  return (
    <Container className={className}>
      {children}
    </Container>
  );
};

Widget.propTypes = {
  className: PropTypes.string,
  children:  PropTypes.node.isRequired
};

export default Widget;
