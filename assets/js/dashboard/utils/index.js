/**
 * @param emails
 * @param eid
 */
export const findEmailLocation = (emails, eid) => {
  const tids = Object.keys(emails);
  for (let i = 0; i < tids.length; i++) {
    const tid = tids[i];
    const index = emails[tid].findIndex(e => e.id === eid);
    if (index !== -1) {
      return { tid, index, email: emails[tid][index] };
    }
  }

  return { tid: 0, index: -1, email: null };
};

/**
 * @param folders
 * @param fid
 * @returns {{folder: null, index: number, tid: number}|{folder: *, index: *, tid: string}}
 */
export const findFolderLocation = (folders, fid) => {
  const tids = Object.keys(folders);
  for (let i = 0; i < tids.length; i++) {
    const tid = tids[i];
    const index = folders[tid].findIndex(f => f.id === fid);
    if (index !== -1) {
      return { tid, index, folder: folders[tid][index] };
    }
  }

  return { tid: 0, index: -1, folder: null };
};
