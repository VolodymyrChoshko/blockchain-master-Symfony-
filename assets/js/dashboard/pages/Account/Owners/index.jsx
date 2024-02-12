import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { useUsersActions } from 'dashboard/actions/usersActions';
import { useUIActions } from 'builder/actions/uiActions';
import useMe from 'dashboard/hooks/useMe';
import Box from 'dashboard/components/Box';
import Avatar from 'dashboard/components/Avatar';
import Icon from 'components/Icon';
import Button from 'components/Button';
import Widget from 'dashboard/components/Form/Widget';
import Input from 'dashboard/components/Form/Input';

const Owners = ({ isOwner, isAdmin, users, access }) => {
  const me = useMe();
  const usersActions = useUsersActions();
  const uiActions = useUIActions();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');

  /**
   * @param e
   * @param user
   */
  const handleRevokeClick = (e, user) => {
    e.preventDefault();
    uiActions.confirm('Revoke Powers', 'Are you sure you want to revoke powers for this user?', () => {
      usersActions.revokePowers(user.id, access === '1' ? 'owners' : 'admins');
    });
  };

  /**
   *
   */
  const handleInviteClick = () => {
    usersActions.inviteUser(name, email, access);
    setName('');
    setEmail('');
  };

  let title = '';
  switch (access) {
    case '1':
      title = 'Other owners on this account';
      break;
    case '2':
      title = 'Other admins on this account';
      break;
    case '3':
      title = 'Account owners on this account';
      break;
    case '4':
      title = 'Admins on this account';
      break;
  }

  return (
    <Box className="mb-4" padded={false} white>
      <Box.Section className="border-bottom">
        <h2>
          {title}
        </h2>
      </Box.Section>
      <table className="w-100" cellPadding="0" cellSpacing="0">
        <tbody>
          {users.map((user) => {
            if (user.id === me.id) {
              return null;
            }

            return (
              <tr key={user.id}>
                <td className="border-bottom p-2 pl-3" style={{ width: '25%' }}>
                  <Avatar user={user} className="mr-2" />
                  {user.name}
                </td>
                <td className="border-bottom p-2" style={{ width: '33%' }}>
                  {user.job && (
                    <>{user.job}, </>
                  )}
                  {user.organization}
                </td>
                <td className="border-bottom p-2 pr-3 text-right" style={{ width: '25%' }}>
                  {((isOwner && access === '1') || ((isOwner || isAdmin) && access === '2')) ? (
                    <a
                      href="#"
                      className="d-flex align-items-center justify-content-end text-link"
                      onClick={e => handleRevokeClick(e, user)}
                    >
                      <Icon name="be-symbol-delete" className="mr-2" />
                      Revoke Powers
                    </a>
                  ) : (
                    <a
                      href={`mailto:${user.email}`}
                      className="d-flex align-items-center justify-content-end text-link"
                    >
                      <Icon name="be-symbol-email" className="mr-2" />
                      Email Owner
                    </a>
                  )}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
      {(access === '1' || access === '2') && (
        <Box.Section className="dark no-border-top">
          <Widget>
            <label>
              {access === '1' ? 'Add another owner:' : 'Add another admin:'}
            </label>
            <div className="d-flex">
              <Input
                name="name"
                value={name}
                onChange={e => setName(e.target.value)}
                className="mr-2"
                placeholder="Name"
              />
              <Input
                name="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                className="mr-2"
                placeholder="Email"
              />
              <Button variant="main" onClick={handleInviteClick}>
                Invite and grant powers
              </Button>
            </div>
          </Widget>
        </Box.Section>
      )}
    </Box>
  );
};

Owners.propTypes = {
  isOwner: PropTypes.bool.isRequired,
  isAdmin: PropTypes.bool.isRequired,
  users:   PropTypes.array.isRequired,
  access:  PropTypes.string.isRequired
};

export default Owners;
