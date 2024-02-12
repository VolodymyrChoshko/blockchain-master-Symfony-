import React from 'react';
import PropTypes from 'prop-types';
import { Container } from './styles';

const SidebarTitle = ({ children, style }) => {
  return (
    <Container style={style}>
      {children}
    </Container>
  );
};

SidebarTitle.propTypes = {
  children: PropTypes.node.isRequired,
  style: PropTypes.object
};

export default SidebarTitle;
