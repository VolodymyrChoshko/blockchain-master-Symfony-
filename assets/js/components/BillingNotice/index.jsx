import React from 'react';
import PropTypes from 'prop-types';
import { Container } from './styles';

const BillingNotice = ({ className, children }) => {
  return (
    <Container className={className}>
      {children}
    </Container>
  );
};

BillingNotice.propTypes = {
  children:  PropTypes.node,
  className: PropTypes.string
};

BillingNotice.defaultProps = {
  className: '',
};

export default BillingNotice;
