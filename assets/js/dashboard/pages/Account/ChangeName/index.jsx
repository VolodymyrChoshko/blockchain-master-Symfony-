import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { useUsersActions } from 'dashboard/actions/usersActions';
import Box from 'dashboard/components/Box';
import Widget from 'dashboard/components/Form/Widget';
import Input from 'dashboard/components/Form/Input';
import Button from 'components/Button';

const ChangeName = ({ account }) => {
  const usersActions = useUsersActions();
  const [name, setName] = useState('');

  /**
   *
   */
  useEffect(() => {
    setName(account.organization.org_name);
  }, [account]);

  /**
   *
   */
  const handleSaveClick = () => {
    usersActions.updateOrganization({ name });
  };

  return (
    <Box className="mb-4" white>
      <h2>
        Change organization name
      </h2>
      <p>
        This name is shown to anyone invited to templates under you organization.
      </p>
      <Widget className="d-flex">
        <Input
          name="name"
          value={name}
          onChange={e => setName(e.target.value)}
          className="mr-2"
        />
        <Button variant="main" onClick={handleSaveClick}>
          Change Name
        </Button>
      </Widget>
    </Box>
  );
};

ChangeName.propTypes = {
  account: PropTypes.object.isRequired
};

export default ChangeName;
