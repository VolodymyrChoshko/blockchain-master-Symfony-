import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import { useUsersActions } from 'dashboard/actions/usersActions';
import useMe from 'dashboard/hooks/useMe';
import Box from 'dashboard/components/Box';
import Button from 'components/Button';
import Widget from 'dashboard/components/Form/Widget';
import Label from 'dashboard/components/Form/Label';
import Input from 'dashboard/components/Form/Input';
import Select from 'dashboard/components/Form/Select';
import timezones from 'dashboard/pages/Profile/timezones';

const SignUp = () => {
  const me = useMe();
  const navigate = useNavigate();
  const usersActions = useUsersActions();
  const { register, handleSubmit, setValue, setError, formState: { errors } } = useForm();

  /**
   *
   */
  useEffect(() => {
    if (me) {
      navigate('/');
    }
  }, [me]);

  /**
   *
   */
  useEffect(() => {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (timezone) {
      setValue('timezone', timezone);
    }
  }, []);

  /**
   * @param values
   */
  const onSubmit = (values) => {
    if (!values.terms) {
      setError('terms', { message: 'Required' });
      return;
    }

    const searchParams = new URLSearchParams(window.location.search);
    usersActions.createAccount(values, searchParams.get('ref') || '');
  };

  return (
    <Box className="mt-4 mb-4" padded={false} shadow={false} overflowHidden={false} narrow>
      <h1 className="mb-2">Create Account</h1>

      <Box className="mb-4" white narrow>
        <form onSubmit={handleSubmit(onSubmit)}>
          <Widget>
            <Label htmlFor="input-name" errorMessage={errors.name ? 'Required' : ''} required>
              Name
            </Label>
            <Input
              name="name"
              error={!!errors.name}
              {...register('name', { required: true })}
            />
          </Widget>
          <Widget>
            <Label htmlFor="input-email" errorMessage={errors.email ? 'Required' : ''} required>
              Email Address
            </Label>
            <Input
              type="email"
              name="email"
              error={!!errors.email}
              {...register('email', { required: true })}
            />
          </Widget>
          <Widget>
            <Label htmlFor="input-password" errorMessage={errors.password ? 'Required' : ''} required>
              Password
            </Label>
            <Input
              type="password"
              name="password"
              error={!!errors.password}
              {...register('password', { required: true })}
            />
          </Widget>
          <Widget>
            <Label htmlFor="input-job" errorMessage={errors.job ? 'Required' : ''}>
              Role
            </Label>
            <Input
              name="job"
              error={!!errors.job}
              {...register('job', { required: false })}
            />
          </Widget>
          <Widget>
            <Label htmlFor="input-organization" errorMessage={errors.organization ? 'Required' : ''}>
              Organization
            </Label>
            <Input
              name="organization"
              error={!!errors.organization}
              {...register('organization', { required: false })}
            />
          </Widget>
          <Widget>
            <Label htmlFor="input-timezone">
              Time Zone
            </Label>
            <Select
              name="timezone"
              error={!!errors.timezone}
              {...register('timezone')}
              values={timezones}
            />
          </Widget>
          <Widget>
            <Input
              id="input-terms"
              type="checkbox"
              name="terms"
              error={!!errors.terms}
              {...register('terms', { required: true })}
              className="d-inline position-relative mr-2"
              style={{
                top:      2,
                float:    'left',
                fontSize: 20,
                width:    'auto',
              }}
            />
            <label htmlFor="input-terms" style={{ border: errors.terms ? '1px solid red' : '' }}>
              I agree to the <a href="https://blocksedit.com/about/terms/" target="_blank" rel="noopener noreferrer">Terms of Service</a> and
              the <a href="https://blocksedit.com/about/privacy/" target="_blank" rel="noopener noreferrer">Privacy Policy</a>
            </label>
          </Widget>
          <Widget>
            <Input
              id="input-newsletter"
              type="checkbox"
              name="newsletter"
              {...register('newsletter')}
              className="d-inline position-relative mr-2"
              style={{
                top:      2,
                fontSize: 20,
                float:    'left',
                width:    'auto',
              }}
            />
            <label htmlFor="input-newsletter">
              Subscribe to our monthly newsletter with tips on improving your marketing emails.
            </label>
          </Widget>
          <Button variant="main" type="submit" className="mb-3" wide>
            Create my account
          </Button>
          <p className="text-center">
            Already have an account? <Link to="/login">Sign In</Link>
          </p>
        </form>
      </Box>
    </Box>
  );
};

export default SignUp;
