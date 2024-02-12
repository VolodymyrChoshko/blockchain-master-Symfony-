import React from 'react';
import PropTypes from 'prop-types';
import { Icon } from 'components/index';
import { Container } from './styles';

const HelpButton = ({ className }) => {
  return (
    <Container
      title="Help"
      href="https://blocksedit.com/help/"
      className={className}
      rel="noopener noreferrer"
      target="_blank"
    >
      <Icon name="be-symbol-help" />
    </Container>
  );
};

HelpButton.propTypes = {
  className: PropTypes.string,
};

export default HelpButton;
