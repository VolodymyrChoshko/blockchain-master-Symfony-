import React from 'react';
import { useSelector } from 'react-redux';
import { useUIActions } from 'builder/actions/uiActions';
import { useBuilderActions } from 'builder/actions/builderActions';
import Icon from 'components/Icon';
import Toolbar from 'builder/components/Toolbar';

const ToolbarRight = () => {
  const uiActions = useUIActions();
  const builderActions = useBuilderActions();
  const previewDevice = useSelector(state => state.ui.previewDevice);
  const colorScheme = useSelector(state => state.builder.colorScheme);
  const hasColorScheme = useSelector(state => state.builder.hasColorScheme);
  const isActivityOpen = useSelector(state => state.ui.isActivityOpen);
  const isChecklistOpen = useSelector(state => state.ui.isChecklistOpen);
  const isLoaded = useSelector(state => state.builder.isLoaded);
  const checklistSettings = useSelector(state => state.checklist.settings);
  const mode = useSelector(state => state.builder.mode);

  const buttons = [];
  buttons.push(
    <Toolbar.Button
      key="device-desktop"
      title="Desktop"
      active={previewDevice === 'desktop'}
      onClick={() => {
        if (isLoaded) {
          uiActions.uiPreviewDevice('desktop');
        }
      }}
    >
      <Icon name="be-symbol-desktop" />
    </Toolbar.Button>
  );
  buttons.push(
    <Toolbar.Button
      key="device-mobile"
      title="Mobile"
      onClick={() => {
        if (isLoaded) {
          uiActions.uiPreviewDevice('mobile');
        }
      }}
      active={previewDevice === 'mobile'}
    >
      <Icon name="be-symbol-mobile" />
    </Toolbar.Button>
  );
  if (hasColorScheme) {
    buttons.push(
      <Toolbar.Button
        key="device-dark"
        title={colorScheme === 'dark' ? 'Light Mode' : 'Dark Mode'}
        onClick={() => {
          if (isLoaded) {
            builderActions.toggleColorScheme();
          }
        }}
        active={colorScheme === 'dark'}
      >
        <Icon name="be-symbol-dark-mode" />
      </Toolbar.Button>
    );
  }

  buttons.push(<Toolbar.Separator key="break1" />);

  buttons.push(
    <Toolbar.Button
      key="activity"
      title="Activity"
      onClick={() => {
        if (isLoaded) {
          uiActions.toggleActivity();
        }
      }}
      active={isActivityOpen}
    >
      <Icon name="be-symbol-activity" />
    </Toolbar.Button>
  );

  if (mode.indexOf('email') !== -1 && checklistSettings.enabled) {
    buttons.push(
      <Toolbar.Button
        key="checklist"
        title="Checklist"
        onClick={() => {
          if (isLoaded) {
            uiActions.toggleChecklist();
          }
        }}
        active={isChecklistOpen}
      >
        <Icon name="be-symbol-checklist" />
      </Toolbar.Button>
    );
  }

  return (
    <Toolbar direction="right">
      {isLoaded ? buttons : null}
    </Toolbar>
  );
};

export default ToolbarRight;
