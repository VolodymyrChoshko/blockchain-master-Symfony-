import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { uiActions } from 'builder/actions';

@connect(
  null,
  mapDispatchToProps({ uiToast: uiActions.uiToast })
)
export default class ErrorBoundary extends React.Component {
  static propTypes = {
    uiToast: PropTypes.func.isRequired
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);
    this.state = {
      hasError: false,
      error:    ''
    };
  }

  /**
   * @returns {{hasError: boolean}}
   */
  static getDerivedStateFromError() {
    return {
      hasError: true
    };
  }

  /**
   * @param {*} error
   */
  componentDidCatch(error) {
    const { uiToast } = this.props;

    console.log(error);
    this.setState({ error });
    uiToast(error.toString());
  }

  /**
   * @returns {*}
   */
  render() {
    const { children } = this.props;
    const { hasError, error } = this.state;

    if (hasError) {
      return (
        <div className="d-flex align-items-center justify-content-center flex-column w-100 h-100">
          <h1>Something went wrong.</h1>
          <p>
            {error.toString()}
          </p>
        </div>
      );
    }

    return children;
  }
}
