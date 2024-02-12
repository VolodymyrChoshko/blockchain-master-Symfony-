import React, { useEffect } from 'react';
import { useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { useUsersActions } from 'dashboard/actions/usersActions';
import Box from 'dashboard/components/Box';
import ChangeName from './ChangeName';
import Owners from './Owners';

const Account = () => {
  const usersActions = useUsersActions();
  const account = useSelector(state => state.users.account);

  /**
   *
   */
  useEffect(() => {
    usersActions.fetchAccount();
  }, []);

  if (!account) {
    return null;
  }

  const { isOwner, isAdmin, isEditor, owners, admins, organization } = account;

  return (
    <Box className="mt-4 mb-4" padded={false} shadow={false} overflowHidden={false}>
      <div className="mb-4">
        {isOwner && (
          <>
            <div className="builder-header-info mb-2 pl-0">
              <Link to="/">
                ← Back to Dashboard
              </Link>
            </div>
            <h1>
              You are an account owner for {organization.org_name}
            </h1>
            <h2 className="m-0">
              Owners can view everything on the account.
            </h2>
            <Link to="/billing" className="d-block mb-2 mt-2">
              View billing and invoices &rarr;
            </Link>
            <a href="mailto:support@blocksedit.com">
              Request an additional organization
            </a>
          </>
        )}
        {isAdmin && (
          <>
            <div className="builder-header-info mb-2 pl-0">
              <Link to="/">
                ← Back to Dashboard
              </Link>
            </div>
            <h1>
              You are an admin for {organization.org_name}
            </h1>
            <h2 className="m-0">
              Admins can view everything on the account.
            </h2>
            <a href="mailto:support@blocksedit.com" className="d-block mt-2">
              Request an additional organization
            </a>
          </>
        )}
        {isEditor && (
          <>
            <div className="builder-header-info mb-2 pl-0">
              <Link to="/">
                ← Back to Dashboard
              </Link>
            </div>
            <h1>
              You are an editor for {organization.org_name}
            </h1>
            <h2 className="m-0">
              Editors can add and update emails.
            </h2>
          </>
        )}
      </div>

      {isOwner && (
        <ChangeName account={account} />
      )}
      {(isOwner || isAdmin) && (
        <Owners isOwner={isOwner} isAdmin={isAdmin} isEditor={isEditor} users={account.owners} access="1" />
      )}
      {(isOwner || isAdmin) && (
        <Owners isOwner={isOwner} isAdmin={isAdmin} isEditor={isEditor} users={account.admins} access="2" />
      )}
      {(isEditor && owners.length > 0) && (
        <Owners isOwner={isOwner} isAdmin={isAdmin} isEditor={isEditor} users={account.owners} access="3" />
      )}
      {(isEditor && admins.length > 0) && (
        <Owners isOwner={isOwner} isAdmin={isAdmin} isEditor={isEditor} users={account.admins} access="4" />
      )}

      {isOwner && (
        <div>
          <h2>
            <Link to="/account/cancel">
              Cancel this account &rarr;
            </Link>
          </h2>
        </div>
      )}
    </Box>
  );
};

export default Account;
