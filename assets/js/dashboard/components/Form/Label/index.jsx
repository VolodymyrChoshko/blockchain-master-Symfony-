import React from 'react';
import PropTypes from 'prop-types';
import { Container, Error } from './styles';

const Label = ({ htmlFor, required, errorMessage, children }) => {
  return (
    <Container htmlFor={htmlFor}>
      {children}
      {required && ' *'}
      {errorMessage && (
        <Error>{errorMessage}</Error>
      )}
    </Container>
  );
};

Label.propTypes = {
  htmlFor:      PropTypes.string.isRequired,
  errorMessage: PropTypes.string,
  required:     PropTypes.bool,
  children:     PropTypes.node
};

Label.defaultProps = {
  required:     false,
  errorMessage: '',
};

export default Label;
