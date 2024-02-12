import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { connect, useSelector } from 'react-redux';
import { DATA_BLOCK } from 'builder/engine/constants';
import { Button, Icon } from 'components';
import { mapDispatchToProps } from 'utils';
import { builderActions } from 'builder/actions';
import HTMLUtils from 'builder/engine/HTMLUtils';
import Menu from './Menu';
import Prompt from './Prompt';

const BGColorMenu = ({ block, position, builderUpdateBlock }) => {
  const [isPromptOpen, setPromptOpen] = useState(false);
  const iframe = useSelector(state => state.builder.iframe);

  /**
   * @param {Event} e
   * @param {string} value
   */
  const handlePromptUpdate = (e, value) => {
    if (value !== '-1') {
      if (value[0] !== '#' && value.indexOf('rgb') === -1) {
        value = `#${value}`;
      }

      block.element.setAttribute('bgcolor', value);
      HTMLUtils.setStyleValue(block.element, 'background-color', value);
      if (block.element.getAttribute(DATA_BLOCK)) {
        HTMLUtils.replaceBackgroundColor(block.element, block.element.getAttribute(DATA_BLOCK), value, iframe);
      }
      builderUpdateBlock(block.id, 'element', block.element);
    }
    setPromptOpen(false);
  };

  let bgColor = '';
  if (block.element.getAttribute('bgcolor')) {
    bgColor = block.element.getAttribute('bgcolor');
  } else if (HTMLUtils.getStyleValue(block.element, 'background-color')) {
    bgColor = HTMLUtils.getStyleValue(block.element, 'background-color');
  }

  return (
    <>
      <Menu position={position} open>
        {provided => (
          <div
            ref={provided.menuRef}
            style={provided.styles.menu}
            className={`builder-menu builder-menu-bg-color builder-menu-${provided.position}`}
          >
            <Button
              title="Change background color"
              className="builder-menu-btn"
              style={provided.styles.button}
              onClick={() => setPromptOpen(true)}
              sm
            >
              <Icon name="be-symbol-color-palette" />
            </Button>
          </div>
        )}
      </Menu>
      <Prompt
        field="background-color"
        open={isPromptOpen}
        placeholder="Background color"
        cancelValue="-1"
        value={bgColor}
        onUpdate={handlePromptUpdate}
      />
    </>
  );
};

BGColorMenu.propTypes = {
  block:              PropTypes.object.isRequired,
  position:           PropTypes.string,
  builderUpdateBlock: PropTypes.func.isRequired
};

export default connect(null, mapDispatchToProps(builderActions))(BGColorMenu);
