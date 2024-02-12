import React from 'react';
import { Link } from 'react-router-dom';
import { useSelector } from 'react-redux';
import Notice from 'dashboard/pages/Templates/Notice';
import useMe from 'dashboard/hooks/useMe';

const Notices = () => {
  const me = useMe();
  const firstUseNotice = useSelector(state => state.template.firstUseNotice);
  const notices = useSelector(state => state.template.notices);
  const billingPlan = useSelector(state => state.template.billingPlan);
  const { isOwner } = me;

  return (
    <>
      {(isOwner && billingPlan.isDeclined && !billingPlan.isPaused) && (
        <Notice closeable={false}>
          Your credit card has been declined.
          Please <Link to="/billing">update your billing information</Link> to avoid
          interruptions of service.
        </Notice>
      )}
      {(isOwner && billingPlan.isPaused) && (
        <Notice closeable={false}>
          Your account is on hold.
          Please <Link to="/billing">update your billing information</Link>.
        </Notice>
      )}
      {(isOwner && billingPlan.isTrial && !billingPlan.hasCreditCard) && (
        <Notice closeable={false}>
          You have {billingPlan.daysUntilTrialEnds} days left on your
          trial. <Link to="/billing">Add your payment info</Link>.
        </Notice>
      )}
      {(isOwner && billingPlan.isTrialIntegration && !billingPlan.hasCreditCard) && (
        <Notice closeable={false}>
          You have {billingPlan.daysUntilTrialEnds} days left on your
          trial. <Link to="/billing">Add your payment info</Link>.
        </Notice>
      )}
      {/* eslint-disable-next-line max-len */}
      {(isOwner && billingPlan.isSolo && billingPlan.isTrialComplete && !billingPlan.hasCreditCard && !billingPlan.isDowngraded && billingPlan.hasTeamMembers) && (
        <Notice closeable={false}>
          Your trial period has run out! Your team can no
          longer build and edit emails. <Link to="/billing">Upgrade today</Link>.
        </Notice>
      )}
      {(!isOwner && (billingPlan.id && !billingPlan.canTeamEdit)) && (
        <Notice closeable={false}>
          Access to this template has been disabled.
          Please <a href={`mailto:${billingPlan.ownerEmail}`}>contact the template owner</a>.
        </Notice>
      )}
      {firstUseNotice === false && (
        <Notice>
          This is the dashboard for your template and its emails.
          You can create new emails and edit current ones. You can also
          invite your coworkers to build and edit emails. We&apos;ve added this
          example template for you to see how the editor works.
        </Notice>
      )}
      {notices.map(notice => (
        <Notice key={notice.id} notice={notice} />
      ))}
    </>
  );
};

export default Notices;
