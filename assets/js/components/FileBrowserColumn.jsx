import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Scrollbars } from 'react-custom-scrollbars';
import scrollbars from 'utils/scrollbars';

export default class FileBrowserColumn extends React.PureComponent {
  static propTypes = {
    dir:          PropTypes.string.isRequired,
    list:         PropTypes.array.isRequired,
    allowedTypes: PropTypes.array,
    onSelect:     PropTypes.func
  };

  static defaultProps = {
    allowedTypes: [],
    onSelect:     () => {}
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.state = {
      selected: ''
    };
  }

  /**
   * Returns a boolean indicating whether the given file is one of the allowedTypes or a folder
   *
   * @private
   * @param {File} file
   * @returns {boolean}
   */
  isAllowedType = (file) => {
    const { allowedTypes } = this.props;

    return file.type === 'folder' || allowedTypes.indexOf(file.type) !== -1;
  };

  /**
   * @param {Event} e
   * @param {*} file
   */
  handleClick = (e, file) => {
    const { onSelect, dir } = this.props;
    const { selected } = this.state;

    if (this.isAllowedType(file)) {
      if (selected !== file.path) {
        this.setState({ selected: file.path });
      }
      onSelect(e, file, dir);
    }
  };

  /**
   * @returns {*}
   */
  render() {
    const { list } = this.props;
    const { selected } = this.state;

    if (list.length === 0) {
      return null;
    }

    return (
      <div className="file-browser-column">
        <Scrollbars
          renderTrackVertical={scrollbars.renderTrackVertical}
          renderThumbVertical={scrollbars.renderThumbVertical}
        >
          <ul className="file-browser-column-list">
            {list.map((file) => {
              const classes = classNames(`file-browser-column-list-item file-browser-column-list-item-${file.type}`, {
                'file-browser-column-list-item-disabled': !this.isAllowedType(file),
                'file-browser-column-list-item-selected': file.path === selected
              });

              return (
                <li
                  key={file.name}
                  className={classes}
                  title={file.name}
                  onClick={e => this.handleClick(e, file)}
                >
                  {file.name.toString().replace(/^-[\d]+-/, '')}
                </li>
              );
            })}
          </ul>
        </Scrollbars>
      </div>
    );
  }
}
