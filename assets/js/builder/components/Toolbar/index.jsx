import React from 'react';
import { Container, Button, Separator } from './styles';

const Toolbar = ({ direction, children }) => {
  return (
    <Container direction={direction}>
      {children}
    </Container>
  );
};

Toolbar.Button = Button;
Toolbar.Separator = Separator;

export default Toolbar;
