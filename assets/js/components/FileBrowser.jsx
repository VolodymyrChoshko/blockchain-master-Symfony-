import React from 'react';
import { useSelector } from 'react-redux';
import FileBrowserColumn from './FileBrowserColumn';
import Button from './Button';
import Icon from './Icon';

const FileBrowser = ({
  files,
  folder,
  wdir,
  activeSourceID,
  preview,
  allowedTypes,
  onSelect,
  onChoose,
  onChooseFolder,
}) => {
  const sources = useSelector((state) => state.source.sources);

  let activeSource;
  if (activeSourceID) {
    activeSource = sources.find((s) => s.id === activeSourceID);
  }

  let folderName = '';
  if (folder) {
    folderName = folder.split('/').pop();
  }

  const wdirBase = wdir.split('/').pop();
  const canCreateRootFolder = !!(
    !activeSource
    || (wdir && wdir !== '/')
    || (activeSource.settings.rules.can_create_root_folder && (!wdir || wdir === '/'))
  );

  let folderPane;
  if (allowedTypes.indexOf('folder') !== -1) {
    folderPane = folderName ? (
      <div className="file-browser-column file-browser-preview">
        <Icon name="folder" />
        <div className="file-browser-preview-name">
          {folderName}
        </div>
        <Button variant="main" onClick={onChooseFolder}>
          Select
        </Button>
      </div>
    ) : (
      <div className="file-browser-column file-browser-preview">
        <Icon name="folder" />
        <div className="file-browser-preview-name">
          {wdirBase || '/'}
        </div>
        {canCreateRootFolder && (
          <Button variant="main" onClick={onChooseFolder}>
            Use This Folder
          </Button>
        )}
      </div>
    );

    if (activeSourceID === -1 || (!wdirBase && wdir !== '/' && !canCreateRootFolder)) {
      folderPane = null;
    }
  }

  return (
    <div className="file-browser">
      <div className="file-browser-columns">
        {files.map(({ dir, list }) => (
          <FileBrowserColumn
            key={dir}
            dir={dir}
            list={list}
            allowedTypes={allowedTypes}
            onSelect={onSelect}
          />
        ))}
        {preview && (
          <div className="file-browser-column file-browser-preview">
            {preview.img ? (
              <img src={preview.img} alt="Preview" />
            ) : (
              <div className="file-browser-preview-iframe-container">
                <iframe src={preview.html} scrolling="no" />
              </div>
            )}
            <div className="file-browser-preview-name">
              {preview.name}
            </div>
            <div className="file-browser-preview-size">
              {preview.size}
            </div>
            <Button variant="main" onClick={onChoose}>
              Select
            </Button>
          </div>
        )}
        {folderPane}
      </div>
    </div>
  );
};

FileBrowser.defaultProps = {
  allowedTypes:   [],
  onSelect:       () => {},
  onChoose:       () => {},
  onChooseFolder: () => {}
};

export default FileBrowser;

/* export default class FileBrowser extends React.PureComponent {
  static propTypes = {
    files:          PropTypes.array.isRequired,
    folder:         PropTypes.string.isRequired,
    wdir:           PropTypes.string.isRequired,
    activeSourceID: PropTypes.number.isRequired,
    preview:        PropTypes.object,
    allowedTypes:   PropTypes.array,
    onSelect:       PropTypes.func,
    onChoose:       PropTypes.func,
    onChooseFolder: PropTypes.func
  };

  static defaultProps = {
    allowedTypes:   [],
    onSelect:       () => {},
    onChoose:       () => {},
    onChooseFolder: () => {}
  };

  /!**
   * @returns {*}
   *!/
  render() {
    const {
      wdir,
      files,
      folder,
      preview,
      activeSourceID,
      allowedTypes,
      onSelect,
      onChoose,
      onChooseFolder
    } = this.props;

    // activeSource.settings.rules

    let folderName = '';
    if (folder) {
      folderName = folder.split('/').pop();
    }

    const wdirBase = wdir.split('/').pop();

    let folderPane;
    if (allowedTypes.indexOf('folder') !== -1) {
      folderPane = folderName ? (
        <div className="file-browser-column file-browser-preview">
          <Icon name="folder" />
          <div className="file-browser-preview-name">
            {folderName}
          </div>
          <Button variant="main" onClick={onChooseFolder}>
            Select
          </Button>
        </div>
      ) : (
        <div className="file-browser-column file-browser-preview">
          <Icon name="folder" />
          <div className="file-browser-preview-name">
            {wdirBase || '/'}
          </div>
          <Button variant="main" onClick={onChooseFolder}>
            Use This Folder
          </Button>
        </div>
      );
      if (activeSourceID === -1 || (!wdirBase && wdir !== '/')) {
        folderPane = null;
      }
    }

    return (
      <div className="file-browser">
        <div className="file-browser-columns">
          {files.map(({ dir, list }) => (
            <FileBrowserColumn
              key={dir}
              dir={dir}
              list={list}
              allowedTypes={allowedTypes}
              onSelect={onSelect}
            />
          ))}
          {preview && (
            <div className="file-browser-column file-browser-preview">
              {preview.img ? (
                <img src={preview.img} alt="Preview" />
              ) : (
                <div className="file-browser-preview-iframe-container">
                  <iframe src={preview.html} scrolling="no" />
                </div>
              )}
              <div className="file-browser-preview-name">
                {preview.name}
              </div>
              <div className="file-browser-preview-size">
                {preview.size}
              </div>
              <Button variant="main" onClick={onChoose}>
                Select
              </Button>
            </div>
          )}
          {folderPane}
        </div>
      </div>
    );
  }
} */
