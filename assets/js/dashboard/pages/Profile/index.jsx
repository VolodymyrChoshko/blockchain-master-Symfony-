import React, { useEffect, useState, useRef } from 'react';
import { useForm } from 'react-hook-form';
import { useUsersActions } from 'dashboard/actions/usersActions';
import { useUIActions } from 'builder/actions/uiActions';
import { scrollIntoView } from 'utils/browser';
import Emoji from 'components/Emoji';
import useMe from 'dashboard/hooks/useMe';
import Avatar from 'dashboard/components/Avatar';
import Box from 'dashboard/components/Box';
import Button from 'components/Button';
import Widget from 'dashboard/components/Form/Widget';
import Label from 'dashboard/components/Form/Label';
import Icon from 'components/Icon';
import Input from 'dashboard/components/Form/Input';
import Select from 'dashboard/components/Form/Select';
import { Link } from 'react-router-dom';
import NotificationsForm from './NotificationsForm';
import timezones from './timezones';
import { AvatarWrap, EmojiWrap } from './styles';

const Profile = () => {
  const me = useMe();
  const meClone = { ...me, 'avatar': me.avatar.replace('-60x60', '') };
  const uiActions = useUIActions();
  const usersActions = useUsersActions();
  const [avatar, setAvatar] = useState('');
  const [isAvatarRemoved, setAvatarRemoved] = useState(false);
  const fileRef = useRef(null);
  const themeSwitchRef = useRef(false);

  const { register, handleSubmit, setValue, watch, formState: { errors } } = useForm({
    defaultValues: {
      ...meClone,
      // eslint-disable-next-line no-nested-ternary
      theme: me.isDarkMode === null ? 'auto' : (me.isDarkMode ? 'dark' : 'light'),
    }
  });
  const skinTone = watch('skinTone');
  const tones = Array.from(Array(5).keys());

  /**
   *
   */
  useEffect(() => {
    if (!me.timezone) {
      const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      if (timezone) {
        setValue('timezone', timezone);
      }
    }
  }, [me]);

  /**
   *
   */
  useEffect(() => {
    const subscription = watch((values) => {
      if ((me.isDarkMode && values.theme === 'light') || (!me.isDarkMode && values.theme === 'dark')) {
        themeSwitchRef.current = true;
      }

      /* if (values.theme === 'auto') {
        usersActions.switchTheme(null);
      } else {
        usersActions.switchTheme(values.theme === 'dark');
      } */
    });

    return () => subscription.unsubscribe();
  }, [me, watch]);

  /**
   *
   */
  useEffect(() => {
    if (document.location.hash) {
      const el = document.querySelector(document.location.hash);
      scrollIntoView(el);
    }
  }, []);

  /**
   * @param values
   */
  const onSubmit = (values) => {
    themeSwitchRef.current = false;
    usersActions.updateMe(values);
  };

  /**
   * @param e
   */
  const handleFileChange = (e) => {
    usersActions.uploadAvatar(e.target.files[0], (url) => {
      setValue('avatar', url);
      setAvatar(url);
    });
  };

  /**
   *
   */
  const handleUploadClick = () => {
    fileRef.current.click();
  };

  /**
   *
   */
  const handleRemoveAvatarClick = () => {
    setValue('avatar', '');
    setAvatar('');
    setAvatarRemoved(true);
  };

  /**
   *
   */
  const handleChangePasswordClick = () => {
    uiActions.uiModal('changePassword', true);
  };

  return (
    <div className="pb-4">
      <Box className="mt-4 mb-2" padded={false} shadow={false} overflowHidden={false} narrow>
        <div className="builder-header-info pl-0">
          <Link to="/">
            ← Back to Dashboard
          </Link>
        </div>
      </Box>
      <Box className="mt-4 mb-4" white narrow>
        <div className="mb-3 text-center">
          <AvatarWrap className="mb-3">
            {isAvatarRemoved ? (
              <Avatar user={{ name: me.name }} lg />
            ) : (
              <Avatar user={meClone} src={(avatar || '').replace('-60x60', '')} lg />
            )}
            <button title="Add avatar image" onClick={handleUploadClick}>
              <Icon name="be-symbol-edit" />
            </button>
            <input ref={fileRef} type="file" className="hidden" onChange={handleFileChange} />
          </AvatarWrap>
          {me.avatar !== '' && (
            <div>
              <Button variant="link" onClick={handleRemoveAvatarClick}>
                Remove avatar and show initials
              </Button>
            </div>
          )}
        </div>

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
              name="email"
              type="email"
              error={!!errors.email}
              {...register('email', { required: true })}
            />
          </Widget>
          <Widget>
            <Label htmlFor="input-job">
              Job Title
            </Label>
            <Input
              name="job"
              error={!!errors.job}
              {...register('job')}
            />
          </Widget>
          <Widget>
            <Label htmlFor="input-organization">
              Organization
            </Label>
            <Input
              name="organization"
              error={!!errors.organization}
              {...register('organization')}
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
            <Label htmlFor="input-theme">
              Site Theme
            </Label>
            <Select
              name="theme"
              error={!!errors.theme}
              {...register('theme')}
              values={{
                'auto':  'Auto',
                'light': 'Light Mode',
                'dark':  'Dark Mode',
              }}
            />
          </Widget>

          <Widget>
            <Label htmlFor="input-skin-tone">
              Emoji Skin Tone
            </Label>
            <EmojiWrap>
              <button
                type="button"
                onClick={() => setValue('skinTone', '-1')}
                className={parseInt(skinTone, 10) === -1 ? 'btn-tone selected mr-2' : 'btn-tone mr-2'}
              >
                <Emoji tone={-1} code="1F44D" />
              </button>
              {tones.map((key) => (
                <button
                  key={key}
                  type="button"
                  onClick={() => setValue('skinTone', key)}
                  className={skinTone === key ? 'btn-tone selected mr-2' : 'btn-tone mr-2'}
                >
                  <Emoji tone={key} code="1F44D" />
                </button>
              ))}
            </EmojiWrap>
          </Widget>

          <Button variant="main" type="submit" wide>
            Save
          </Button>
        </form>
        {me.hasPass && (
          <div className="pt-3 text-center">
            <Button variant="link" onClick={handleChangePasswordClick}>
              Change password
            </Button>
          </div>
        )}
      </Box>

      <Box className="mt-4 mb-4" white narrow>
        <h2 id="notifications" className="mb-2">Notifications</h2>
        <NotificationsForm />
      </Box>
    </div>
  );
};

export default Profile;
