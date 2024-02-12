import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import arrays from 'utils/arrays';
import objects from 'utils/objects';
import Toast from './Toast';

const mapStateToProps = state => ({
  toastMessages: state.ui.toastMessages
});

@connect(
  mapStateToProps,
  mapDispatchToProps()
)
export default class ToastContainer extends React.Component {
  static propTypes = {
    toastMessages: PropTypes.array.isRequired
  };

  static defaultProps = {};

  /**
   * @param {*} props
   * @param {*} state
   * @returns {null|{messages: (*|Array)}}
   */
  static getDerivedStateFromProps(props, state) {
    const { toastMessages } = props;
    const { messages } = state;

    if (toastMessages.length !== messages.length) {
      const stale = [];
      toastMessages.forEach((toast) => {
        const index = arrays.findIndexByID(messages, toast.id);
        if (index === -1) {
          ToastContainer.refs[toast.id] = React.createRef();
          messages.push(toast);
        }
      });

      messages.forEach((message) => {
        const index = arrays.findIndexByID(toastMessages, message.id);
        if (index === -1 && arrays.findIndexByID(stale, message.id) === -1) {
          stale.push(message);
        }
      });

      return {
        stale,
        messages
      };
    }

    return null;
  }

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.state = {
      messages: [],
      stale:    []
    };
  }

  /**
   * @param {*} prevProps
   * @param {*} prevState
   */
  componentDidUpdate(prevProps, prevState) {
    const { messages, stale } = this.state;
    const { stale: pStale } = prevState;

    const removing = [];
    if (stale.length > pStale.length) {
      const newMessages = objects.clone(messages);
      stale.forEach((toast) => {
        const index = arrays.findIndexByID(messages, toast.id);
        if (index !== -1) {
          newMessages[index].hiding = true;
          removing.push(newMessages[index]);
        }
      });
      this.setState({ messages: newMessages });

      if (removing.length > 0) {
        setTimeout(() => {
          removing.forEach((remove) => {
            const index = arrays.findIndexByID(messages, remove.id);
            if (index !== -1) {
              newMessages.splice(index, 1);
            }
          });
          this.setState({ messages: newMessages });
        }, 500);
      }
    }
  }

  static refs = [];

  /**
   * @returns {*}
   */
  render() {
    const { messages } = this.state;

    const toasts = messages.map(toast => (
      <Toast
        key={toast.id}
        hiding={toast.hiding || false}
        innerRef={ToastContainer.refs[toast.id]}
      >
        {toast.message}
      </Toast>
    ));

    return ReactDOM.createPortal(toasts, document.body);
  }
}
