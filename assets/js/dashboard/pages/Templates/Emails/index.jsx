import React from 'react';
import PropTypes from 'prop-types';
import Email from '../Email';
import { Container } from './styles';

const Emails = ({ emails }) => {
  return (
    <Container>
      {emails.map(email => (
        <Email key={email.id} email={email} />
      ))}
    </Container>
  );
};

Emails.propTypes = {
  emails: PropTypes.array.isRequired
};

export default Emails;
