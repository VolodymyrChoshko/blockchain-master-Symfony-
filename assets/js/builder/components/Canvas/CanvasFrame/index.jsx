import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import browser from 'utils/browser';
import { builderActions } from 'builder/actions';
import { actions as ruleActions } from 'builder/actions/ruleActions';
import { Container } from './styles';

const mapStateToProps = state => ({
  editing:        state.builder.editing,
  canvasHeight:   state.builder.canvasHeight,
  previewDevice:  state.ui.previewDevice,
  isRulesEditing: state.rules.isEditing,
  scrollToBlock:  state.builder.scrollToBlock,
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions, ruleActions)
)
export default class CanvasFrame extends React.PureComponent {
  static propTypes = {
    html:                 PropTypes.string.isRequired,
    style:                PropTypes.object,
    editing:              PropTypes.bool.isRequired,
    canvasHeight:         PropTypes.number.isRequired,
    previewDevice:        PropTypes.string.isRequired,
    isRulesEditing:       PropTypes.bool.isRequired,
    onLoad:               PropTypes.func,
    builderUpdateBlocks:  PropTypes.func.isRequired,
    builderIFrameMounted: PropTypes.func.isRequired,
    builderIFrameRefresh: PropTypes.func.isRequired,
    builderScrollToBlock: PropTypes.func.isRequired,
    frameResize:          PropTypes.func.isRequired,
    scrollToBlock:        PropTypes.number.isRequired,
  };

  static defaultProps = {
    style:  {},
    onLoad: () => {}
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.iframe     = React.createRef();
    this.lastHeight = 0;
    this.interval   = 0;
  }

  /**
   *
   */
  componentDidMount() {
    const { html, builderUpdateBlocks, builderIFrameMounted } = this.props;

    const iframe = this.iframe.current;
    window.addEventListener('resize', this.handleWindowResize, false);
    iframe.addEventListener('load', this.handleLoad, true);

    this.lastHeight = this.getFrameHeight();
    this.interval = setInterval(() => {
      if (this.getFrameHeight() !== this.lastHeight) {
        this.lastHeight = this.getFrameHeight();
        this.handleHeightChange();
      }
    }, 100);

    builderIFrameMounted(iframe);
    browser.iFrameSrc(iframe, html);
    builderUpdateBlocks();

    const doc = browser.iFrameDocument(iframe);
    doc.removeEventListener('mousedown', this.handleClick);
    doc.addEventListener('mousedown', this.handleClick, false);
  }

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { html, canvasHeight, previewDevice, builderIFrameRefresh, scrollToBlock, onLoad } = this.props;
    const { html: pHtml, canvasHeight: pCanvasHeight, previewDevice: pPreviewDevice, scrollToBlock: pScrollToBlock } = prevProps;

    if (html !== pHtml || previewDevice !== pPreviewDevice) {
      const iframe        = this.iframe.current;
      iframe.height       = `${this.getFrameHeight()}px`;
      iframe.style.height = iframe.height;

      builderIFrameRefresh();
      onLoad(null, iframe, this.getFrameHeight());

      // browser.iFrameSrc(this.iframe.current, html);
    }

    if (canvasHeight !== pCanvasHeight) {
      const iframe        = this.iframe.current;
      iframe.height       = `${this.getFrameHeight()}px`;
      iframe.style.height = iframe.height;
    }

    if (scrollToBlock && scrollToBlock !== pScrollToBlock) {
      const el = browser.iFrameDocument(this.iframe.current).querySelector(`[data-be-id="${scrollToBlock}"]`);
      browser.scrollIntoView(el);
    }

    const doc = browser.iFrameDocument(this.iframe.current);
    doc.removeEventListener('mousedown', this.handleClick);
    doc.addEventListener('mousedown', this.handleClick, false);
  }

  /**
   *
   */
  componentWillUnmount() {
    const iframe = this.iframe.current;
    window.removeEventListener('resize', this.handleWindowResize);
    iframe.removeEventListener('load', this.handleLoad);
    clearInterval(this.interval);
  }

  /**
   * @returns {number}
   */
  getFrameHeight = () => {
    const doc = browser.iFrameDocument(this.iframe.current);
    if (!doc.body) {
      return 0;
    }

    const el  = doc.documentElement;
    return Math.max(doc.body.scrollHeight, doc.body.offsetHeight, el.clientHeight, el.scrollHeight, el.offsetHeight);
  };

  /**
   *
   */
  handleHeightChange = () => {
    const { builderUpdateBlocks } = this.props;

    const iframe        = this.iframe.current;
    iframe.height       = `${this.getFrameHeight()}px`;
    iframe.style.height = iframe.height;
    builderUpdateBlocks();
  };

  /**
   *
   */
  handleWindowResize = () => {
    const { builderUpdateBlocks, isRulesEditing, frameResize } = this.props;

    setTimeout(() => {
      if (isRulesEditing) {
        frameResize();
      } else {
        builderUpdateBlocks();
      }
    }, 100);
  };

  /**
   * @param {Event} e
   */
  handleLoad = (e) => {
    const { onLoad, builderIFrameRefresh } = this.props;

    const iframe = this.iframe.current;
    const doc    = browser.iFrameDocument(iframe);

    // Stop links from opening.
    doc.addEventListener('click', (e2) => {
      if (browser.hasParentTag(e2.target, 'A')) {
        // eslint-disable-next-line react/destructuring-assignment
        if (this.props.editing) {
          e2.preventDefault();
        } else {
          e2.preventDefault();
          let url = '';
          if (e2.target.tagName === 'A') {
            url = e2.target.getAttribute('href');
          } else if (e2.target.parentNode.tagName === 'A') {
            url = e2.target.parentNode.getAttribute('href');
          }
          if (url) {
            if (url.startsWith('#')) {
              const el = doc.querySelector(url);
              browser.scrollIntoView(el);
            } else {
              window.open(url, '_blank');
            }
          }
        }
      }
    });

    iframe.height       = `${this.getFrameHeight()}px`;
    iframe.style.height = iframe.height;

    if (!window.undoBusy) {
      setTimeout(() => {
        builderIFrameRefresh();
        onLoad(e, iframe, this.getFrameHeight());
      }, 1);
    }
  };

  /**
   *
   */
  handleClick = () => {
    const { scrollToBlock, builderScrollToBlock } = this.props;

    if (scrollToBlock) {
      builderScrollToBlock(0);
    }
  };

  /**
   * @returns {*}
   */
  render() {
    const { style } = this.props;

    return (
      <Container
        id="canvas-iframe"
        scrolling="no"
        src="about:blank"
        style={style}
        ref={this.iframe}
      />
    );
  }
}
