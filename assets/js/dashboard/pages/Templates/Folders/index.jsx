import React, { useState, useMemo } from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import browser from 'utils/browser';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import Folder from '../Folder';
import Email from '../Email';
import EmailForm from '../EmailForm';
import { Container, ListItem } from './styles';

const Folders = ({
  folders,
  emails,
  template,
  overFolder,
  parent,
  depth,
  tree,
  isOver,
  highlightedEmailID,
  isCollapsed,
  canEdit
}) => {
  const templateActions = useTemplateActions();
  const highlightedPath = useSelector(state => state.template.highlightedPath);
  const [renaming, setRenaming] = useState([]);
  const [opened, setOpened] = useState(
    browser.storage.getItem(`dashboard.template.openedFolders.${template.id}`, [])
  );
  const isDragging = useSelector(state => state.template.isDragging);

  /**
   * @type {*[]}
   */
  const [combined, isOpen] = useMemo(() => {
    const cf = [];
    const es = [];
    const start = new Date().getTime();

    folders.forEach((folder) => {
      if (folder.pid === parent) {
        if (folder.updatedAt === 0) {
          folder.updatedAt = folder.createdAt;
          folder.isNew = true;
        }
        cf.push(folder);
      }
    });

    emails.forEach((email) => {
      if (email.fid === parent) {
        if (email.updatedAt === 0) {
          email.updatedAt = email.createdAt;
          email.isNew = true;
        }
        es.push(email);
      }
    });

    const sortedFolders = cf.sort((a, b) => {
      if (a.name < b.name) { return -1; }
      if (a.name > b.name) { return 1; }
      return 0;
    });
    const sortedEmails = es.sort((a, b) => b.updatedAt - a.updatedAt);

    const end = new Date().getTime();
    console.log(end - start);

    return [
      sortedEmails.concat(sortedFolders),
      false
    ];
  }, [emails, folders]);

  /**
   * @param {Event} e
   * @param {*} email
   */
  const handleDuplicate = (e, email) => {
    const newRenaming = Array.from(renaming);
    const index = newRenaming.indexOf(email.id);
    if (index === -1) {
      newRenaming.push(email.id);
    } else {
      newRenaming.splice(index, 1);
    }
    setRenaming(newRenaming);
  };

  /**
   * @param {Event} e
   * @param {*} folder
   */
  const handleCollapse = (e, folder) => {
    if (isDragging) {
      return;
    }

    const newOpened = Array.from(opened);
    const index = newOpened.indexOf(folder.id);
    if (index === -1) {
      newOpened.push(folder.id);
    } else {
      newOpened.splice(index, 1);
    }

    templateActions.setHighlightedPath([]);
    setOpened(newOpened);
    browser.storage.setItem(`dashboard.template.openedFolders.${template.id}`, newOpened);
    setTimeout(() => {
      window.dispatchEvent(new Event('be.dropped'));
    }, 250);
  };

  /**
   *
   */
  const handleDuplicateSave = (e, title, email) => {
    handleDuplicate(e, email);
    templateActions.duplicateEmail(email.id, title);
  };

  return (
    <Container>
      {combined.map(c => (
        <ListItem key={c.id} isCollapsed={isCollapsed && highlightedPath.indexOf(c.id) === -1}>
          {c.fid !== undefined ? (
            <>
              <Email
                email={c}
                depth={depth}
                template={template}
                isOver={isOver}
                canEdit={canEdit}
                onDuplicate={handleDuplicate}
                highlighted={highlightedEmailID === c.id}
              />
              {renaming.indexOf(c.id) !== -1 && (
                <EmailForm
                  email={c}
                  onSave={handleDuplicateSave}
                  onCancel={handleDuplicate}
                  isDuplicating
                />
              )}
            </>
          ) : (
            <>
              <Folder
                folder={c}
                depth={depth}
                template={template}
                isOver={c.id === overFolder}
                onCollapse={handleCollapse}
                isCollapsed={opened.indexOf(c.id) === -1 && !isOpen && highlightedPath.indexOf(c.id) === -1}
              />
              <Folders
                parent={c.id}
                depth={depth + 1}
                tree={tree}
                emails={emails}
                canEdit={canEdit}
                folders={folders}
                template={template}
                overFolder={overFolder}
                isOver={c.id === overFolder}
                highlightedEmailID={highlightedEmailID}
                isCollapsed={opened.indexOf(c.id) === -1 && !isOpen && highlightedPath.indexOf(c.id) === -1}
              />
            </>
          )}
        </ListItem>
      ))}
    </Container>
  );
};

Folders.propTypes = {
  template:           PropTypes.object.isRequired,
  emails:             PropTypes.array.isRequired,
  folders:            PropTypes.array.isRequired,
  parent:             PropTypes.number.isRequired,
  overFolder:         PropTypes.number.isRequired,
  highlightedEmailID: PropTypes.number.isRequired,
  depth:              PropTypes.number.isRequired,
  isCollapsed:        PropTypes.bool.isRequired,
  canEdit:            PropTypes.bool.isRequired,
  tree:               PropTypes.array.isRequired,
};

export default Folders;
