import React, { useEffect } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useSelector } from 'react-redux';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import Box from 'dashboard/components/Box';
import BillingNotice from 'components/BillingNotice';
import InviteAccountUsers from './InviteAccountUsers';
import InviteUsers from './InviteUsers';
import Users from './Users';

const People = () => {
  const params = useParams();
  const templateActions = useTemplateActions();
  const templatePeople = useSelector(state => state.template.templatePeople);
  const id = parseInt(params.id, 10);

  /**
   *
   */
  useEffect(() => {
    templateActions.loadTemplatePeople(params.id);

    return () => {
      templateActions.resetTemplatePeople();
    };
  }, []);

  if (!templatePeople) {
    return null;
  }

  const { tmpTitle, accountUsers, users, invites, isOwner, billingPlan, showUpgradeError } = templatePeople;

  return (
    <>
      {(billingPlan.isSolo && billingPlan.isTrialComplete === false) && (
        <BillingNotice className="mt-4">
          Inviting team members will upgrade you to Blocks Edit Team and start your 30 day trial.
        </BillingNotice>
      )}
      {showUpgradeError && (
        <BillingNotice className="mt-4">
          <a href="#">
            Upgrade to Blocks Edit Team to add team members
          </a>.
        </BillingNotice>
      )}
      <Box className="mt-4 mb-4" padded={false} shadow={false} overflowHidden={false}>
        <div className="builder-header-info mb-2 pl-0">
          <Link to="/">
            ← Back to Dashboard
          </Link>
        </div>
        <div className="mb-4">
          <h1>{tmpTitle} template team members</h1>
          <h2 className="m-0">
            People who can make changes to this template&apos;s emails
          </h2>
        </div>
        {accountUsers.length > 0 && (
          <InviteAccountUsers id={id} users={accountUsers} />
        )}

        <Box padded={false} white>
          <InviteUsers id={id} />
          <Users id={id} isOwner={isOwner} users={users} invites={invites} />
        </Box>
      </Box>
    </>
  );
};

export default People;
