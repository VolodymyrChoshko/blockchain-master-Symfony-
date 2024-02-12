import React, { useEffect } from 'react';
import { useSelector } from 'react-redux';
import { useBuilderActions } from 'builder/actions/builderActions';
import { useUIActions } from 'builder/actions/uiActions';
import { useRuleActions } from 'builder/actions/ruleActions';
import Icon from 'components/Icon';
import Toolbar from 'builder/components/Toolbar';

const ToolbarLeft = () => {
  const builderActions = useBuilderActions();
  const uiActions = useUIActions();
  const rulesActions = useRuleActions();
  const editing = useSelector(state => state.builder.editing);
  const libraries = useSelector(state => state.builder.libraries);
  const sections = useSelector(state => state.builder.sections);
  const components = useSelector(state => state.builder.components);
  const mode = useSelector(state => state.builder.mode);
  const ruleMode = useSelector(state => state.rules.mode);
  const gridVisible = useSelector(state => state.builder.gridVisible);
  const sidebarSection = useSelector(state => state.ui.sidebarSection);
  const isEditingRules = useSelector(state => state.rules.isEditing);
  const isEditingHtml = useSelector(state => state.rules.isEditingHtml);

  /**
   *
   */
  useEffect(() => {
    if (sidebarSection === '') {
      if (sections.length > 0) {
        uiActions.uiSidebarSection('sections');
      } else if (components.length > 0) {
        uiActions.uiSidebarSection('components');
      }
    }
  }, [sidebarSection, sections, components]);

  const buttons = [];

  if (isEditingRules) {
    buttons.push(
      <Toolbar.Button
        key="rules"
        title="Editable Rules"
        active={!isEditingHtml && ruleMode === 'editable'}
        onClick={() => {
          rulesActions.setEditingHtml(false);
          rulesActions.setMode('editable');
        }}
      >
        <Icon name="be-symbol-content" />
      </Toolbar.Button>
    );

    buttons.push(
      <Toolbar.Button
        key="sections"
        title="Section Rules"
        active={!isEditingHtml && ruleMode === 'sections'}
        onClick={() => {
          rulesActions.setEditingHtml(false);
          rulesActions.setMode('sections');
        }}
      >
        <Icon name="be-symbol-sections" />
      </Toolbar.Button>
    );
    buttons.push(
      <Toolbar.Button
        key="regions"
        title="Region Rules"
        active={!isEditingHtml && ruleMode === 'regions'}
        onClick={() => {
          rulesActions.setEditingHtml(false);
          rulesActions.setMode('regions');
        }}
      >
        <Icon name="be-symbol-region" />
      </Toolbar.Button>
    );
    buttons.push(
      <Toolbar.Button
        key="components"
        title="Component Rules"
        active={!isEditingHtml && ruleMode === 'components'}
        onClick={() => {
          rulesActions.setEditingHtml(false);
          rulesActions.setMode('components');
        }}
      >
        <Icon name="be-symbol-components" />
      </Toolbar.Button>
    );
    buttons.push(
      <Toolbar.Button
        key="html"
        title="HTML"
        active={isEditingHtml}
        onClick={() => rulesActions.setEditingHtml(!isEditingHtml)}
      >
        <Icon name="be-symbol-code" />
      </Toolbar.Button>
    );
  }
  if (editing && !isEditingRules) {
    if (sections.length > 0) {
      buttons.push(
        <Toolbar.Button
          key="sections"
          title="Sections"
          active={sidebarSection === 'sections'}
          onClick={() => uiActions.uiSidebarSection('sections')}
        >
          <Icon name="be-symbol-sections" />
        </Toolbar.Button>
      );
    }
    if (components.length > 0) {
      buttons.push(
        <Toolbar.Button
          key="components"
          title="Components"
          active={sidebarSection === 'components'}
          onClick={() => uiActions.uiSidebarSection('components')}
        >
          <Icon name="be-symbol-components" />
        </Toolbar.Button>
      );
    }
    if (libraries.length > 0 && !isEditingRules) {
      buttons.push(
        <Toolbar.Button
          key="libraries"
          title="Section Library"
          active={sidebarSection === 'libraries'}
          onClick={() => uiActions.uiSidebarSection('libraries')}
        >
          <Icon name="be-symbol-pin" />
        </Toolbar.Button>
      );
    }

    if ((sections.length > 0 || components.length > 0 || libraries.length > 0) && !isEditingRules) {
      buttons.push(<Toolbar.Separator key="break1" />);
    }

    if (mode.indexOf('preview') === -1 && !isEditingRules) {
      buttons.push(
        <Toolbar.Button
          key="layouts"
          title="Layouts"
          active={sidebarSection === 'layouts'}
          onClick={() => uiActions.uiSidebarSection('layouts')}
        >
          <Icon name="be-symbol-layout" />
        </Toolbar.Button>
      );
    }
    if (mode === 'email') {
      buttons.push(
        <Toolbar.Button
          key="settings"
          title="Settings"
          onClick={() => builderActions.emailSettings()}
        >
          <Icon name="be-symbol-settings" />
        </Toolbar.Button>
      );
    }

    if ((mode === 'email' || mode.indexOf('preview') === -1) && !isEditingRules) {
      buttons.push(<Toolbar.Separator key="break2" />);
    }

    if (__ENV__ === 'development') {
      buttons.push(
        <Toolbar.Button
          key="debug-update-blocks"
          title="(Debug) Update Blocks"
          onClick={builderActions.updateBlocks}
        >
          <Icon name="fa-th-large" />
        </Toolbar.Button>
      );
      buttons.push(
        <Toolbar.Button
          key="debug-blocks"
          title="(Debug) Blocks"
          onClick={() => uiActions.uiSidebarSection('blocks')}
        >
          <Icon name="fa-cube" />
        </Toolbar.Button>
      );
      buttons.push(
        <Toolbar.Button
          key="debug-grid"
          title="(Debug) Toggle grid"
          active={gridVisible}
          onClick={builderActions.toggleGrid}
        >
          <Icon name="fa-border-none" />
        </Toolbar.Button>
      );
    }
  }

  return (
    <Toolbar direction="left">
      {buttons}
    </Toolbar>
  );
};

export default ToolbarLeft;
