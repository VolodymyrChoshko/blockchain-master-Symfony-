import useMe from 'dashboard/hooks/useMe';
import React, { useEffect } from 'react';
import { useSelector } from 'react-redux';
import { useIntegrationsActions } from 'dashboard/actions/integrationsActions';
import Box from 'dashboard/components/Box';
import Notice from 'dashboard/pages/Templates/Notice';
import BillingNotice from 'components/BillingNotice';
import { Link } from 'react-router-dom';
import Sources from './Sources';
import Add from './Add';

const Integrations = () => {
  const me = useMe();
  const integrationsActions = useIntegrationsActions();
  const isIntegrationsDisabled = useSelector(state => state.integrations.isIntegrationsDisabled);
  const billingPlan = useSelector(state => state.template.billingPlan);
  const { isOwner } = me;

  /**
   *
   */
  useEffect(() => {
    integrationsActions.load();
  }, []);

  return (
    <Box className="mt-4 mb-4" padded={false} shadow={false} overflowHidden={false}>
      <div className="builder-header-info mb-2 pl-0">
        <Link to="/">
          ← Back to Dashboard
        </Link>
      </div>
      <div className="mb-4">
        <h1>Integrations</h1>
        <h2 className="m-0">
          Direct connections to other tools.
        </h2>
      </div>

      {(isOwner && !billingPlan.isTrialIntegration && !billingPlan.isTrialComplete) && (
        <BillingNotice className="mb-3">
          Adding an integration will start your 30 day trial.
        </BillingNotice>
      )}
      {isIntegrationsDisabled && (
        <Notice closeable={false}>
          Integration functionality has been disabled.
          Please <Link to="/billing">add a payment option</Link> to enable functionality.
        </Notice>
      )}

      <Box padded={false} white>
        <Sources />
        <Add />
      </Box>
    </Box>
  );
};

export default Integrations;
