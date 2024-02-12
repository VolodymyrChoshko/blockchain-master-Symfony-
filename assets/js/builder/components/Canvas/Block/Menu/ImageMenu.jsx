import { DATA_HOSTED, DATA_IMG_ID } from 'builder/engine/constants';
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import HTMLUtils from 'builder/engine/HTMLUtils';
import { Icon, Button } from 'components';
import { builderActions, mediaActions, uiActions } from 'builder/actions';
import * as constants from 'builder/engine/constants';
import ImageLinkMenu from './ImageLinkMenu';
import Prompt from './Prompt';
import Menu from './Menu';

const mapStateToProps = state => ({
  sources: state.source.sources
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions, mediaActions, uiActions)
)
export default class ImageMenu extends React.PureComponent {
  static propTypes = {
    block:              PropTypes.object.isRequired,
    sources:            PropTypes.array.isRequired,
    position:           PropTypes.string,
    onMouseEnter:       PropTypes.func,
    onMouseLeave:       PropTypes.func,
    mediaUpload:        PropTypes.func.isRequired,
    mediaImport:        PropTypes.func.isRequired,
    builderActiveID:    PropTypes.func.isRequired,
    builderUpdateBlock: PropTypes.func.isRequired,
    builderHoverMenus:  PropTypes.func.isRequired,
    uiModal:            PropTypes.func.isRequired
  };

  static defaultProps = {
    position:     'left',
    onMouseEnter: () => {},
    onMouseLeave: () => {}
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.file = React.createRef();
    this.state = {
      promptSrcOpen:  false,
      promptAltOpen:  false,
      promptLinkOpen: false,
      promptBGOpen:   false,
    };
  }

  /**
   *
   */
  componentWillUnmount() {
    const { block, builderHoverMenus } = this.props;

    builderHoverMenus(block.id, block.type, false);
  }

  /**
   *
   */
  handleMouseEnter = () => {
    const { block, onMouseEnter, builderHoverMenus } = this.props;

    builderHoverMenus(block.id, block.type, true);
    onMouseEnter();
  };

  /**
   *
   */
  handleMouseLeave = () => {
    const { block, onMouseLeave, builderHoverMenus } = this.props;

    builderHoverMenus(block.id, block.type, false);
    onMouseLeave();
  };

  /**
   * @param {string} field
   * @param {string} value
   */
  handlePromptUpdate = (field, value) => {
    const { block, builderUpdateBlock, builderActiveID } = this.props;
    const { element } = block;

    if (field === 'src') {
      if (value && block.isBackground()) {
        builderUpdateBlock(block.id, 'background', {
          src:      value,
          original: value
        });
        element.setAttribute('original-bg', value);
        element.setAttribute('data-be-custom-src', '1');
        element.removeAttribute(constants.DATA_HOSTED);
        element.removeAttribute(constants.DATA_IMG_ID);
        builderUpdateBlock(block.id, 'element', element);
        builderActiveID(-1);
      } else if (value && value !== element.src) {
        element.src = value;
        element.setAttribute('original', value);
        element.setAttribute('data-be-custom-src', '1');
        element.removeAttribute(constants.DATA_HOSTED);
        element.removeAttribute(constants.DATA_IMG_ID);
        builderUpdateBlock(block.id, 'element', element);
      }
      this.setState({ promptSrcOpen: false });
    } else if (field === 'alt') {
      if (value !== element.alt) {
        element.alt = value;
        builderUpdateBlock(block.id, 'element', element);
      }
      this.setState({ promptAltOpen: false });
    } else if (field === 'link') {
      if (element.tagName === 'IMG' && element.parentNode.tagName !== 'A') {
        builderUpdateBlock(block.id, 'href', value);
      } else if (value !== element.parentNode.href) {
        element.parentNode.href = value;
        builderUpdateBlock(block.id, 'element', element);
      }
      this.setState({ promptLinkOpen: false });
    } else if (field === 'background-color') {
      if (value !== '-1') {
        block.element.setAttribute('bgcolor', value);
        HTMLUtils.setStyleValue(block.element, 'background-color', value);
        builderUpdateBlock(block.id, 'element', element);
      }
      this.setState({ promptBGOpen: false });
    }
  };

  /**
   * @param {string} field
   */
  handlePromptClick = (field) => {
    const { block, builderActiveID } = this.props;

    const state = {
      promptSrcOpen:  false,
      promptAltOpen:  false,
      promptLinkOpen: false,
      promptBGOpen:   false
    };

    state[field] = true;
    this.setState(state);
    builderActiveID(block.id, false);
  };

