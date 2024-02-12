import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import classNames from 'classnames';
import { BlockCollection, HTMLUtils } from 'builder/engine';
import { mapDispatchToProps } from 'utils';
import browser from 'utils/browser';
import { Icon, Button } from 'components';
import { editableActions, builderActions } from 'builder/actions';
import LinkPrompt from './LinkPrompt';
import Prompt from './Prompt';
import Menu from './Menu';

const mapStateToProps = state => ({
  iframe:           state.builder.iframe,
  blocks:           state.builder.blocks,
  editingID:        state.builder.editingID,
  anchorStyleIndex: state.editable.anchorStyleIndex,
  linkValue:        state.editable.linkValue,
  linkAlias:        state.editable.linkAlias,
  activeTools:      state.editable.activeTools
});

@connect(
  mapStateToProps,
  mapDispatchToProps(editableActions, builderActions)
)
export default class EditableMenu extends React.Component {
  static propTypes = {
    blocks:                  PropTypes.instanceOf(BlockCollection).isRequired,
    editingID:               PropTypes.number.isRequired,
    position:                PropTypes.string,
    linkValue:               PropTypes.string.isRequired,
    linkAlias:               PropTypes.string.isRequired,
    activeTools:             PropTypes.object.isRequired,
    anchorStyleIndex:        PropTypes.number.isRequired,
    iframe:                  PropTypes.object.isRequired,
    onChange:                PropTypes.func,
    editableExec:            PropTypes.func.isRequired,
    editableInit:            PropTypes.func.isRequired,
    editableReset:           PropTypes.func.isRequired,
    editableToolsQuery:      PropTypes.func.isRequired,
    editableUpdateLink:      PropTypes.func.isRequired,
    builderUpdateBlock:      PropTypes.func.isRequired,
    editableUpdateLinkValue: PropTypes.func.isRequired
  };

  static defaultProps = {
    position: 'top',
    onChange: () => {}
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.block          = null;
    this.clickedElement = null;
    this.unmounting     = false;
    this.state = {
      linkOpen:         false,
      promptAnchorOpen: false,
    };
  }

  /**
   *
   */
  componentDidMount() {
    const { blocks, editingID, editableInit } = this.props;

    /** @type {Block} */
    this.block = blocks.getByID(editingID);

    setTimeout(() => {
      this.block.element.addEventListener('mousedown', this.handleElementClick, false);
      // this.block.element.addEventListener('mouseup', editableToolsQuery, false);
      editableInit();
    }, 200);
  }

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { editingID, blocks, editableInit } = this.props;
    const { editingID: pEditingID, blocks: pBlocks } = prevProps;

