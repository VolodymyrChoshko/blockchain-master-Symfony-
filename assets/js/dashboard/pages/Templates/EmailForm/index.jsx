import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import Input from 'components/forms/Input';
import Button from 'components/Button';
import { Container, Title, Controls } from './styles';

const EmailForm = ({ email, placeholder, isDuplicating, onSave, onCancel }) => {
  const [title, setTitle] = useState(email.title || email.name || '');
  const inputRef = useRef(null);

  /**
   *
   */
  useEffect(() => {
    setTimeout(() => {
      inputRef.current.focus();
    }, 250);
  }, []);

  return (
    <Container big={email.id === undefined || isDuplicating}>
      <Title>
        <Input
          id="db-new-email-title"
          placeholder={placeholder}
          value={title}
          onChange={e => setTitle(e.target.value)}
          innerRef={inputRef}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              onSave(e, title, email);
            }
          }}
        />
      </Title>
      <Controls>
        <Button variant="main" onClick={e => onSave(e, title, email)}>
          Save
        </Button>
        <Button variant="alt" onClick={e => onCancel(e, email)}>
          Cancel
        </Button>
      </Controls>
    </Container>
  );
};

EmailForm.propTypes = {
  email:         PropTypes.object,
  placeholder:   PropTypes.string,
  isDuplicating: PropTypes.bool,
  onSave:        PropTypes.func.isRequired,
  onCancel:      PropTypes.func.isRequired
};

EmailForm.defaultProps = {
  placeholder:  'Email name',
  isDuplicating: false,
};

export default EmailForm;
