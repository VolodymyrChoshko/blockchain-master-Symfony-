import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { builder as builderStyles } from 'lib/styles';

@connect(
  null,
  mapDispatchToProps()
)
export default class Pill extends React.PureComponent {
  static propTypes = {
    dimensions: PropTypes.object.isRequired,
    className:  PropTypes.string,
    children:   PropTypes.node
  };

  static defaultProps = {
    className: '',
    children:  ''
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.timeout = 0;
    this.ref     = React.createRef();
    this.state   = {
      styles: {}
    };
  }

  /**
   *
   */
  componentDidMount() {
    this.timeout = setTimeout(() => {
      this.setState({
        styles: this.getStyles()
      });
    }, 150);
  }

  /**
   *
   */
  componentWillUnmount() {
    clearTimeout(this.timeout);
  }

  /**
   * @returns {{top: *, left: number}}
   */
  getStyles = () => {
    const { dimensions } = this.props;
    const { menuOffset } = builderStyles;
    const { offsetWidth } = this.ref.current;

    return {
      opacity: 1,
      top:     Math.floor((dimensions.top + dimensions.height + menuOffset)),
      left:    Math.floor(dimensions.left + ((dimensions.width / 2) - (offsetWidth / 2)))
    };
  };

  /**
   * @returns {*}
   */
  render() {
    const { className, children } = this.props;
    const { styles } = this.state;

    return (
      <div ref={this.ref} className={classNames('builder-pill', className)} style={styles}>
        {children}
      </div>
    );
  }
}
