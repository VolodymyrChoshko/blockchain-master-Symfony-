import { useEffect, useMemo } from 'react';
import { useSelector } from 'react-redux';
import { useTemplateActions } from 'dashboard/actions/templateActions';

/**
 * @param fid
 * @param folders
 * @param emails
 * @returns {*[]}
 */
const findChildren = (fid, folders, emails) => {
  const children = [];
  for (let i = 0; i < folders.length; i++) {
    const folder = folders[i];
    folder.type = 'folder';
    if (folder.pid === fid) {
      children.push(folder);

      folder.children = [];
      for (let y = 0; y < emails.length; y++) {
        const email = emails[y];
        email.type = 'email';
        if (email.fid === folder.id) {
          folder.children.push(email);
        }
      }

      const subFolders = Array.from(folders).filter(f => f.id !== fid && f.id !== folders[i].id);
      if (subFolders.length > 0) {
        const more = findChildren(folders[i].id, subFolders, emails);
        if (more.length > 0) {
          folder.children = folder.children.concat(more);
        }
      }
    }
  }

  return children;

  /* return children.sort((a, b) => {
    if (a.name < b.name) { return -1; }
    if (a.name > b.name) { return 1; }
    return 0;
  }); */
};

/**
 * @param tid
 * @returns {*}
 */
const useTemplate = (tid) => {
  const templateActions = useTemplateActions();
  const templates = useSelector(state => state.template.templates);
  const emails = useSelector(state => state.template.emails);
  const people = useSelector(state => state.template.people);
  const folders = useSelector(state => state.template.folders);
  const isEmailsLoading = useSelector(state => state.template.isEmailsLoading);

  /**
   *
   */
  useEffect(() => {
    if (tid && emails[tid] === undefined && templates[tid] !== undefined) {
      templateActions.fetchEmails(tid);
    }
  }, [tid, emails, templates]);

  return useMemo(() => {
    const result = {
      template:        null,
      people:          [],
      emails:          [],
      folders:         [],
      isEmailsLoading: false
    };
    if (!tid) {
      return result;
    }
    if (templates[tid]) {
      result.template = templates[tid];
    }
    if (emails[tid]) {
      result.emails = emails[tid];
    }
    if (people[tid]) {
      result.people = people[tid];
    }
    if (folders[tid]) {
      result.folders = folders[tid];
    }
    result.isEmailsLoading = isEmailsLoading[tid] === true;

    result.tree = [];
    if (folders[tid]) {
      result.tree.push({
        type:     'folder',
        id:       0,
        children: [],
      });
      if (emails[tid]) {
        for (let y = 0; y < emails[tid].length; y++) {
          const email = emails[tid][y];
          email.type = 'email';
          if (email.fid === 0) {
            if (email.updatedAt === 0) {
              email.updatedAt = email.createdAt;
              email.isNew = true;
            }
            result.tree[0].children.push(email);
          }
        }
      }

      for (let i = 0; i < folders[tid].length; i++) {
        const folder = folders[tid][i];
        if (folder.pid === 0) {
          folder.type = 'folder';
          folder.children = findChildren(folder.id, folders[tid], emails[tid] || []);
          result.tree.push(folder);

          if (emails[tid]) {
            for (let y = 0; y < emails[tid].length; y++) {
              const email = emails[tid][y];
              email.type = 'email';
              if (email.fid === folder.id) {
                folder.children.push(email);
              }
            }
          }
        }
      }

      /* result.tree = result.tree.sort((a, b) => {
        if (a.name < b.name) { return -1; }
        if (a.name > b.name) { return 1; }
        return 0;
      }); */
    }

    // console.log(result.tree);

    return result;
  }, [tid, templates, people, emails, folders, isEmailsLoading]);
};

export default useTemplate;
