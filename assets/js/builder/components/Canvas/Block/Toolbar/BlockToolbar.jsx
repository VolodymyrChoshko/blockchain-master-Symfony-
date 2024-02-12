import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { Icon } from 'components';
import Flyout from 'builder/components/Flyout';
import { builderActions } from 'builder/actions';
import { actions as commentActions } from 'builder/actions/commentActions';
import HTMLUtils from 'builder/engine/HTMLUtils';
import { DATA_VARIATION_INDEX, BLOCK_SECTION, CLASS_BLOCK_SCRIPT_SEC } from 'builder/engine/constants';
import Toolbar from './Toolbar';
import { MenuButton } from './styles';

const mapStateToProps = state => ({
  groups:          state.builder.groups,
  editing:         state.builder.editing,
  scrollTop:       state.builder.scrollTop,
  draggingBlockID: state.builder.draggingBlockID
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions, commentActions)
)
export default class BlockToolbar extends React.PureComponent {
  timeout = 0;

  static propTypes = {
    block:                PropTypes.object.isRequired,
    groups:               PropTypes.object.isRequired,
    active:               PropTypes.bool.isRequired,
    editing:              PropTypes.bool.isRequired,
    scrollTop:            PropTypes.number.isRequired,
    placement:            PropTypes.string.isRequired,
    draggingBlockID:      PropTypes.number.isRequired,
    builderMove:          PropTypes.func.isRequired,
    builderHoverID:       PropTypes.func.isRequired,
    builderExpandBlock:   PropTypes.func.isRequired,
    builderCloneBlock:    PropTypes.func.isRequired,
    builderRemoveBlock:   PropTypes.func.isRequired,
    builderVariation:     PropTypes.func.isRequired,
    builderLibrarySave:   PropTypes.func.isRequired,
    builderDraggingBlock: PropTypes.func.isRequired,
    attachBlock:          PropTypes.func.isRequired,
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.dragButton = React.createRef();
  }

  /**
   *
   */
  componentWillUnmount() {
    clearTimeout(this.timeout);
  }

  /**
   * @param {Event} e
   * @param {number} blockID
   * @param {string} direction
   */
  handleMove = (e, blockID, direction) => {
    const { builderMove, builderHoverID } = this.props;

    e.preventDefault();
    builderMove(blockID, direction);

    // When the block moves the mouse will be over the block that was in the
    // old blocks spot, and that triggers a hover event. Which leaves two
    // blocks highlighted at the same time. Doesn't look good.
    this.timeout = setTimeout(() => {
      builderHoverID(-1);
    }, 100);
  };

  /**
   * @param {MouseEvent} e
   * @param {number} blockID
   */
  handleDragStart = (e, blockID) => {
    const { scrollTop, builderDraggingBlock } = this.props;

    const canvas      = document.querySelector('.builder-canvas');
    const canvasRect  = canvas.getBoundingClientRect();
    const offsetPageX = Math.floor(e.pageX - canvasRect.left);
    const offsetPageY = Math.floor(e.pageY - canvasRect.top + scrollTop);

    this.dragButton.current.style.cursor = 'move';
    builderDraggingBlock(blockID, offsetPageX, offsetPageY);
  };

  /**
   * @param {MouseEvent} e
   */
  handleDragEnd = (e) => {
    const { scrollTop, builderDraggingBlock } = this.props;

    const canvas      = document.querySelector('.builder-canvas');
    const canvasRect  = canvas.getBoundingClientRect();
    const offsetPageX = Math.floor(e.pageX - canvasRect.left);
    const offsetPageY = Math.floor(e.pageY - canvasRect.top + scrollTop);

    this.dragButton.current.style.cursor = 'default';
    builderDraggingBlock(-1, offsetPageX, offsetPageY);
  };

