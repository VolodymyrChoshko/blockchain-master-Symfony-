import React, { useEffect, useRef } from 'react';
import { useForm } from 'react-hook-form';
import { useUsersActions } from 'dashboard/actions/usersActions';
import useMe from 'dashboard/hooks/useMe';
import Button from 'components/Button';
import Widget from 'dashboard/components/Form/Widget';
import Switch from 'components/Switch';
import { requestPushSubscription } from 'utils/serviceWorkers';

const NotificationsForm = () => {
  const me = useMe();
  const usersActions = useUsersActions();
  const prevIsNotificationsEnabled = useRef(me.isNotificationsEnabled);

  const { register, handleSubmit, setValue, watch, setError, formState: { errors } } = useForm({
    defaultValues: {
      isNotificationsEnabled: me.isNotificationsEnabled,
      isEmailsEnabled: me.isEmailsEnabled,
      isShowingCount: me.isShowingCount,
      webPushSubscription: '',
    }
  });

  /**
   *
   */
  useEffect(() => {
    const subscription = watch((values) => {
      if (
        values.isNotificationsEnabled !== prevIsNotificationsEnabled.current
        && values.isNotificationsEnabled === true
      ) {
        requestPushSubscription()
          .then((sub) => {
            setValue('webPushSubscription', JSON.stringify(sub));
          })
          .catch((err) => {
            console.error(err);
            setValue('isNotificationsEnabled', false);
            setError('isNotificationsEnabled', {
              message: 'Notification permissions have been denied',
            });
          });
      }
      prevIsNotificationsEnabled.current = values.isNotificationsEnabled;
    });

    return () => subscription.unsubscribe();
  }, [me, watch]);

  /**
   * @param values
   */
  const onSubmit = (values) => {
    usersActions.updateNotificationSettings(values);
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      {errors.isNotificationsEnabled && (
        <div className="mb-3">
          <p className="text-danger">
            {errors.isNotificationsEnabled.message}.
          </p>
        </div>
      )}
      <Widget>
        <div className="d-flex align-items-center mr-3">
          <label htmlFor="input-notifications-computer" className="mr-2" style={{ width: 90 }}>
            Desktop
          </label>
          <Switch
            id="input-notifications-computer"
            {...register('isNotificationsEnabled')}
          />
        </div>
        <small className="form-help text-muted">
          Receive notifications on your desktop computer.
        </small>
      </Widget>

      <Widget>
        <div className="d-flex align-items-center">
          <label htmlFor="input-notifications-email" className="mr-2" style={{ width: 90 }}>
            Email
          </label>
          <Switch
            id="input-notifications-email"
            {...register('isEmailsEnabled')}
          />
        </div>
        <small className="form-help text-muted">
          Receive notifications in your inbox.
        </small>
      </Widget>

      <Widget>
        <div className="d-flex align-items-center mr-3">
          <label htmlFor="input-notifications-count" className="mr-2" style={{ width: 90 }}>
            Show Count
          </label>
          <Switch
            id="input-notifications-count"
            {...register('isShowingCount')}
          />
        </div>
        <small className="form-help text-muted">
          Show count of new notifications in the top bar.
        </small>
      </Widget>

      <input type="hidden" {...register('webPushSubscription')} />
      <Button variant="main" type="submit" wide>
        Save
      </Button>
    </form>
  );
};

export default NotificationsForm;
