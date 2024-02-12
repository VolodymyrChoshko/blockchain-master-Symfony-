import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { Icon, Button } from 'components';
import { builderActions } from 'builder/actions';
import Prompt from './Prompt';
import Menu from './Menu';

@connect(
  null,
  mapDispatchToProps(builderActions)
)
export default class LinkMenu extends React.PureComponent {
  static propTypes = {
    block:              PropTypes.object.isRequired,
    position:           PropTypes.string,
    onMouseEnter:       PropTypes.func,
    onMouseLeave:       PropTypes.func,
    builderUpdateBlock: PropTypes.func.isRequired
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
      promptLinkOpen: false
    };
  }

  /**
   * @param {string} field
   * @param {string} value
   */
  handlePromptUpdate = (field, value) => {
    const { block, builderUpdateBlock } = this.props;
    const { element } = block;

    if (field === 'link') {
      if (value !== element.href) {
        element.href = value;
        builderUpdateBlock(block.id, 'element', element);
      }
      this.setState({ promptLinkOpen: false });
    }
  };

  /**
   * @param {string} field
   */
  handlePromptClick = (field) => {
    const state = {
      promptLinkOpen: false
    };

    state[field] = true;
    this.setState(state);
  };

  /**
   * @returns {*}
   */
  render() {
    const { block, position, onMouseEnter, onMouseLeave } = this.props;
    const { promptLinkOpen } = this.state;

    return (
      <Menu position={position} nextPositions={['middle']} open>
        {provided => (
          <>
            <div
              ref={provided.menuRef}
              style={provided.styles.menu}
              className="builder-menu builder-menu-image"
              onMouseEnter={onMouseEnter}
              onMouseLeave={onMouseLeave}
            >
              <Button
                title="Change link"
                className="builder-menu-btn"
                style={provided.styles.button}
                onClick={() => this.handlePromptClick('promptLinkOpen')}
                sm
              >
                <Icon name="be-symbol-link" />
              </Button>
            </div>
            <Prompt
              field="link"
              placeholder="URL"
              open={promptLinkOpen}
              style={provided.styles.menu}
              value={block.element.getAttribute('href')}
              onUpdate={this.handlePromptUpdate}
            />
          </>
        )}
      </Menu>
    );
  }
}
