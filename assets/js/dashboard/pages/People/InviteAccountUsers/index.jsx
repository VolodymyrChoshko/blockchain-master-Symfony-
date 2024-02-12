import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import Box from 'dashboard/components/Box';
import Widget from 'dashboard/components/Form/Widget';
import Select from 'dashboard/components/Form/Select';
import Label from 'dashboard/components/Form/Label';
import Button from 'components/Button';

const InviteAccountUsers = ({ id, users }) => {
  const templateActions = useTemplateActions();
  const [selected, setSelected] = useState('');

  /**
   *
   */
  useEffect(() => {
    setSelected(users[0].id);
  }, [users]);

  /**
   *
   */
  const handleInviteClick = () => {
    templateActions.invitePerson(id, selected);
  };

  const items = {};
  users.forEach((user) => {
    items[user.id] = user.name;
  });

  return (
    <Box.Section>
      <Widget>
        <Label htmlFor="input-user">Add team member:</Label>
        <Select
          name="user"
          value={selected}
          values={items}
          onChange={e => setSelected(e.target.value)}
        />
      </Widget>
      <Button variant="main" onClick={handleInviteClick}>
        Invite
      </Button>
    </Box.Section>
  );
};

InviteAccountUsers.propTypes = {
  id:    PropTypes.number.isRequired,
  users: PropTypes.array.isRequired,
};

export default InviteAccountUsers;
