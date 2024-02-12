import React, { Suspense } from 'react';
import ReactDOM from 'react-dom';
import { useSelector } from 'react-redux';
import { useUIActions } from 'builder/actions/uiActions';
import Loading from 'components/Loading';
import objects from 'utils/objects';

const modalComponents = {
  share:               React.lazy(() => import('builder/modals/ShareModal')),
  import:              React.lazy(() => import('builder/modals/ImportModal')),
  html:                React.lazy(() => import('builder/modals/HtmlModal')),
  shareEmail:          React.lazy(() => import('builder/modals/ShareEmail')),
  creditCard:          React.lazy(() => import('builder/modals/CreditCardModal')),
  newTemplate:         React.lazy(() => import('builder/modals/NewTemplateModal')),
  exportEmail:         React.lazy(() => import('builder/modals/ExportEmailModal')),
  sourceBrowse:        React.lazy(() => import('builder/modals/SourceBrowseModal')),
  blockSettings:       React.lazy(() => import('builder/modals/BlockSettingsModal')),
  emailSettings:       React.lazy(() => import('builder/modals/EmailSettingsModal')),
  changePassword:      React.lazy(() => import('builder/modals/ChangePasswordModal')),
  templateSettings:    React.lazy(() => import('builder/modals/TemplateSettingsModal')),
  emailTemplateSend:   React.lazy(() => import('builder/modals/EmailTemplateSendModal')),
  integrationSettings: React.lazy(() => import('builder/modals/IntegrationSettings')),
};

const ModalsContainer = () => {
  const uiActions = useUIActions();
  const modals = useSelector(state => state.ui.modals);

  /**
   * @param {Event} e
   * @param {string} name
   */
  const handleHidden = (e, name) => {
    uiActions.modal(name, false);
  };

  const opened = [];
  objects.forEach(modals, ({ open, meta, close }, name) => {
    if (open) {
      const Component = modalComponents[name];
      if (Component) {
        opened.push(
          <Component
            key={name}
            {...meta}
            closeModal={close}
            onHidden={e => handleHidden(e, name)}
            open
          />
        );
      }
    }
  });

  return (
    <Suspense fallback={<Loading />}>
      {ReactDOM.createPortal(opened, document.body)}
    </Suspense>
  );
};

export default ModalsContainer;
