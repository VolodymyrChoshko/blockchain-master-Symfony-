import React, { useEffect } from 'react';
import { useSelector } from 'react-redux';
import { useParams } from 'react-router-dom';
import ErrorBoundary from 'components/ErrorBoundary';
import { useBuilderActions } from 'builder/actions/builderActions';
import { useIntegrationsActions } from 'dashboard/actions/integrationsActions';
import Header from 'builder/components/Header';
import Canvas from 'builder/components/Canvas';
import Loading from 'components/Loading';
import Sidebar from 'builder/sidebars/LeftSidebar';
import RulesSidebar from 'builder/sidebars/RulesSidebar';
import ActivitySidebar from 'builder/sidebars/ActivitySidebar';
import ChecklistSidebar from 'builder/sidebars/ChecklistSidebar';
import ToolbarLeft from 'builder/components/ToolbarLeft';
import ToolbarRight from 'builder/components/ToolbarRight';
import UpgradingStatus from 'builder/components/UpgradingStatus';
import Cropper from 'builder/components/Cropper';
import { Container, Body } from './styles';

const App = ({ mode }) => {
  const params             = useParams();
  const builderActions     = useBuilderActions();
  const integrationActions = useIntegrationsActions();
  const builderMode        = useSelector(state => state.builder.mode);
  const editing            = useSelector(state => state.builder.editing);
  const isLoaded           = useSelector(state => state.builder.isLoaded);
  const isEditingRules     = useSelector(state => state.rules.isEditing);
  const upgrading          = useSelector(state => state.builder.upgrading);

  /**
   * @param e
   */
  // eslint-disable-next-line consistent-return
  const handleBeforeUnload = (e) => {
    if ((editing && builderMode.indexOf('template') !== 0) || upgrading.length !== 0) {
      e.returnValue = 'Are you sure you want to leave? Changes may not be saved.';
      return 'Are you sure you want to leave? Changes may not be saved.';
    }
  };

  /**
   *
   */
  useEffect(() => {
    const emailVersion     = parseInt(params.emailVersion || '0', 10);
    const templateVersion  = parseInt(params.templateVersion || '0', 10);
    const isCurrentVersion = params.emailVersion === undefined && params.templateVersion === undefined;

    builderActions.open(false, {
      id:           parseInt(params.id, 10),
      previewToken: params.token || '',
      tid:          parseInt(params.tid || '0', 10),
      isCurrentVersion,
      emailVersion,
      templateVersion,
      mode,
    });
    integrationActions.load();

    return () => {
      builderActions.clearState();
    };
  }, []);

  /**
   *
   */
  useEffect(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
    };
  }, [editing, upgrading, builderMode]);

  return (
    <Container className="builder">
      <Header />
      <ErrorBoundary>
        <Body>
          {isLoaded ? (
            <>
              <ToolbarLeft />
              {isEditingRules ? (
                <RulesSidebar />
              ) : (
                <Sidebar />
              )}
              <Canvas />
              <ActivitySidebar />
              <ChecklistSidebar />
              <ToolbarRight />
            </>
          ) : (
            <>
              <ToolbarLeft />
              <Sidebar />
              <div style={{ flexGrow: 1, backgroundColor: '#FFF' }}>
                <Loading />
              </div>
              <ActivitySidebar />
              <ToolbarRight />
            </>
          )}
        </Body>
      </ErrorBoundary>
      <Cropper />
      <UpgradingStatus />
      <div id="builder-popup-menu-mount" />
    </Container>
  );
};

export default App;