  /**
   * @returns {[]}
   */
  getMoveButtons = () => {
    const { /** @type {Block} */ block, editing, attachBlock, placement, draggingBlockID, builderCloneBlock } = this.props;
    const { /** @type {Rules} */ rules } = block;

    const buttons = [];
    if (editing && (rules.movesUp && !rules.canRepeat)) {
      buttons.push(
        <Flyout key="moveUp" block={block}>
          <MenuButton
            placement={placement}
            className="builder-menu-btn"
            title="Move Up"
            onClick={e => this.handleMove(e, block.id, 'up')}
          >
            <Icon name="be-symbol-arrow-up" />
          </MenuButton>
        </Flyout>
      );
    }

    if (editing && (rules.movesDown && !rules.canRepeat)) {
      buttons.push(
        <Flyout key="moveDown" block={block}>
          <MenuButton
            placement={placement}
            className="builder-menu-btn"
            title="Move Down"
            onClick={e => this.handleMove(e, block.id, 'down')}
          >
            <Icon name="be-symbol-arrow-down" />
          </MenuButton>
        </Flyout>
      );
    }

    if (editing && block.isComponent()) {
      buttons.push(
        <Flyout key="clone" block={block} innerRef={this.dragButton}>
          <MenuButton
            placement={placement}
            className="builder-menu-btn"
            title={draggingBlockID !== -1 ? '' : 'Move'}
            onMouseDown={e => this.handleDragStart(e, block.id)}
            onMouseUp={this.handleDragEnd}
          >
            <Icon name="be-symbol-drag" />
          </MenuButton>
        </Flyout>
      );
    }

    if (editing && rules.canRepeat) {
      if (rules.maxRepeat === 0 || HTMLUtils.findRepeatCount(block.element) < rules.maxRepeat) {
        buttons.push(
          <Flyout key="clone" block={block}>
            <MenuButton
              placement={placement}
              className="builder-menu-btn"
              title="Clone block"
              onClick={() => builderCloneBlock(block.id)}
            >
              <Icon name="be-symbol-duplicate" />
            </MenuButton>
          </Flyout>
        );
      }

      if (editing && rules.movesUp) {
        buttons.push(
          <Flyout key="clone-up" block={block}>
            <MenuButton
              placement={placement}
              className="builder-menu-btn"
              title="Move up"
              onClick={e => this.handleMove(e, block.id, 'up')}
            >
              <Icon name="be-symbol-arrow-up" />
            </MenuButton>
          </Flyout>
        );
      }

      if (rules.movesDown) {
        buttons.push(
          <Flyout key="clone-down" block={block}>
            <MenuButton
              placement={placement}
              className="builder-menu-btn"
              title="Move down"
              onClick={e => this.handleMove(e, block.id, 'down')}
            >
              <Icon name="be-symbol-arrow-down" />
            </MenuButton>
          </Flyout>
        );
      }
    }

    if (block.type === BLOCK_SECTION) {
      buttons.push(
        <Flyout key="comment" block={block}>
          <MenuButton
            placement={placement}
            className="builder-menu-btn"
            title="Comment"
            onClick={() => attachBlock(block)}
          >
            <Icon name="be-symbol-comment" />
          </MenuButton>
        </Flyout>
      );
    }

    return buttons;
  };

  /**
   * @returns {[]}
   */
  getCenterButtons = () => {
    const {
      /** @type {Block} */ block,
      placement,
      groups,
      editing,
      draggingBlockID,
      builderVariation,
      builderExpandBlock
    } = this.props;

    if (draggingBlockID !== -1 || !editing) {
      return [];
    }

    const buttons = [];
    if (block.element.classList.contains(CLASS_BLOCK_SCRIPT_SEC)
      || (block.element.parentElement && block.element.parentElement.classList.contains(CLASS_BLOCK_SCRIPT_SEC))) {
      buttons.push(
        <Flyout key="expand" block={block}>
          <MenuButton
            placement={placement}
            className="builder-menu-btn"
            title="Expand block"
            onClick={() => builderExpandBlock(block.id)}
          >
            <Icon name="be-symbol-code" />
          </MenuButton>
        </Flyout>
      );
    }

    if (block.groupName) {
      const activeIndex = parseInt(block.element.getAttribute(DATA_VARIATION_INDEX), 10) || 0;
      if (groups[block.groupName]) {
        const group = groups[block.groupName];
        if (group.items.length > 1) {
          group.items.forEach((v, i) => {
            buttons.push(
              <Flyout key={`v-${i}`} block={block}>
                <MenuButton
                  placement={placement}
                  active={activeIndex === i}
                  className={classNames('builder-menu-btn', { 'btn-active': activeIndex === i })}
                  onClick={() => builderVariation(block.id, i)}
                >
                  <strong>{i + 1}</strong>
                </MenuButton>
              </Flyout>
            );
          });
        }
      }
    }

    return buttons;
  };

  /**
   * @returns {[]}
   */
  getRemoveButtons = () => {
    const {
      /** @type {Block} */ block,
      editing,
      placement,
      draggingBlockID,
      builderRemoveBlock,
      builderLibrarySave,
    } = this.props;
    const { /** @type {Rules} */ rules } = block;

    if (draggingBlockID !== -1 || !editing) {
      return [];
    }

    const buttons = [];
    if (block.isSection()) {
      buttons.push(
        <Flyout key="save" block={block}>
          <MenuButton
            placement={placement}
            className="builder-menu-btn"
            title="Save"
            onClick={() => builderLibrarySave(block.id)}
          >
            <Icon name="be-symbol-pin" />
          </MenuButton>
        </Flyout>
      );
    }

    if (rules.canRemove) {
      buttons.push(
        <Flyout key="remove" block={block}>
          <MenuButton
            placement={placement}
            className="builder-menu-btn"
            title="Remove"
            onClick={() => builderRemoveBlock(block.id)}
          >
            <Icon name="be-symbol-delete" />
          </MenuButton>
        </Flyout>
      );
    }

    return buttons;
  };

  /**
   * @returns {*}
   */
  render() {
    const {
      block,
      active,
      placement
    } = this.props;

    const classes = classNames(`builder-menu-common builder-menu-common-b-${block.type}`, {
      active
    });

    const buttonsMove   = this.getMoveButtons();
    const buttonsCenter = this.getCenterButtons();
    const buttonsRemove = this.getRemoveButtons();
    if (buttonsMove.length === 0 && buttonsCenter.length === 0 && buttonsRemove.length === 0) {
      return null;
    }

    return (
      <Toolbar block={block} placement={placement}>
        <div className={classes}>
          <div className="builder-menu-command-btns">
            {buttonsMove}
          </div>
          <div className="builder-menu-command-btns">
            {buttonsCenter}
          </div>
          <div className="builder-menu-command-btns">
            {buttonsRemove}
          </div>
        </div>
      </Toolbar>
    );
  }
}
