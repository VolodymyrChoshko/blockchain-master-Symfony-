import React, { useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { useUsersActions } from 'dashboard/actions/usersActions';
import Modal from 'components/Modal';
import Button from 'components/Button';
import Widget from 'dashboard/components/Form/Widget';
import Input from 'dashboard/components/Form/Input';

const ChangePasswordModal = (props) => {
  const usersActions = useUsersActions();
  const [password, setPassword] = useState('');
  const inputRef = useRef(null);

  /**
   *
   */
  const handleClick = () => {
    const p = password.trim();
    if (p) {
      usersActions.updatePassword(p, () => {
        props.closeModal();
      });
    }
  };

  /**
   *
   */
  const handleVisible = () => {
    setTimeout(() => {
      inputRef.current.focus();
    }, 100);
  };

  return (
    <Modal title="Change Password" {...props} onVisible={handleVisible} auto sm>
      <Widget>
        <Input
          ref={inputRef}
          name="password"
          type="password"
          value={password}
          onChange={e => setPassword(e.target.value)}
        />
      </Widget>
      <Button variant="main" onClick={handleClick}>
        Save Password
      </Button>
    </Modal>
  );
};

ChangePasswordModal.propTypes = {
  closeModal: PropTypes.func
};

export default ChangePasswordModal;