  /**
   *
   */
  handleUploadClick = () => {
    this.file.current.click();
  };

  /**
   * @param {Event} e
   */
  handleFileChange = (e) => {
    const { block, mediaUpload } = this.props;
    const { files } = e.target;

    mediaUpload(block.id, files[0]);
    e.target.value = '';
  };

  /**
   *
   */
  handleSourceClick = () => {
    const { block, uiModal, mediaImport } = this.props;

    uiModal('sourceBrowse', true, {
      selectType: 'image',
      onChoose:   (img) => {
        uiModal('sourceBrowse', false);
        mediaImport(block.id, img);
      }
    });
  };

  /**
   * @returns {*}
   */
  render() {
    const { block, sources, position } = this.props;
    const { promptSrcOpen, promptAltOpen, promptLinkOpen, promptBGOpen } = this.state;

    let imageSrc       = '';
    const isBackground = block.isBackground() || block.hasBackground();
    if (isBackground) {
      imageSrc = HTMLUtils.getBackgroundImage(block.element);
    } else {
      imageSrc = block.element.src;
    }

    let bgColor = '';
    if (block.element.getAttribute('bgcolor')) {
      bgColor = block.element.getAttribute('bgcolor');
    } else if (HTMLUtils.getStyleValue(block.element, 'background-color')) {
      bgColor = HTMLUtils.getStyleValue(block.element, 'background-color');
    }

    const sourcesLen = sources.filter((s) => s.settings.rules.can_export_images).length;

    return (
      <>
        <Menu position={position} open>
          {provided => (
            <div
              ref={provided.menuRef}
              style={provided.styles.menu}
              className={`builder-menu builder-menu-image builder-menu-${provided.position}`}
              onMouseEnter={this.handleMouseEnter}
              onMouseLeave={this.handleMouseLeave}
            >
              <input
                ref={this.file}
                type="file"
                accept="image/*"
                className="form-hidden-file-input"
                onChange={this.handleFileChange}
              />
              {block.rules.canChangeImg && (
                <Button
                  title="Upload image"
                  style={provided.styles.button}
                  onClick={this.handleUploadClick}
                  className="builder-menu-btn"
                  sm
                >
                  <Icon name="be-symbol-upload" />
                </Button>
              )}
              {(sourcesLen > 0 && block.rules.canChangeImg) && (
                <Button
                  title="Upload from source"
                  style={provided.styles.button}
                  onClick={this.handleSourceClick}
                  className="builder-menu-btn"
                  sm
                >
                  <Icon name="be-symbol-host" />
                </Button>
              )}
              {block.rules.canChangeImg && (
                <Button
                  title="Edit image URL"
                  className="builder-menu-btn"
                  style={provided.styles.button}
                  onClick={() => this.handlePromptClick('promptSrcOpen')}
                  sm
                >
                  <Icon name="be-symbol-edit" />
                </Button>
              )}
              {(!isBackground && block.rules.canChangeImg) && (
                <Button
                  title="Edit alternative text"
                  className="builder-menu-btn"
                  style={provided.styles.button}
                  onClick={() => this.handlePromptClick('promptAltOpen')}
                  sm
                >
                  <Icon name="be-symbol-info" />
                </Button>
              )}
              {!isBackground && (
                <Button
                  title="Change link"
                  className="builder-menu-btn"
                  style={provided.styles.button}
                  onClick={() => this.handlePromptClick('promptLinkOpen')}
                  active={!!(block.element.parentNode.getAttribute('href') || '')}
                  sm
                >
                  <Icon name="be-symbol-link" />
                </Button>
              )}
            </div>
          )}
        </Menu>
        <Prompt
          field="src"
          value={imageSrc}
          open={promptSrcOpen}
          placeholder="Image URL"
          onUpdate={this.handlePromptUpdate}
        />
        <Prompt
          field="alt"
          open={promptAltOpen}
          placeholder="Alternative text"
          value={block.element.alt || ''}
          onUpdate={this.handlePromptUpdate}
        />
        <Prompt
          field="background-color"
          open={promptBGOpen}
          placeholder="Background color"
          cancelValue="-1"
          value={bgColor}
          onUpdate={this.handlePromptUpdate}
        />
        <ImageLinkMenu
          block={block}
          open={promptLinkOpen}
          value={block.element.parentNode.getAttribute('href') || ''}
          alt={block.element.parentNode.getAttribute('alias') || ''}
          onUpdate={this.handlePromptUpdate}
          onClose={() => this.setState({ promptLinkOpen: false })}
        />
      </>
    );
  }
}
