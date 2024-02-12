import React, { useEffect, Suspense } from 'react';
import { useSelector } from 'react-redux';
import { Routes, Route, useLocation } from 'react-router-dom';
import ToastContainer from 'components/ToastContainer';
import ModalsContainer from 'components/ModalsContainer';
import { ThemeProvider } from 'styled-components';
import { getCookie } from 'dashboard/utils/cookies';
import { useSocketActions } from 'builder/actions/socketActions';
import Confirm from 'components/Confirm';
import Header from 'dashboard/components/Header';
import NotFound from 'dashboard/pages/NotFound';
import ServiceWorker from 'dashboard/components/ServiceWorker';
import ProtectedRoute from 'dashboard/components/ProtectedRoute';
import ConfirmContainer from 'components/ConfirmContainer';
import UploadingStatus from 'components/UploadingStatus';
import useMe from 'dashboard/hooks/useMe';
import { lightTheme, darkTheme, GlobalStyle } from 'theme';

const Templates = React.lazy(() => import('./pages/Templates'));
const Profile = React.lazy(() => import('./pages/Profile'));
const Builder = React.lazy(() => import('builder/App'));
const Account = React.lazy(() => import('./pages/Account'));
const Cancel = React.lazy(() => import('./pages/Cancel'));
const Login = React.lazy(() => import('./pages/Login'));
const ForgotPassword = React.lazy(() => import('./pages/ForgotPassword'));
const ResetPassword = React.lazy(() => import('./pages/ResetPassword'));
const Integrations = React.lazy(() => import('./pages/Integrations'));
const People = React.lazy(() => import('./pages/People'));
const Billing = React.lazy(() => import('./pages/Billing'));
const SignUp = React.lazy(() => import('./pages/SignUp'));

const App = () => {
  let me = useMe();
  const location = useLocation();
  const socketActions = useSocketActions();
  const isUpgrading = useSelector(state => state.ui.isUpgrading);
  const socket = useSelector(state => state.socket);

  /**
   *
   */
  useEffect(() => {
    if (me && socket.url) {
      socketActions.connect()
        .then(() => socketActions.subNotifications());
    }
  }, [me?.id, socket.url]);

  let theme = lightTheme;
  const om = { ...me };
  if (!me || me.isDarkMode === null) {
    me = {
      isDarkMode: window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
    };
  }
  if (om.isDarkMode === undefined && getCookie('isDarkMode') === '1') {
    theme = darkTheme;
  } else if (me.isDarkMode === true) {
    theme = darkTheme;
  }

  return (
    <Suspense fallback={<div />}>
      <ThemeProvider theme={theme}>
        {(location.pathname.indexOf('/build') === -1 && location.pathname.indexOf('/preview') === -1) && (
          <Header />
        )}
        <GlobalStyle />
        <Routes>
          <Route path="/" element={<ProtectedRoute><Templates /></ProtectedRoute>} />
          <Route path="/t/:id" element={<ProtectedRoute><Templates /></ProtectedRoute>} />
          <Route path="/profile" element={<ProtectedRoute><Profile /></ProtectedRoute>} />
          <Route path="/account" element={<ProtectedRoute><Account /></ProtectedRoute>} />
          <Route path="/account/cancel" element={<ProtectedRoute><Cancel /></ProtectedRoute>} />
          <Route path="/build/email/:tid/:id" element={<ProtectedRoute><Builder mode="email" /></ProtectedRoute>} />
          <Route
            path="/build/email/:tid/:id/versions/:emailVersion"
            element={<ProtectedRoute><Builder mode="email" /></ProtectedRoute>}
          />
          <Route
            path="/build/template/:id"
            element={<ProtectedRoute><Builder mode="template" /></ProtectedRoute>}
          />
          <Route
            path="/build/template/:id/versions/:templateVersion"
            element={<ProtectedRoute><Builder mode="template" /></ProtectedRoute>}
          />
          <Route
            path="/preview/t/:id/:token/:templateVersion"
            element={<Builder mode="template_preview" />}
          />
          <Route path="/preview/t/:id/:token" element={<Builder mode="template_preview" />} />
          <Route path="/preview/e/:tid/:id/:token/:emailVersion" element={<Builder mode="email_preview" />} />
          <Route path="/preview/e/:tid/:id/:token" element={<Builder mode="email_preview" />} />
          <Route path="/integrations" element={<ProtectedRoute><Integrations /></ProtectedRoute>} />
          <Route path="/people/:id" element={<ProtectedRoute><People /></ProtectedRoute>} />
          <Route path="/billing" element={<ProtectedRoute><Billing /></ProtectedRoute>} />
          <Route path="/login" element={<Login />} />
          <Route path="/signup" element={<SignUp />} />
          <Route path="/forgotpassword" element={<ForgotPassword />} />
          <Route path="/resetpassword/:token" element={<ResetPassword />} />
          <Route path="*" element={<NotFound />} />
        </Routes>
        <ToastContainer />
        <ModalsContainer />
        <ConfirmContainer />
        <UploadingStatus />
        <ServiceWorker />
        {isUpgrading && (
          <Confirm
            title="Template Upgrading."
            index={0}
            open
            buttons={null}
            options={{
              loading: true,
              status:  'Please wait one moment.'
            }}
          />
        )}
      </ThemeProvider>
    </Suspense>
  );
};

export default App;
