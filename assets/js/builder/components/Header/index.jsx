import Avatar from 'dashboard/components/Avatar';
import React from 'react';
import useMe from 'dashboard/hooks/useMe';
import { useSelector } from 'react-redux';
import { useRuleActions } from 'builder/actions/ruleActions';
import { useBuilderActions } from 'builder/actions/builderActions';
import { Button, Icon } from 'components';
import UserMenu from 'components/UserMenu';
import NotificationsMenu from 'components/NotificationsMenu';
import HelpButton from 'components/HelpButton';
import EmailButtons from './EmailButtons';
import TemplateButtons from './TemplateButtons';
import TemplatePreviewButtons from './TemplatePreviewButtons';
import BrandInfo from './BrandInfo';
import { Container, Editing } from './styles';

const Header = () => {
  const me = useMe();
  const ruleActions = useRuleActions();
  const builderActions = useBuilderActions();
  const builder = useSelector(state => state.builder);
  const mode = useSelector(state => state.builder.mode);
  const editing = useSelector(state => state.builder.editing);
  const historyLength = useSelector(state => state.builder.history.length);
  const futureLength = useSelector(state => state.builder.future.length);
  const room = useSelector(state => state.builder.room);
  const isLoaded = useSelector(state => state.builder.isLoaded);
  const isRulesEditing = useSelector(state => state.rules.isEditing);

  if (!mode) {
    // return null;
  }

  /**
   *
   */
  const handleClick = () => {
    if (isLoaded) {
      builderActions.deselectAll();
      ruleActions.deselectAll();
    }
  };

  return (
    <Container className="builder-header header-nav" onClick={handleClick}>
      <BrandInfo />
      <div className="flex-grow-1 flex-basis-0 flex-no-wrap">
        {room.length > 0 && (
          <div className="builder-header-others-editing d-inline-block">
            {room.map((u) => {
              if (u.state === 'editing') {
                return (
                  <div key={u.email} className="d-flex align-items-center">
                    <Avatar user={u} className="mr-2" />
                    <Editing>{u.name} is editing</Editing>
                  </div>
                );
              }
              return null;
            })}
          </div>
        )}
        {(editing && !isRulesEditing) && (
          <Button
            variant="transparent"
            className="mb-0"
            title="Undo"
            onClick={builderActions.undo}
            disabled={historyLength === 0}
          >
            <Icon name="be-symbol-undo" className="builder-header-icon" />
          </Button>
        )}
        {(editing && !isRulesEditing) && (
          <Button
            variant="transparent"
            className="mb-0"
            title="Redo"
            onClick={builderActions.redo}
            disabled={futureLength === 0}
          >
            <Icon name="be-symbol-redo" className="builder-header-icon" />
          </Button>
        )}
      </div>
      <div className="d-flex flex-grow-1 flex-basis-0 flex-no-wrap align-items-center justify-content-end">
        {mode && (
          <>
            {{
              'template':         () => <TemplateButtons builder={builder} />,
              'template_preview': () => <TemplatePreviewButtons mode={mode} />,
              'email_preview':    () => <TemplatePreviewButtons mode={mode} />,
              'email':            () => <EmailButtons />,
            }[mode]()}
          </>
        )}
        {me && (
          <>
            <HelpButton />
            <NotificationsMenu />
            <UserMenu />
          </>
        )}
      </div>
    </Container>
  );
};

export default Header;
