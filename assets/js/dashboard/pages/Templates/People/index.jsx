import React, { useState } from 'react';
import PropTypes from 'prop-types';
import Avatar from 'dashboard/components/Avatar';
import Icon from 'components/Icon';
import router from 'lib/router';
import PersonModal from 'components/PersonModal';
import { Container, Item, List, AddIcon, AddButton } from './styles';

const People = ({ users, template }) => {
  const [modalUser, setModalUser] = useState(null);

  if (!template) {
    return null;
  }

  const addHref = router.generate('people', { id: template.id });

  return (
    <Container className="pt-1">
      <List>
        {users.map(u => (
          <Item key={u.id}>
            <Avatar user={u} className="avatar" onClick={() => setModalUser(u)} />
          </Item>
        ))}
        <Item className="d-inline-flex align-items-center">
          <AddIcon to={addHref}>
            <Icon name="be-symbol-plus" />
          </AddIcon>
          <AddButton to={addHref}>Add/Remove Editors</AddButton>
        </Item>
      </List>
      {modalUser && (
        <PersonModal user={modalUser} onClose={() => setModalUser(null)} />
      )}
    </Container>
  );
};

People.propTypes = {
  users:    PropTypes.array.isRequired,
  template: PropTypes.object
};

export default People;
