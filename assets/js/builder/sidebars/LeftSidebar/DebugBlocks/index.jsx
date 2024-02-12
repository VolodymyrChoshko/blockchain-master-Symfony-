import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { BlockCollection } from 'builder/engine';
import { builderActions } from 'builder/actions';
import { scrollIntoView } from 'utils/browser';
import DebugBlock from './DebugBlock';

const mapStateToProps = state => ({
  blocks:         state.builder.blocks,
  hoverID:        state.builder.hoverID,
  hoverSectionID: state.builder.hoverSectionID,
  activeID:       state.builder.activeID,
  canvas:         state.builder.canvas
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions)
)
export default class DebugBlocks extends React.PureComponent {
  isClicked = false;

  static propTypes = {
    blocks:           PropTypes.instanceOf(BlockCollection).isRequired,
    hoverID:          PropTypes.number.isRequired,
    activeID:         PropTypes.number.isRequired,
    hoverSectionID:   PropTypes.number.isRequired,
    canvas:           PropTypes.object.isRequired,
    builderActiveID:  PropTypes.func.isRequired,
    builderVariation: PropTypes.func.isRequired
  };

  static defaultProps = {};

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { activeID, hoverSectionID } = this.props;
    const { activeID: pActiveID, hoverSectionID: pHoverSectionID } = prevProps;

    if (activeID !== pActiveID) {
      if (this.isClicked) {
        this.isClicked = false;
        return;
      }
      const el = document.getElementById(`draggable-block-debug-${activeID}`);
      scrollIntoView(el);
    } else if (hoverSectionID !== pHoverSectionID) {
      const el = document.getElementById(`draggable-block-debug-${hoverSectionID}`);
      scrollIntoView(el);
    }
  }

  /**
   * @param {Event} e
   * @param {Block} block
   */
  handleClick = (e, block) => {
    const { canvas, builderActiveID } = this.props;

    const { id } = block;
    this.isClicked = true;
    builderActiveID(id);
    const el = canvas.querySelector(`[data-block-id="${id}"]`);
    scrollIntoView(el);
  };

  /**
   * @param {Event} e
   * @param {Block} block
   * @param {Block} variation
   */
  handleVariationClick = (e, block, variation) => {
    const { canvas, builderVariation } = this.props;

    e.stopPropagation();
    builderVariation(block.id, variation.id);

    const el = canvas.querySelector(`[data-block-id="${variation.id}"]`);
    scrollIntoView(el);
  };

  /**
   * @returns {*}
   */
  render() {
    const { blocks, activeID, hoverID, hoverSectionID } = this.props;

    return (
      <div className="builder-sidebar-draggables-layouts">
        <h3>{blocks.length} Blocks</h3>
        {blocks.map(block => (
          <DebugBlock
            key={block.id}
            block={block}
            active={block.id === activeID}
            hover={block.id === hoverID || block.id === hoverSectionID}
            onClick={this.handleClick}
            onVariationClick={this.handleVariationClick}
          />
        ))}
      </div>
    );
  }
}
