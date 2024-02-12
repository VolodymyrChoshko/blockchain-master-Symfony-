import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { Scrollbars } from 'react-custom-scrollbars';
import scrollbars from 'utils/scrollbars';
import { mapDispatchToProps } from 'utils';
import arrays from 'utils/arrays';
import { hasIntegrationRule } from 'lib/integrations';
import { Modal, FileBrowser, Icon } from 'components';
import { sourceActions, uiActions } from 'builder/actions';

const mapStateToProps = state => ({
  wdir:           state.source.wdir,
  depth:          state.source.depth,
  files:          state.source.files,
  folder:         state.source.folder,
  sources:        state.source.sources,
  preview:        state.source.preview,
  imported:       state.source.imported,
  activeSourceID: state.source.activeSourceID
});

@connect(
  mapStateToProps,
  mapDispatchToProps(sourceActions, uiActions)
)
export default class SourceBrowseModal extends React.PureComponent {
  static propTypes = {
    wdir:                     PropTypes.string.isRequired,
    depth:                    PropTypes.number.isRequired,
    files:                    PropTypes.array.isRequired,
    sources:                  PropTypes.array.isRequired,
    selectType:               PropTypes.string.isRequired,
    folder:                   PropTypes.string.isRequired,
    preview:                  PropTypes.object,
    imported:                 PropTypes.object,
    activeSourceID:           PropTypes.number,
    onChoose:                 PropTypes.func,
    onSelectFile:             PropTypes.func,
    sourceSelectFolder:       PropTypes.func.isRequired,
    sourceListFiles:          PropTypes.func.isRequired,
    sourceDownload:           PropTypes.func.isRequired,
    sourceMakeFolder:         PropTypes.func.isRequired,
    sourceActiveSourceID:     PropTypes.func.isRequired,
    sourceImportSelectedPath: PropTypes.func.isRequired,
    uiPrompt:                 PropTypes.func.isRequired,
    uiAlert:                  PropTypes.func.isRequired,
  };

