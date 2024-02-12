import React from 'react';
import PropTypes from 'prop-types';
import { useForm } from 'react-hook-form';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import Box from 'dashboard/components/Box';
import Label from 'dashboard/components/Form/Label';
import Input from 'dashboard/components/Form/Input';
import Button from 'components/Button';

const InviteUsers = ({ id }) => {
  const templateActions = useTemplateActions();
  const { register, handleSubmit, reset, formState: { errors } } = useForm({
    defaultValues: {
      name:  '',
      email: '',
    }
  });

  /**
   * @param values
   */
  const onSubmit = (values) => {
    templateActions.invitePerson(id, 0, values.name, values.email);
    reset();
  };

  return (
    <Box.Section className="dark border-bottom">
      <form onSubmit={handleSubmit(onSubmit)}>
        <Label htmlFor="input-name">Invite a new team member</Label>
        <div className="d-flex">
          <Input
            name="name"
            type="text"
            error={!!errors.name}
            {...register('name', { required: true })}
            placeholder="Name"
            className="mr-2"
          />
          <Input
            name="email"
            type="email"
            error={!!errors.email}
            {...register('email', { required: true })}
            placeholder="Email address"
            className="mr-2"
          />
          <Button type="submit" variant="main">
            Invite
          </Button>
        </div>
      </form>
    </Box.Section>
  );
};

InviteUsers.propTypes = {
  id: PropTypes.number.isRequired
};

export default InviteUsers;
