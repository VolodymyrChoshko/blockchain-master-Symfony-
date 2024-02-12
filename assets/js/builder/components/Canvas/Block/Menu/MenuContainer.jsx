import React from 'react';
import PropTypes from 'prop-types';
import MenuContext from './MenuContext';

export default class MenuContainer extends React.PureComponent {
  static propTypes = {
    dimensions: PropTypes.shape({
      left:   PropTypes.number,
      top:    PropTypes.number,
      width:  PropTypes.number,
      height: PropTypes.number
    }).isRequired,
    children: PropTypes.node
  };

  static defaultProps = {
    children: ''
  };

  /**
   * @returns {*}
   */
  render() {
    const { children, dimensions } = this.props;

    return (
      <MenuContext.Provider value={{ dimensions }}>
        {children}
      </MenuContext.Provider>
    );
  }
}
