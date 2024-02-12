import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { builderActions } from 'builder/actions';
import { Button, Icon } from 'components';
import Prompt from './Prompt';
import Menu from './Menu';

const AnchorMenu = ({ block, position, builderUpdateBlock }) => {
  const [isPromptOpen, setPromptOpen] = useState(false);

  /**
   * @param {string} field
   * @param {string} value
   */
  const handleChange = (field, value) => {
    const { element } = block;

    element.id = value;
    builderUpdateBlock(block.id, 'element', element);
    setPromptOpen(false);
  };

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
              <Icon name="be-symbol-link" />
            </Button>
          </div>
        )}
      </Menu>
      <Prompt
        field="id"
        value={block.element.id || ''}
        open={isPromptOpen}
        placeholder="Anchor"
        onUpdate={handleChange}
      />
    </>
  );
};

AnchorMenu.propTypes = {
  block:              PropTypes.object.isRequired,
  position:           PropTypes.string,
  builderUpdateBlock: PropTypes.func.isRequired
};

export default connect(null, mapDispatchToProps(builderActions))(AnchorMenu);
