/**
 * @param folders
 * @param pid
 * @param found
 * @returns {*[]}
 */
export const getSubFolders = (folders, pid, found) => {
  for (let i = 0; i < folders.length; i++) {
    if (folders[i].pid === pid) {
      found.push(folders[i].id);
      getSubFolders(folders, folders[i].id, found);
    }
  }
};

/**
 * @param folders
 * @param targetID
 * @param sourceID
 * @returns {boolean}
 */
export const isChildFolder = (folders, targetID, sourceID) => {
  const subFolders = [];
  getSubFolders(folders, sourceID, subFolders);

  return subFolders.indexOf(parseInt(targetID, 10)) !== -1;
};
