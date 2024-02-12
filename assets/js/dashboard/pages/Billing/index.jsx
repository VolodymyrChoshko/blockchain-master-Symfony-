import React, { useEffect } from 'react';
import { useSelector } from 'react-redux';
import { useBillingActions } from 'dashboard/actions/billingActions';
import { useUIActions } from 'builder/actions/uiActions';
import Box from 'dashboard/components/Box';
import Button from 'components/Button';
import { Link } from 'react-router-dom';

const Billing = () => {
  const uiActions = useUIActions();
  const billingActions = useBillingActions();
  const billing = useSelector(state => state.billing);
  const { billingPlan, nextInvoice, invoices, creditCard } = billing;

  /**
   *
   */
  useEffect(() => {
    billingActions.load();
  }, []);

  /**
   *
   */
  const handleUpdateCardClick = () => {
    uiActions.modal('creditCard', true, {
      onComplete: () => {
        billingActions.load();
      }
    });
  };

  /**
   *
   */
  const handleRemoveCardClick = () => {
    uiActions.confirm('', 'Are you sure you want to remove your credit card?', [
      {
        text:    'Yes',
        variant: 'danger',
        action:  () => {
          billingActions.removeCreditCard();
        }
      },
      {
        text:    'No',
        variant: 'alt',
      }
    ]);
  };

  /**
   *
   */
  const handleUpgradeClick = () => {
    if (!creditCard) {
      uiActions.modal('creditCard', true, {
        onComplete: () => {
          billingActions.upgrade();
        }
      });
    } else {
      billingActions.upgrade();
    }
  };

  /**
   *
   */
  const handleDowngradeClick = () => {
    // eslint-disable-next-line max-len
    uiActions.confirm('', 'Any team members you\'ve invited will no longer have access to build and update emails from your templates. Are you sure you want to proceed?', [
      {
        text:    'Yes',
        variant: 'danger',
        action:  () => {
          billingActions.downgrade();
        }
      },
      {
        text:    'No',
        variant: 'alt'
      }
    ]);
  };

  if (!billingPlan) {
    return null;
  }

  return (
    <Box className="mt-4 mb-4" padded={false} shadow={false} overflowHidden={false}>
      <div className="builder-header-info mb-2 pl-0">
        <Link to="/">
          ← Back to Dashboard
        </Link>
      </div>
      <h1 className="mb-2">Billing and Invoices</h1>

      <Box className="mb-4" white>
        {(nextInvoice && nextInvoice.amountCents !== 0) && (
          <h2 className="mb-2">
            Your next bill is for ${(nextInvoice.amountCents / 100).toFixed(2)},
            due {billingPlan.nextBillingDate}.
          </h2>
        )}
        {billingPlan.isSolo && (
          <Button variant="main" className="mb-3" onClick={handleUpgradeClick}>
            Upgrade to Blocks Edit Team
          </Button>
        )}

        <table className="table table-bordered table-striped mb-4">
          <tbody>
            {nextInvoice.items.map(item => (
              <tr key={item.description}>
                <td>{item.description}</td>
                <td className="text-right">
                  {item.type === 'discount' ? (
                    <>
                      -${(item.amountCents / 100).toFixed(2).replace('-', '')}
                    </>
                  ) : (
                    <>
                      ${(item.amountCents / 100).toFixed(2)}/month
                    </>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {creditCard ? (
          <p>
            To be charged on your credit card: {creditCard.brand} ending in {creditCard.number4}
            &nbsp;&ndash; <Button variant="link" className="pl-0 pr-0" onClick={handleUpdateCardClick}>Update card</Button>
            &nbsp;&ndash; <Button variant="link" className="pl-0 pr-0" onClick={handleRemoveCardClick}>Remove card</Button>
          </p>
        ) : (
          <p>
            You don&apos;t have a credit card on file
            &nbsp;&ndash; <Button variant="link" className="pl-0 pr-0" onClick={handleUpdateCardClick}>Add card</Button>
          </p>
        )}

        <p className="font-size-sm mb-0">
          To make a lump sum payment towards a yearly payment, or just to simplify your
          billing, <a href="https://blocksedit.com/support/" target="_blank" rel="noopener noreferrer">contact us</a>.
        </p>

        {billingPlan.isTeam && (
          <Button variant="alt" className="mt-3" onClick={handleDowngradeClick}>
            Downgrade to Blocks Edit Solo
          </Button>
        )}
      </Box>

      {invoices.length > 0 && (
        <Box padded={false} white>
          <Box.Section className="border-bottom">
            <h2>
              Invoices
            </h2>
          </Box.Section>
          <Box.Section style={{ borderTop: 0 }}>
            <table className="table table-bordered table-striped">
              <tbody>
                {invoices.map(invoice => (
                  <tr key={invoice.id}>
                    <td>#{invoice.id}</td>
                    <td>
                      <a href={invoice.fileUrl} target="_blank" rel="noopener noreferrer">
                        ${(invoice.amountCents / 100).toFixed(2)} for {invoice.description}
                      </a>
                    </td>
                    <td className="text-right">
                      {invoice.dateCreated}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </Box.Section>
        </Box>
      )}
    </Box>
  );
};

export default Billing;
