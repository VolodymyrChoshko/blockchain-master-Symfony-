import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useSelector } from 'react-redux';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import { useUIActions } from 'builder/actions/uiActions';
import eventDispatcher from 'builder/store/eventDispatcher';
import { scrollIntoView } from 'utils/browser';
import { pushHistoryState, onLocationChange } from 'lib/history';
import { isChildFolder } from 'dashboard/utils/folders';
import useTemplate from 'dashboard/hooks/useTemplate';
import useMe from 'dashboard/hooks/useMe';
import Icon from 'components/Icon';
import Box from 'dashboard/components/Box';
import Flex from 'components/Flex';
import Button from 'components/Button';
import Loading from 'components/Loading';
import EmailForm from './EmailForm';
import Thumbnail from './Thumbnail';
import TemplateDropdown from './TemplateDropdown';
import People from './People';
import Folders from './Folders';
import Notices from './Notices';
import { AddFolderFooter, AddFolderButton, NewEmailButton } from './styles';

/**
 * @param eid
 * @param tree
 * @param pp
 * @returns {[*[],null]}
 */
const findEmail = (eid, tree, pp = []) => {
  let path = pp;
  let email = null;
  for (let i = 0; i < tree.length; i++) {
    const leaf = tree[i];

    if (leaf.children) {
      for (let j = 0; j < leaf.children.length; j++) {
        const child = leaf.children[j];

        if (child.type === 'email' && child.id === eid) {
          email = child;
          path.push(leaf.id);
          break;
        }
      }
      if (!email) {
        const orgPath = Array.from(path);
        if (path.indexOf(leaf.id) === -1) {
          path.push(leaf.id);
        }
        const [p, e] = findEmail(eid, leaf.children, path);
        if (e) {
          email = e;
          for (let j = 0; j < p.length; j++) {
            if (path.indexOf(p[j]) === -1) {
              path.push(p[j]);
            }
          }
        } else {
          path = orgPath;
        }
      }
    }
  }

  return [path, email];
};

