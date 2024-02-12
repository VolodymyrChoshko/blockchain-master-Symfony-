import React from 'react';
import PropTypes from 'prop-types';
import Menu from './Menu';

export default class Prompt extends React.PureComponent {
  static propTypes = {
    open:        PropTypes.bool,
    field:       PropTypes.string.isRequired,
    value:       PropTypes.string,
    cancelValue: PropTypes.string,
    placeholder: PropTypes.string,
    onChange:    PropTypes.func,
    onUpdate:    PropTypes.func
  };

  static defaultProps = {
    open:        false,
    value:       '',
    placeholder: '',
    cancelValue: '',
    onChange:    () => {},
    onUpdate:    () => {}
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.input = React.createRef();
    this.state = {
      value: props.value
    };
  }

  /**
   *
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { open, value } = this.props;
    const { open: pOpen, pValue } = prevProps;

    if (value !== pValue) {
      // this.setState({ value });
    }
    if (open && !pOpen) {
      setTimeout(() => {
        this.input.current.focus();
        this.input.current.select();
      }, 100);
    }
  }

  /**
   *
   */
  handleClick = () => {
    const { field, onUpdate } = this.props;
    const { value } = this.state;

    onUpdate(field, value);
  };

  /**
   *
   */
  handleCancelClick = () => {
    const { field, cancelValue, onUpdate } = this.props;

    onUpdate(field, cancelValue);
  };

  /**
   * @param {Event} e
   */
  handleChange = (e) => {
    const { onChange } = this.props;

    this.setState({ value: e.target.value });
    onChange(e);
  };

  /**
   * @returns {*}
   */
  render() {
    const { open, placeholder } = this.props;
    const { value } = this.state;

    return (
      <Menu position="top" nextPositions={['middle']} open={open}>
        {provided => (
          <div ref={provided.menuRef} className="builder-menu builder-menu-prompt builder-no-canvas-click">
            <input
              ref={this.input}
              value={value}
              placeholder={placeholder}
              className="form-control mr-2 mb-2"
              onChange={this.handleChange}
            />
            <div className="builder-menu-buttons">
              <button className="btn mr-2" onClick={this.handleClick}>
                Okay
              </button>
              <button className="btn" onClick={this.handleCancelClick}>
                Cancel
              </button>
            </div>
          </div>
        )}
      </Menu>
    );
  }
}
