import React, { useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import useDragAndDrop from 'dashboard/hooks/useDragAndDrop';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import { useUIActions } from 'builder/actions/uiActions';
import Icon from 'components/Icon';
import EmailForm from '../EmailForm';
import { Container, TitleWrap, Name, Button, EmailIcon, Remove } from './styles';

const Folder = ({ folder, template, depth, isOver, isCollapsed, onCollapse }) => {
  const templateActions = useTemplateActions();
  const uiActions = useUIActions();
  const container = useRef(null);
  const isDragging = useSelector(state => state.template.isDragging);
  const [isRenaming, setRenaming] = useState(false);
  const [handleMouseDown, handleMouseOver] = useDragAndDrop(container, folder.id, 'folder');

  /**
   *
   */
  const handleDeleteClick = () => {
    if (folder.id === -1) {
      return;
    }
    // eslint-disable-next-line max-len
    uiActions.confirm('', 'Are you sure you want to delete this folder? All emails in the folder will also be deleted.', [
      {
        text:    'Yes',
        variant: 'danger',
        action:  () => {
          templateActions.deleteFolder(template.id, folder.id);
        }
      },
      {
        text:    'No',
        variant: 'alt'
      }
    ]);
  };

  /**
   * @param e
   * @param title
   */
  const handleRenameSave = (e, title) => {
    templateActions.renameFolder(template.id, folder.id, title, () => {
      setRenaming(false);
    });
  };

  return (
    // eslint-disable-next-line jsx-a11y/mouse-events-have-key-events
    <Container
      ref={container}
      isOver={isOver}
      isRenaming={isRenaming}
      className="folder"
      onMouseDown={handleMouseDown}
      onMouseOver={handleMouseOver}
      data-fid={folder.id}
      data-pid={folder.pid}
    >
      <TitleWrap>
        {!isRenaming && (
          <>
            <Name
              depth={depth}
              onClick={(e) => {
                if (!isDragging && folder.id !== -1) {
                  onCollapse(e, folder);
                }
              }}
            >
              <Icon
                name={isCollapsed ? 'be-symbol-folder' : 'be-symbol-open-folder'}
                className="mr-2 pointer"
                far
              />
              {folder.name}
            </Name>
            <div className="rename-wrap">
              {folder.id !== -1 && (
                <button type="button" className="font-size-sm" onClick={() => setRenaming(true)}>
                  RENAME
                </button>
              )}
            </div>
          </>
        )}
        {isRenaming && (
          <EmailForm email={folder} onSave={handleRenameSave} onCancel={() => setRenaming(false)} />
        )}
      </TitleWrap>
      <Remove>
        <Button type="button" title="Delete" onClick={handleDeleteClick} disabled={folder.id === -1}>
          <EmailIcon name="be-symbol-delete" />
        </Button>
      </Remove>
    </Container>
  );
};

Folder.propTypes = {
  folder:      PropTypes.object.isRequired,
  template:    PropTypes.object.isRequired,
  isOver:      PropTypes.bool.isRequired,
  isCollapsed: PropTypes.bool.isRequired,
  depth:       PropTypes.number.isRequired,
  onCollapse:  PropTypes.func.isRequired,
};

export default Folder;
