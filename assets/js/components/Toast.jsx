import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

export default class Toast extends React.PureComponent {
  static propTypes = {
    hiding:   PropTypes.bool.isRequired,
    children: PropTypes.node.isRequired,
    innerRef: PropTypes.object.isRequired
  };

  static defaultProps = {};

  /**
   * @returns {*}
   */
  render() {
    const { hiding, innerRef, children } = this.props;

    const classes = classNames('toast', { hiding });

    return (
      <div ref={innerRef} className={classes}>
        {children}
      </div>
    );
  }
}