  static defaultProps = {};

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.scrollbars = React.createRef();
    this.modal = React.createRef();
    this.selectedFile = null;
  }

  /**
   *
   */
  componentDidMount() {
    const { sources, activeSourceID, sourceActiveSourceID, sourceListFiles } = this.props;

    if (activeSourceID === -1) {
      sourceActiveSourceID(sources[0].id);
    }
    sourceListFiles('~');
  }

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { depth, preview, wdir, imported, onChoose } = this.props;
    const { depth: pDepth, preview: pPreview, wdir: pWdir, imported: pImported } = prevProps;

    if (depth !== pDepth || preview && !pPreview || wdir !== pWdir) {
      this.scrollbars.current.scrollToRight();
    }
    if (imported && imported !== pImported) {
      onChoose(imported);
    }
  }

  /**
   *
   */
  componentWillUnmount() {
    // const { sourceSelectFolder } = this.props;

    // sourceSelectFolder('');
  }

  /**
   * @param {Event} e
   * @param {*} file
   */
  handleSelectFile = (e, file) => {
    const { sourceSelectFolder, sourceDownload, sourceListFiles } = this.props;

    this.selectedFile = file;
    switch (file.type) {
      case 'folder':
        sourceSelectFolder(file.path);
        sourceListFiles(file.path);
        break;
      case 'image':
      case 'html':
        sourceSelectFolder('');
        sourceDownload(file.path);
        break;
    }
  };

  /**
   *
   */
  handleChoose = () => {
    const { onSelectFile, sourceImportSelectedPath } = this.props;

    if (onSelectFile) {
      onSelectFile(this.selectedFile);
    } else {
      sourceImportSelectedPath();
    }
  };

  /**
   *
   */
  handleChooseFolder = () => {
    const { wdir, folder, onChoose, activeSourceID, sourceSelectFolder } = this.props;

    sourceSelectFolder(folder || wdir);
    onChoose(folder || wdir, activeSourceID);
  };

  /**
   * @param {Event} e
   * @param {*} source
   */
  handleSourceClick = (e, source) => {
    const { sourceListFiles, sourceActiveSourceID } = this.props;

    sourceActiveSourceID(source.id);
    sourceListFiles('~');
  };

  /**
   *
   */
  handleCreateFolderClick = () => {
    const { activeSourceID, sources, sourceMakeFolder, wdir, uiPrompt, uiAlert } = this.props;

    const source = arrays.findByID(sources, activeSourceID);
    const canCreateRootFolder = !!(
      (wdir && wdir !== '/')
      || (source.settings.rules.can_create_root_folder && (!wdir || wdir === '/'))
    );
    if (!canCreateRootFolder) {
      return;
    }

    const getFolderName = (value = '') => {
      uiPrompt('Folder Name', value, '', (name) => {
        if (!name) {
          uiPrompt(false);
          return;
        }

        if (source && hasIntegrationRule(source, 'no_folder_spaces') && name.indexOf(' ') !== -1) {
          uiAlert('Error', 'Spaces are not allowed in folder names.');
          return;
        }

        sourceMakeFolder(name);
        uiPrompt(false);
      }, undefined, { closeOnClick: false });
    };

    getFolderName();
  };

  /**
   * @returns {*}
   */
  render() {
    const { selectType, sources, preview, activeSourceID, wdir, depth, folder, files, ...props } = this.props;

    let title        = 'Select Image';
    let allowedTypes = ['image'];
    if (selectType === 'folder') {
      title        = 'Select Folder';
      allowedTypes = ['folder'];
    } else if (selectType === 'html') {
      title        = 'Select Template';
      allowedTypes = ['html'];
    }

    const activeSource = arrays.findByID(sources, activeSourceID);
    if (!activeSource) {
      return null;
    }
    const { rules } = activeSource.settings;
    const canCreateRootFolder = !!(
      (wdir && wdir !== '/')
      || (activeSource.settings.rules.can_create_root_folder && (!wdir || wdir === '/'))
    );

    return (
      <Modal ref={this.modal} title={title} className="modal-source-browser" {...props} scrollbars={false} auto lg>
        <div className="modal-source-browser-body">
          <div className="modal-source-browser-sources">
            <Scrollbars
              style={{ height: '88%' }}
              renderTrackHorizontal={scrollbars.renderTrackHorizontal}
              renderThumbHorizontal={scrollbars.renderThumbHorizontal}
            >
              <ul>
                {sources.filter(s => s.settings.rules.can_list_files).map((source) => {
                  const classes = classNames('modal-source-browser-sources-item', {
                    'modal-source-browser-sources-item-selected': source.id === activeSourceID
                  });
                  return (
                    <li key={source.id} className={classes} onClick={e => this.handleSourceClick(e, source)}>
                      <img src={source.thumb} alt="Thumbnail" />
                      {source.name}
                    </li>
                  );
                })}
              </ul>
            </Scrollbars>
            <div className="modal-source-browser-sources-footer">
              {rules.can_create_folders && (
                <span className={canCreateRootFolder ? 'pointer' : 'integrations-sources-item-disabled'}>
                  <Icon
                    name="be-symbol-new-folder"
                    style={{ fontSize: 28 }}
                    onClick={this.handleCreateFolderClick}
                  />
                </span>
              )}
            </div>
          </div>
          <Scrollbars
            ref={this.scrollbars}
            renderTrackHorizontal={scrollbars.renderTrackHorizontal}
            renderThumbHorizontal={scrollbars.renderThumbHorizontal}
          >
            {(activeSource && files && files.length > 0) && (
              <FileBrowser
                wdir={wdir}
                files={files}
                folder={folder}
                preview={preview}
                rules={activeSource.settings.rules}
                allowedTypes={allowedTypes}
                activeSourceID={activeSourceID}
                onSelect={this.handleSelectFile}
                onChoose={this.handleChoose}
                onChooseFolder={this.handleChooseFolder}
              />
            )}
          </Scrollbars>
        </div>
      </Modal>
    );
  }
}
