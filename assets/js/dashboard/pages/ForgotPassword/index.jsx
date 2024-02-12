import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import api from 'lib/api';
import router from 'lib/router';
import { useUIActions } from 'builder/actions/uiActions';
import Box from 'dashboard/components/Box';
import Label from 'dashboard/components/Form/Label';
import Widget from 'dashboard/components/Form/Widget';
import Input from 'dashboard/components/Form/Input';
import Button from 'components/Button';

const ForgotPassword = () => {
  const uiActions = useUIActions();
  const { register, handleSubmit, setError, formState: { errors } } = useForm();
  const [disabled, setDisabled] = useState(false);

  /**
   * @param values
   */
  const onSubmit = (values) => {
    api.post(router.generate('api_v1_auth_forgot_password'), { email: values.email })
      .then((resp) => {
        if (resp.error) {
          setError('email', { message: resp.error });
        } else if (resp.success) {
          uiActions.notice('success', resp.success);
          setDisabled(true);
        }
      });
  };

  return (
    <Box className="mt-4 mb-4" narrow white>
      <h1 className="mb-2">Forgot Password</h1>
      <form onSubmit={handleSubmit(onSubmit)}>
        <p>
          Enter your email and we&apos;ll send you a link to reset your password.
        </p>
        <Widget>
          <Label htmlFor="input-email" errorMessage={errors.email ? errors.email.message : ''} />
          <Input
            name="email"
            type="email"
            error={!!errors.email}
            {...register('email', { required: true })}
            placeholder="Email address"
          />
        </Widget>
        <Button variant="main" type="submit" disabled={disabled} wide>
          Reset My Password
        </Button>
      </form>
    </Box>
  );
};

export default ForgotPassword;
