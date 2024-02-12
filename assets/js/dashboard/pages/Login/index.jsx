import React, { useEffect } from 'react';
import { useSelector } from 'react-redux';
import { Link, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import useMe from 'dashboard/hooks/useMe';
import { useUsersActions } from 'dashboard/actions/usersActions';
import Box from 'dashboard/components/Box';
import Label from 'dashboard/components/Form/Label';
import Widget from 'dashboard/components/Form/Widget';
import Input from 'dashboard/components/Form/Input';
import Button from 'components/Button';

const Login = () => {
  const me = useMe();
  const usersActions = useUsersActions();
  const navigate = useNavigate();
  const idProviders = useSelector(state => state.users.idProviders);
  const { register, handleSubmit, formState: { errors } } = useForm();

  /**
   *
   */
  useEffect(() => {
    if (me) {
      navigate('/');
      return;
    }
    const matches = document.location.hostname.match(/^([\d]+)\.[\w]+\.blocksedit.com$/);
    if (matches) {
      const oid = parseInt(matches[1], 10);
      if (oid && !isNaN(oid)) {
        usersActions.loadIdProviders(oid);
      }
    }
  }, [me]);

  /**
   *
   */
  const onSubmit = (values) => {
    usersActions.login(values.email, values.password, () => {
      navigate('/');
    });
  };

  return (
    <Box className="mt-4 mb-4" narrow white>
      <h1 className="mb-2">Sign In</h1>
      <form onSubmit={handleSubmit(onSubmit)}>
        <Widget>
          <Label htmlFor="input-email" errorMessage={errors.email ? 'Required' : ''} />
          <Input
            name="email"
            type="email"
            error={!!errors.email}
            {...register('email', { required: true })}
            placeholder="Email address"
          />
        </Widget>
        <Widget>
          <Label htmlFor="input-password" errorMessage={errors.password ? 'Required' : ''} />
          <Input
            type="password"
            name="password"
            error={!!errors.password}
            {...register('password', { required: true })}
            placeholder="Password"
          />
        </Widget>
        <Link to="/forgotpassword" className="d-block mb-3">
          I forgot my password
        </Link>
        <Button variant="main" type="submit" wide>
          Sign In
        </Button>
        {idProviders.map(ip => (
          <a
            key={ip.url}
            href={ip.url}
            className="btn-main w-100 text-center mt-3"
          >
            {ip.label}
          </a>
        ))}
      </form>
      <div className="text-center mt-3">
        Don&apos;t have an account yet? <Link to="/signup">Sign Up</Link>
      </div>
    </Box>
  );
};

export default Login;
