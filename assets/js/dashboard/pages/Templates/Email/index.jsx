import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import { useUIActions } from 'builder/actions/uiActions';
import { useBuilderActions } from 'builder/actions/builderActions';
import useDragAndDrop from 'dashboard/hooks/useDragAndDrop';
import router from 'lib/router';
import arrays from 'utils/arrays';
import { multilineEllipsis } from 'utils/browser';
import { getFormattedDate, formatAMPM } from 'dashboard/utils/dates';
import EmailForm from '../EmailForm';
import {
  Author,
  Button,
  Container,
  Controls,
  EmailIcon,
  Remove,
  TitleWrap,
  Title,
  TitleDisabled,
  TemplateTitle
} from './styles';

const Email = ({
  email,
  template,
  depth,
  showControls,
  showTemplate,
  isOver,
  draggable,
  highlighted,
  onDuplicate,
  canEdit,
}) => {
  const uiActions = useUIActions();
  const templateActions = useTemplateActions();
  const builderActions = useBuilderActions();
  const container = useRef(null);
  const titleRef = useRef();
  const [isRenaming, setRenaming] = useState(false);
  const [handleMouseDown, handleMouseOver, isDragging] = useDragAndDrop(container, email.id, 'email');
  const people = useSelector(state => state.template.people);

  /**
   *
   */
  useEffect(() => {
    multilineEllipsis(titleRef.current);
  }, []);

  /**
   *
   */
  const handleDeleteClick = () => {
    // eslint-disable-next-line max-len
    uiActions.confirm('', `Are you sure you want to delete "${email.title}"? The email will be removed for all editors.`, [
      {
        text:    'Yes',
        variant: 'danger',
        action:  () => {
          templateActions.deleteEmail(email.id);
        }
      },
      {
        text:    'No',
        variant: 'alt'
      }
    ]);
  };

  /**
   *
   */
  const handleShareClick = () => {
    const previewUrl = router.generate('build_email_preview', { id: email.id, tid: email.tid, token: email.token }, 'absolute');
    builderActions.setState('id', email.id);
    builderActions.setState('previewUrl', previewUrl);
    uiActions.modal('shareEmail', true);
  };

  /**
   *
   */
  const handleExportClick = () => {
    builderActions.setState('id', email.id);
    builderActions.setState('token', email.token);
    uiActions.modal('exportEmail', true);
  };

  /**
   * @param {Event} e
   */
  const handleTitleClick = (e) => {
    if (isDragging || email.id === -1) {
      e.preventDefault();
    }
  };

  /**
   * @param e
   * @param title
   */
  const handleRenameSave = (e, title) => {
    templateActions.renameEmail(template.id, email.id, title, () => {
      setRenaming(false);
      setTimeout(() => {
        if (titleRef.current) {
          multilineEllipsis(titleRef.current);
        }
      }, 250);
    });
  };

  /**
   *
   */
  const handleSearchClick = () => {
    templateActions.searchEmails(false);
  };

  let author;
  const searchPeople = email.people ? email.people : people[template.id];
  if (email.updatedAt !== 0) {
    author = arrays.findByID(searchPeople, email.updatedUserID || email.createdUserID);
  } else {
    author = arrays.findByID(searchPeople, email.createdUserID);
  }
  const date = new Date((email.updatedAt !== 0 ? email.updatedAt : email.createdAt) * 1000);

  let link;
  if (email.id === -1) {
    link = '/';
  } else if (email.org) {
    link = router.generate('build_email', { tid: template.id, id: email.id }, 'absolute', email.org.id);
  } else {
    link = router.generate('build_email', { tid: template.id, id: email.id });
  }

  return (
    // eslint-disable-next-line jsx-a11y/mouse-events-have-key-events
    <Container
      ref={container}
      isOver={isOver}
      disabled={!canEdit}
      highlighted={highlighted}
      onMouseDown={draggable ? handleMouseDown : undefined}
      onMouseOver={handleMouseOver}
      data-eid={email.id}
      data-fid={email.fid}
      data-pid={email.fid}
    >
      <TitleWrap isSearch={!!showTemplate}>
        {!isRenaming && (
          <>
            {canEdit ? (
              <Title
                href={link}
                depth={depth}
                draggable={false}
                onMouseDown={draggable ? handleMouseDown : undefined}
                onClick={handleTitleClick}
                ref={titleRef}
              >
                {email.title}
              </Title>
            ) : (
              <TitleDisabled>
                {email.title}
              </TitleDisabled>
            )}
            {showTemplate && (
              <TemplateTitle
                href={`https://${template.oid}.${router.getSiteUrl().replace('https://', '')}/t/${template.id}#${email.id}`}
                onClick={handleSearchClick}
              >
                {email.org.name} &gt; {template.title}
              </TemplateTitle>
            )}
            {(!showTemplate && canEdit && email.id !== -1) && (
              <div className="rename-wrap">
                <button type="button" className="font-size-sm" onClick={() => setRenaming(true)}>
                  RENAME
                </button>
              </div>
            )}
          </>
        )}
        {isRenaming && (
          <EmailForm email={email} onSave={handleRenameSave} onCancel={() => setRenaming(false)} />
        )}
      </TitleWrap>
      {showControls && (
        <Controls>
          <Button
            type="button"
            title="Duplicate"
            disabled={!canEdit || email.id === -1}
            onClick={e => onDuplicate(e, email)}
          >
            <EmailIcon name="be-symbol-copy" />
          </Button>
          <Button type="button" title="Share" disabled={email.id === -1} onClick={handleShareClick}>
            <EmailIcon name="be-symbol-share" />
          </Button>
          <Button type="button" title="Export" disabled={email.id === -1} onClick={handleExportClick}>
            <EmailIcon name="be-symbol-export" />
          </Button>
        </Controls>
      )}
      <Author>
        <div>{email.isNew ? 'Created' : 'Updated'} {getFormattedDate(date)} at {formatAMPM(date)}</div>
        <div>by {author ? author.name : 'Unknown'}</div>
      </Author>
      {showControls && (
        <Remove>
          <Button type="button" title="Delete" disabled={!canEdit || email.id === -1} onClick={handleDeleteClick}>
            <EmailIcon name="be-symbol-delete" />
          </Button>
        </Remove>
      )}
    </Container>
  );
};

Email.propTypes = {
  template:     PropTypes.object.isRequired,
  email:        PropTypes.object.isRequired,
  onDuplicate:  PropTypes.func.isRequired,
  depth:        PropTypes.number.isRequired,
  showControls: PropTypes.bool,
  showTemplate: PropTypes.bool,
  draggable:    PropTypes.bool,
  highlighted:  PropTypes.bool,
  isOver:       PropTypes.bool,
  canEdit:      PropTypes.bool.isRequired,
};

Email.defaultProps = {
  showControls: true,
  showTemplate: false,
  draggable:    true,
  highlighted:  false,
  isOver:       false
};

export default Email;
