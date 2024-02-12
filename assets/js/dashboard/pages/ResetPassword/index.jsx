import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { loading } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import { useUIActions } from 'builder/actions/uiActions';
import Box from 'dashboard/components/Box';
import Label from 'dashboard/components/Form/Label';
import Widget from 'dashboard/components/Form/Widget';
import Input from 'dashboard/components/Form/Input';
import Button from 'components/Button';

const ResetPassword = () => {
  const params = useParams();
  const navigate = useNavigate();
  const uiActions = useUIActions();
  const { register, handleSubmit, setError, formState: { errors } } = useForm();

  /**
   * @param values
   */
  const onSubmit = (values) => {
    loading(true);
    const body = {
      token:    params.token,
      password: values.password,
    };
    api.post(router.generate('api_v1_auth_reset_password'), body)
      .then((resp) => {
        if (resp.error) {
          setError('password', { message: resp.error });
        } else {
          uiActions.notice('success', 'Password reset successfully!');
          navigate('/login');
        }
      })
      .finally(() => {
        loading(false);
      });
  };

  return (
    <Box className="mt-4 mb-4" narrow white>
      <h1 className="mb-2">Reset Password</h1>
      <form onSubmit={handleSubmit(onSubmit)}>
        <Widget>
          <Label htmlFor="input-password" errorMessage={errors.password ? errors.password.message : ''}>
            Enter New Password
          </Label>
          <Input
            name="password"
            type="password"
            error={!!errors.password}
            {...register('password', { required: true })}
          />
        </Widget>
        <Button variant="main" type="submit" wide>
          Save
        </Button>
      </form>
    </Box>
  );
};

export default ResetPassword;
