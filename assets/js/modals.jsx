import ModalsContainer from 'components/ModalsContainer';
import ToastContainer from 'components/ToastContainer';
import ConfirmContainer from 'components/ConfirmContainer';
import { getCookie } from 'dashboard/utils/cookies';
import { ThemeProvider } from 'styled-components';
import React, { useEffect } from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import configureStore from 'dashboard/store/configureStore';
import { lightTheme, darkTheme } from 'theme';
import useMe from 'dashboard/hooks/useMe';

const App = () => {
  let me = useMe();

  /**
   *
   */
  useEffect(() => {
    document.dispatchEvent(new CustomEvent('be-modals-loaded'));
  }, []);

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
    <ThemeProvider theme={theme}>
      <ModalsContainer />
      <ToastContainer />
      <ConfirmContainer />
    </ThemeProvider>
  );
};

ReactDOM.render(
  <Provider store={configureStore()}>
    <App />
  </Provider>,
  document.getElementById('modals-mount')
);
