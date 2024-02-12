import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { BlockCollection } from 'builder/engine';
import DropZone from './DropZone';
import Highlight from './Highlight';
import Block from '../Block';

const mapStateToProps = state => ({
  blocks:        state.builder.blocks,
  zones:         state.builder.zones,
  editingID:     state.builder.editingID,
  dropZoneID:    state.builder.dropZoneID,
  draggableID:   state.builder.draggableID,
  draggable:     state.builder.draggable,
  scrollToBlock: state.builder.scrollToBlock
});

@connect(
  mapStateToProps,
  mapDispatchToProps()
)
export default class Blocks extends React.PureComponent {
  static propTypes = {
    blocks:        PropTypes.instanceOf(BlockCollection).isRequired,
    zones:         PropTypes.array.isRequired,
    editingID:     PropTypes.number.isRequired,
    draggable:     PropTypes.object,
    draggableID:   PropTypes.number.isRequired,
    dropZoneID:    PropTypes.number.isRequired,
    scrollToBlock: PropTypes.number.isRequired,
  };

  static defaultProps = {};

  /**
   * @returns {*}
   */
  renderBlocks = () => {
    const { /** @type BlockCollection */ blocks } = this.props;

    const len  = blocks.length;
    let zIndex = len + 1;

    return blocks.map(block => (
      <Block
        key={block.id}
        zIndex={zIndex -= 1}
        block={block}
      />
    ));
  };

  /**
   * @returns {*}
   */
  renderZones = () => {
    const { /** @type Zone[] */ zones, dropZoneID, draggableID, draggable, editingID } = this.props;

    if (draggableID === -1) {
      return [];
    }

    return zones.map((z) => {
      const active = dropZoneID === z.id && (draggable && z.type === draggable.type);
      let visible  = editingID === -1;
      if (z.isCode() && !draggable.capabilities) {
        // Sections cannot be dropped on isCode zones. Which are reserved for AMPscript blocks.
        visible = false;
      }

      return (
        <DropZone
          zone={z}
          key={`zone-${z.id}`}
          visible={visible}
          active={active}
        />
      );
    });
  };

  /**
   * @return {*[]}
   */
  renderHighlight = () => {
    const { blocks, scrollToBlock } = this.props;

    if (!scrollToBlock) {
      return [];
    }

    const block = blocks.getByID(scrollToBlock);
    if (!block) {
      return [];
    }

    return [
      <Highlight key="highlight" block={block} />
    ];
  };

  /**
   * @returns {*}
   */
  render() {
    const blocks = this.renderBlocks();
    const zones  = this.renderZones();
    const highlight = this.renderHighlight();

    return blocks.concat(zones).concat(highlight);
  }
}