const Templates = () => {
  const me = useMe();
  const uiActions = useUIActions();
  const templateActions = useTemplateActions();
  const isLoaded = useSelector(state => state.template.isLoaded);
  const templates = useSelector(state => state.template.templates);
  const lastTemplateID = useSelector(state => state.template.lastTemplateID);
  const billingPlan = useSelector(state => state.template.billingPlan);
  const [selectedTemplateID, setSelectedTemplateID] = useState(0);
  const [highlightedEmailID, setHighlightedEmailID] = useState(0);
  const [isAddingFolder, setAddingFolder] = useState(false);
  const { template, people, emails, folders, tree, isEmailsLoading } = useTemplate(selectedTemplateID);
  const [overFolder, setOverFolder] = useState(0);
  const [isReady, setReady] = useState(false);
  const [isCreating, setCreating] = useState(false);
  const overSelf = useRef(false);
  const canEdit = me.isOwner || billingPlan.canTeamEdit;

  /**
   *
   */
  const handleLocationChange = () => {
    const { pathname, hash } = document.location;

    const matches = pathname.match(/\/t\/([\d]+)/);
    if (matches) {
      const id = parseInt(matches[1], 10);
      setSelectedTemplateID(id);
      templateActions.setLastTemplate(id);
    }

    if (hash) {
      const eid = parseInt(hash.substr(1), 10);
      if (!isNaN(eid)) {
        setHighlightedEmailID(eid);
        setTimeout(() => {
          const emailElement = document.querySelector(`[data-eid="${eid}"]`);
          scrollIntoView(emailElement);
        }, 1500);
      }
    }
  };

  /**
   * Boots the dashboard and wires up the search input
   */
  useEffect(() => {
    const matches = document.location.hostname.match(/^([\d]+)\.[\w]+\.blocksedit.com$/);
    if (!matches && me) {
      if (me.lastDashboard) {
        document.location = me.lastDashboard;
        return;
      }
      const org = me.organizations[0];
      if (org) {
        document.location = org.domain;
        return;
      }
    }

    setReady(true);
    if (!isLoaded) {
      let previewHash = '';
      if (document.location.search.indexOf('?preview_notice=') === 0) {
        const parts = document.location.search.split('?preview_notice=', 2);
        // eslint-disable-next-line prefer-destructuring
        previewHash = parts[1];
      }

      templateActions.templateOpen(null, previewHash);
    }
  }, [isLoaded]);

  /**
   * Wires up event listening for location changes.
   */
  useEffect(() => {
    return onLocationChange(handleLocationChange);
  }, []);

  /**
   * Sets the initial selected template using the url, local storage, or the default.
   */
  useEffect(() => {
    const keys = Object.keys(templates);
    if (keys.length > 0 && selectedTemplateID === 0) {
      const { pathname, hash } = document.location;
      if (pathname.match(/\/t\/([\d]+)/) || hash) {
        return;
      }

      if (lastTemplateID && templates[lastTemplateID]) {
        setSelectedTemplateID(lastTemplateID);
        pushHistoryState(`/t/${lastTemplateID}`);
      } else {
        const id = parseInt(keys[0], 10);
        setSelectedTemplateID(id);
        templateActions.setLastTemplate(id);
        pushHistoryState(`/t/${id}`);
      }
    }
  }, [templates, selectedTemplateID]);

  /**
   *
   */
  useEffect(() => {
    if (template) {
      // templateActions.upgradeCheck(template.id);
    }
  }, [template]);

  /**
   * Wires up the dragging event.
   */
  useEffect(() => {
    return eventDispatcher.on('draggingOver', (args) => {
      if (args.target.fid) {
        const isChild = isChildFolder(folders, args.target.fid, args.source.id);

        if (
          !isChild
          && args.target.fid !== args.source.id
          && args.source.pid !== args.target.fid
          && args.source.id !== args.target.pid
        ) {
          setOverFolder(args.target.fid);
          overSelf.current = false;
        } else if (args.target.fid === args.source.id || args.target.fid === args.source.pid) {
          setOverFolder(0);
          overSelf.current = true;
        } else {
          setOverFolder(0);
          overSelf.current = false;
        }
      } else {
        setOverFolder(args.target.fid);
        overSelf.current = false;
      }
    });
  }, [template, folders]);

  /**
   * Wires up the event that detects when an email was dropped in a folder.
   */
  useEffect(() => {
    return eventDispatcher.on('dropped', (args) => {
      if (overFolder === 0) {
        if (args.type === 'email' && !overSelf.current) {
          templateActions.dropInFolder(template.id, 0, 0, args.id, 'detach_email');
        } else if (!overSelf.current) {
          templateActions.dropInFolder(template.id, args.id, 0, 0, 'detach_folder');
        }
      } else if (args.type === 'email') {
        templateActions.dropInFolder(template.id, overFolder, 0, args.id, 'append_folder');
      } else if (overFolder !== args.id) {
        templateActions.dropInFolder(template.id, overFolder, args.id, 0, 'append_folder');
      }
      setOverFolder(0);
    });
  }, [overFolder, template]);

  /**
   *
   */
  useMemo(() => {
    if (highlightedEmailID) {
      for (let i = 0; i < folders.length; i++) {
        const [path, email] = findEmail(highlightedEmailID, tree);
        if (email) {
          console.log(path);
          templateActions.setHighlightedPath(path);
        }
      }
    }
  }, [highlightedEmailID, folders, tree]);

  /**
   * @param {Event} e
   * @param {number} id
   */
  const handleTemplateChange = (e, id) => {
    setSelectedTemplateID(id);
    pushHistoryState(`/t/${id}`);
  };

  /**
   *
   */
  const handleNewTemplateClick = () => {
    uiActions.uiModal('newTemplate', true);
  };

  /**
   * @param {Event} e
   * @param {string} title
   */
  const handleCreateEmailSave = (e, title) => {
    setCreating(false);
    templateActions.createEmail(template.id, title);
  };

  /**
   * @param e
   * @param title
   */
  const handleAddFolderSubmit = (e, title) => {
    templateActions.createFolder(selectedTemplateID, title);
    setAddingFolder(false);
  };

  if (!isReady || !isLoaded) {
    return <Loading />;
  }

  return (
    <>
      <Box className="mb-0 pb-0 pl-0 pt-3" overflowHidden={false} shadow={false} wide>
        {(me && (me.isOwner || me.isAdmin)) && (
          <Flex justifyCenter>
            <Button
              variant="transparent"
              className="font-size d-flex align-items-center"
              onClick={handleNewTemplateClick}
            >
              <Icon name="be-symbol-plus" className="mr-1" />
              New Template
            </Button>
          </Flex>
        )}
        <Notices />
        <Flex justifyBetween>
          <div>
            <TemplateDropdown
              template={template}
              templates={Object.values(templates)}
              onChange={handleTemplateChange}
              initialRenaming={(template && template.title === '')}
            />
            <div className="mt-2">
              <People users={people} template={template} />
            </div>
            <div className="mt-3 mb-3">
              {template && (
                <NewEmailButton
                  variant="main"
                  disabled={(template && template.title === '') || !canEdit}
                  onClick={() => setCreating(true)}
                >
                  New Email
                </NewEmailButton>
              )}
            </div>
          </div>
          <div>
            <Thumbnail template={template} peopleCount={people.length} />
          </div>
        </Flex>
      </Box>

      {template && (
        <Box
          id="db-folders-box"
          className="position-relative p-0 mb-4"
          wide
          white
          onMouseLeave={() => eventDispatcher.trigger('draggingOver', { target: { fid: 0, eid: 0 }, source: { id: 0 } })}
        >
          {isCreating && (
            <EmailForm
              email={{}}
              onSave={handleCreateEmailSave}
              onCancel={() => setCreating(false)}
            />
          )}
          <Folders
            depth={0}
            parent={0}
            tree={tree}
            emails={emails}
            folders={folders}
            template={template}
            overFolder={overFolder}
            highlightedEmailID={highlightedEmailID}
            isCollapsed={false}
            canEdit={canEdit}
          />
          {isAddingFolder && (
            <EmailForm
              email={{}}
              onSave={handleAddFolderSubmit}
              onCancel={() => setAddingFolder(false)}
              placeholder="Folder name"
            />
          )}
          <AddFolderFooter>
            <AddFolderButton onClick={() => setAddingFolder(!isAddingFolder)}>
              <Icon name="be-symbol-new-folder" className="mr-2" />
              Add Folder
            </AddFolderButton>
          </AddFolderFooter>
        </Box>
      )}
      {!template && (
        <Box wide white>
          <div className="text-center">
            Template not found.
          </div>
        </Box>
      )}
      {template && template.title.indexOf('Starter') === 0 && (
        <Box className="font-size-lg text-center mt-4" shadow={false}>
          If you want to import your own custom template, your HTML code will need to include
          Blocks Edit tags to be made editable. Need help setting up your template? Send us
          your code, browse our dev resources, or find how to get your coworkers on board.
          <p className="mt-2">
            <a href="https://blocksedit.com/getting-started/" target="_blank" rel="noopener noreferrer">
              Get help setting up your template →
            </a>
          </p>
        </Box>
      )}
      {isEmailsLoading && (
        <Loading ellipsis />
      )}
    </>
  );
};

export default Templates;
