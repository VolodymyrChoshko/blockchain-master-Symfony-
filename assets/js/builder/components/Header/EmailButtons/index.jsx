import React from 'react';
import { useSelector } from 'react-redux';
import { useBuilderActions } from 'builder/actions/builderActions';
import { useUIActions } from 'builder/actions/uiActions';
import { Button, ButtonGroup, Icon } from 'components';
import useMe from 'dashboard/hooks/useMe';

const EmailButtons = () => {
  const me = useMe();
  const builderActions = useBuilderActions();
  const uiActions = useUIActions();
  const editing = useSelector(state => state.builder.editing);
  const room = useSelector(state => state.builder.room);
  const billingPlan = useSelector(state => state.template.billingPlan);
  const isLoaded = useSelector(state => state.builder.isLoaded);
  const templateVersion = useSelector(state => state.builder.templateVersion);
  const version = useSelector(state => state.builder.version);
  const canEdit = me.isOwner || billingPlan.canTeamEdit;
  let isSomeoneEditing = false;
  room.forEach((u) => {
    if (u.state === 'editing') {
      isSomeoneEditing = true;
    }
  });

  /**
   *
   */
  const handleEditClick = () => {
    if (isLoaded) {
      builderActions.editing(true);
    }
  };

  /**
   *
   */
  const handleShareClick = () => {
    if (isLoaded) {
      uiActions.modal('shareEmail', true);
    }
  };

  /**
   *
   */
  const handleExportClick = () => {
    if (isLoaded) {
      builderActions.exportEmail();
    }
  };

  /**
   *
   */
  const handleUpdateClick = () => {
    uiActions.confirm('', 'There has been an update to the template. Do you want to update your email to take into account the latest template changes?', [
      {
        text:    'Okay',
        variant: 'main',
        action:  () => {
          builderActions.emailUpgrade();
        }
      },
      {
        text:    'Later',
        variant: 'alt'
      }
    ]);
  };

  return (
    <>
      <div className="builder-header-buttons mr-3">
        <ButtonGroup className="d-flex align-items-center">
          {editing ? (
            <Button variant="save" className="mb-0 mr-2" onClick={builderActions.save}>
              SAVE
            </Button>
          ) : (
            <Button
              variant="edit"
              className="mb-0 mr-2"
              onClick={handleEditClick}
              disabled={isSomeoneEditing || !canEdit}
            >
              EDIT
            </Button>
          )}
          {editing && (
            <Button
              variant="transparent"
              className="mb-0 d-inline-flex align-items-center"
              onClick={() => builderActions.cancelEditing()}
              disabled={!editing}
            >
              <Icon name="be-symbol-delete" mr />
              Cancel Changes
            </Button>
          )}
          {(templateVersion > version && editing) && (
            <Button
              title="Update"
              variant="transparent"
              style={{ marginTop: 4 }}
              className="mb-0 d-inline-flex align-items-center pointer"
              onClick={handleUpdateClick}
            >
              <Icon name="be-symbol-update" />
            </Button>
          )}
          {!editing && (
            <Button
              variant="transparent"
              className="mb-0 pl-2 pr-2 d-inline-flex align-items-center"
              onClick={handleShareClick}
            >
              <Icon name="be-symbol-share" className="builder-header-icon" mr />
              Share
            </Button>
          )}
          {!editing && (
            <Button
              variant="transparent"
              className="mb-0 pl-2 pr-0 d-inline-flex align-items-center"
              onClick={handleExportClick}
            >
              <Icon name="be-symbol-export" className="builder-header-icon" mr />
              Export
            </Button>
          )}
        </ButtonGroup>
      </div>
    </>
  );
};

export default EmailButtons;
