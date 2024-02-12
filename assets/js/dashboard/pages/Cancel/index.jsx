import React from 'react';
import { useNavigate } from 'react-router-dom';
import Box from 'dashboard/components/Box';
import Button from 'components/Button';
import { loading } from 'utils';
import api from 'lib/api';
import router from 'lib/router';

const Cancel = () => {
  const navigate = useNavigate();

  /**
   *
   */
  const handleNevermindClick = () => {
    navigate('/');
  };

  /**
   *
   */
  const handleCancelClick = () => {
    loading(true);
    api.req('DELETE', router.generate('api_v1_account_cancel'))
      .then(() => {
        document.location = 'https://app.blocksedit.com/login';
      })
      .finally(() => {
        loading(false);
      });
  };

  return (
    <Box className="mt-4 mb-4" padded={false} shadow={false} overflowHidden={false}>
      <div className="mb-4">
        <h1>
          Cancel my account
        </h1>
        <h2 className="mb-4">
          Are you sure you want to cancel your Blocks Edit account?
        </h2>
      </div>
      <Box white>
        <p>Thanks for using Blocks Edit. We&apos;ll be sorry to see you go!</p>
        <p>Be sure to export any data you want to keep before you cancel. Once your account is canceled:</p>
        <ul className="mb-4 ml-5">
          <li>
            Your account will be closed immediately
          </li>
          <li>
            You won&apos;t be charged again
          </li>
          <li>
            All templates associated with your account will be deleted, including ones that someone else has access to
          </li>
          <li>
            Everything will be completely deleted from our backups after 30 days
          </li>
        </ul>
        <Button variant="main" className="mr-2" onClick={handleCancelClick}>
          Cancel my account
        </Button>
        <Button variant="alt" onClick={handleNevermindClick}>
          Nevermind, I&apos;ll keep my account
        </Button>
        <p className="mt-3">
          Any questions? <a href="/help">Contact support for help</a>
        </p>
      </Box>
    </Box>
  );
};

export default Cancel;