    if (editingID !== pEditingID) {
      pBlocks.getByID(pEditingID).element.removeEventListener('mousedown', this.handleElementClick, false);

      /** @type {Block} */
      this.block = blocks.getByID(editingID);
      this.block.element.addEventListener('mousedown', this.handleElementClick, false);
      editableInit();
    }
  }

  /**
   *
   */
  componentWillUnmount() {
    const { blocks, editingID, editableReset } = this.props;

    /** @type {Block} */
    const block = blocks.getByID(editingID);
    block.element.removeEventListener('mousedown', this.handleElementClick, false);
    editableReset();
    this.unmounting = true;
  }

  /**
   * @param {MouseEvent} e
   */
  handleElementClick = (e) => {
    const { iframe, editableToolsQuery } = this.props;
    const { clientX, clientY } = e;

    this.clickedElement = browser.iFrameDocument(iframe)
      .elementFromPoint(clientX, clientY);
    setTimeout(() => {
      editableToolsQuery(null, this.clickedElement);
    }, 50);
  };

  /**
   * @param {string} name
   * @param {string} value
   * @param {string} alias
   * @param {Block} block
   */
  handleLinkChange = (name, value, alias, block = null) => {
    const { activeTools, onChange, editableExec, editableUpdateLink, builderUpdateBlock, iframe } = this.props;

    if (value !== null && value !== false) {
      if (block && block.tag === 'a') {
        block.element.setAttribute('href', value);
        block.element.setAttribute('alias', alias);
        block.element.setAttribute('data-be-anchor', 'true');
        builderUpdateBlock(block.id, 'element', block.element);
        const dataBlock = block.element.getAttribute('data-block');
        if (dataBlock) {
          HTMLUtils.replaceHrefComment(block.element, dataBlock, value, iframe);
        }
      } else if (block && activeTools.link && (this.clickedElement && this.clickedElement.tagName === 'A')) {
        this.clickedElement.setAttribute('href', value);
        this.clickedElement.setAttribute('alias', alias);
        this.clickedElement.setAttribute('data-be-anchor', 'true');
        const dataBlock = block.element.getAttribute('data-block');
        if (dataBlock) {
          HTMLUtils.replaceHrefComment(this.clickedElement, dataBlock, value, iframe);
        }
      } else {
        editableExec('createLink', value, { alias });
      }
    } else if (value !== false) {
      editableExec('unlink');
      const dataBlock = block.element.getAttribute('data-block');
      if (dataBlock) {
        HTMLUtils.replaceHrefComment(block.element, dataBlock, '', iframe);
      }
    }

    if (!this.unmounting) {
      this.setState({ linkOpen: false });
      onChange();
    }
    editableUpdateLink('', '');
  };

  /**
   * @param {string} field
   * @param {string} value
   */
  handleAnchorChange = (field, value) => {
    const { builderUpdateBlock } = this.props;
    const { element } = this.block;

    element.id = value;
    builderUpdateBlock(this.block.id, 'element', element);
    this.setState({ promptAnchorOpen: false });
  };

  /**
   * @param {Event} e
   * @param {string} tool
   * @param {string} value
   * @param {Block} block
   */
  handleToolClick = (e, tool, value = '', block = null) => {
    const { activeTools, editableExec, editableUpdateLink, editableUpdateLinkValue } = this.props;

    if (tool === 'link') {
      editableUpdateLinkValue();

      if ((block && block.tag === 'a') || (block && activeTools.link)) {
        if (block.tag !== 'a' && activeTools.link) {
          if (this.clickedElement && this.clickedElement.tagName === 'A') {
            editableUpdateLink(
              this.clickedElement.getAttribute('href') || '',
              this.clickedElement.getAttribute('alias') || '',
              this.clickedElement
            );
          }
        } else {
          editableUpdateLink(
            block.element.getAttribute('href') || '',
            block.element.getAttribute('alias') || ''
          );
        }
        this.setState({ linkOpen: true });
      } else if (this.clickedElement && this.clickedElement.tagName === 'A') {
        editableUpdateLink(
          this.clickedElement.getAttribute('href') || '',
          this.clickedElement.getAttribute('alias') || ''
        );
        this.setState({ linkOpen: true });
      } else if (!activeTools.link) {
        editableUpdateLink(
          '',
          '',
          block.element
        );
        this.setState({ linkOpen: true });
      } else {
        editableExec('unlink', '');
      }
    } else if (tool === 'unlink') {
      editableExec('unlink', '');
    } else {
      editableExec(tool, value, {}, null, block.id);
    }
  };

  /**
   * @param {Event} e
   * @param {number|string} index
   * @param {string} value
   * @param {Block} block
   */
  handleOptClick = (e, index, value, block) => {
    const { editableExec } = this.props;

    let anchorStyles = [];
    if (block) {
      // const section = blocks.getByID(block.parentSectionID);
      if (block.anchorStyles.length > 0) {
        ({ anchorStyles } = block);
      }
    }
    editableExec('createLink', value, {
      style: anchorStyles[index] ? anchorStyles[index] : 'orig',
      index
    }, block.element);

    return;

    if (index === -1) {
      editableExec('createLink', value, {
        style: 'orig',
        index
      }, block.element);
    } else {
      editableExec('createLink', value, {
        style: anchorStyles[index].style,
        index
      }, block.element);
    }
  };

  /**
   * @returns {*}
   */
  render() {
    const {
      blocks,
      iframe,
      activeTools,
      editingID,
      position,
      linkValue,
      linkAlias,
      anchorStyleIndex
    } = this.props;
    const { linkOpen, promptAnchorOpen } = this.state;

    let anchorStyles = [];
    const block = blocks.getByID(editingID);
    if (block && !(block.element.tagName === 'A' && block.isEdit())) {
      // const section = blocks.getByID(block.parentSectionID);
      if (block.anchorStyles.length > 0) {
        ({ anchorStyles } = block);
      }
    }

    const { rules } = block;
    const buttons = [];
    if (rules.canBold) {
      buttons.push((provided) => (
        <Button
          key="bold"
          title="Bold"
          className={classNames('builder-menu-btn btn', { 'btn-active': activeTools.bold })}
          style={provided.styles.button}
          onClick={e => this.handleToolClick(e, 'bold', '', this.block)}
          onMouseDown={e => e.preventDefault()}
        >
          <Icon name="be-symbol-bold" />
        </Button>
      ));
    }
    if (rules.canItalic) {
      buttons.push((provided) => (
        <Button
          key="italic"
          title="Italic"
          className={classNames('builder-menu-btn btn', { 'btn-active': activeTools.italic })}
          style={provided.styles.button}
          onClick={e => this.handleToolClick(e, 'italic', '', this.block)}
          onMouseDown={e => e.preventDefault()}
        >
          <Icon name="be-symbol-italic" />
        </Button>
      ));
    }
    if (rules.canSuperscript) {
      buttons.push((provided) => (
        <Button
          key="superscript"
          title="Superscript"
          className={classNames('builder-menu-btn btn', { 'btn-active': activeTools.superscript })}
          style={provided.styles.button}
          onClick={e => this.handleToolClick(e, 'superscript', '', this.block)}
          onMouseDown={e => e.preventDefault()}
        >
          <Icon name="be-symbol-superscript" />
        </Button>
      ));
    }
    if (rules.canSubscript) {
      buttons.push((provided) => (
        <Button
          key="subscript"
          title="Subscript"
          className={classNames('builder-menu-btn btn', { 'btn-active': activeTools.subscript })}
          style={provided.styles.button}
          onClick={e => this.handleToolClick(e, 'subscript', '', this.block)}
          onMouseDown={e => e.preventDefault()}
        >
          <Icon name="be-symbol-subscript" />
        </Button>
      ));
    }
    if (rules.canAnchorEdit) {
      buttons.push((provided) => (
        <Button
          key="anchor"
          title="Edit Anchor"
          className="builder-menu-btn"
          style={provided.styles.button}
          onClick={() => this.setState({ promptAnchorOpen: true })}
          onMouseDown={e => e.preventDefault()}
        >
          <Icon name="be-symbol-link" />
        </Button>
      ));
    }
    if (rules.canLink) {
      buttons.push((provided) => (
        <Button
          key="link"
          title="Link"
          className={classNames('builder-menu-btn btn', { 'btn-active': activeTools.link })}
          style={provided.styles.button}
          onClick={e => this.handleToolClick(e, 'link', '', this.block)}
          onMouseDown={e => e.preventDefault()}
        >
          <Icon name="be-symbol-link" />
        </Button>
      ));
    }

    if (buttons.length === 0) {
      return null;
    }

    return (
      <Menu position={position} open>
        {provided => (
          <>
            <div
              ref={provided.menuRef}
              style={provided.styles.menu}
              className={`builder-menu builder-menu-${provided.position}`}
            >
              {buttons.map((btn) => {
                return btn(provided);
              })}
            </div>
            <LinkPrompt
              field="src"
              iframe={iframe}
              open={linkOpen}
              href={linkValue}
              alt={linkAlias}
              anchorStyles={anchorStyles}
              anchorStyleIndex={anchorStyleIndex}
              onUpdate={(n, v, a) => this.handleLinkChange(n, v, a, this.block)}
              onOptClick={(e, opt, value) => this.handleOptClick(e, opt, value, this.block)}
            />
            <Prompt
              field="id"
              value={block.element.id || ''}
              open={promptAnchorOpen}
              placeholder="Anchor"
              onUpdate={this.handleAnchorChange}
            />
          </>
        )}
      </Menu>
    );
  }
}
