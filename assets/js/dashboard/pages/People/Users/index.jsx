import React from 'react';
import PropTypes from 'prop-types';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import { useUIActions } from 'builder/actions/uiActions';
import Avatar from 'dashboard/components/Avatar';
import Button from 'components/Button';
import Icon from 'components/Icon';

const Users = ({ id, isOwner, users, invites }) => {
  const templateActions = useTemplateActions();
  const uiActions = useUIActions();

  /**
   *
   */
  const handleRemoveClick = (uid, iid = 0) => {
    uiActions.confirm('', 'Are you sure you want to remove this person from the template?', () => {
      templateActions.removePerson(id, uid, iid);
    });
  };

  return (
    <div>
      <table className="w-100">
        <tbody>
          {users.map(user => (
            <tr key={user.id} className={user.accResponded ? '' : 'opacity-50'}>
              <td className="p-2 pl-3 border-bottom">
                <Avatar user={user} className="mr-2" />
                {user.name}
              </td>
              <td className="p-2 border-bottom">
                {(user.job || user.organization) && (
                  <>
                    {user.job && `${user.job}, `}
                    {user.organization}
                    <br />
                  </>
                )}
                {user.email}
              </td>
              <td className="p-2 pr-3 border-bottom text-right">
                {(!user.isOwner && !user.isAdmin) && (
                  <Button
                    title="Remove"
                    variant="transparent"
                    onClick={() => handleRemoveClick(user.id)}
                  >
                    <Icon name="be-symbol-delete" className="mr-2" />
                    Remove
                  </Button>
                )}
              </td>
            </tr>
          ))}
          {invites.map(invite => (
            <tr key={invite.id} className="opacity-50">
              <td className="p-2 pl-3 border-bottom">
                <Avatar user={{ name: invite.name, avatar: '' }} className="mr-2" />
                {invite.name}
              </td>
              <td className="p-2 border-bottom">
                {invite.email}
              </td>
              <td className="p-2 pr-3 border-bottom text-right">
                {isOwner && (
                  <Button
                    title="Remove"
                    variant="transparent"
                    onClick={() => handleRemoveClick(0, invite.id)}
                  >
                    <Icon name="be-symbol-delete" className="mr-2" />
                    Remove
                  </Button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

Users.propTypes = {
  id:      PropTypes.number.isRequired,
  isOwner: PropTypes.bool.isRequired,
  users:   PropTypes.array.isRequired,
  invites: PropTypes.array.isRequired,
};

export default Users;
