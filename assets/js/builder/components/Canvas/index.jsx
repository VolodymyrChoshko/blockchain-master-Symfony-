import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { BlockCollection } from 'builder/engine';
import { mapDispatchToProps } from 'utils';
import { Loading } from 'components';
import { builder } from 'lib/styles';
import { builderActions } from 'builder/actions';
import { BLOCK_SECTION, BLOCK_COMPONENT } from 'builder/engine/constants';
import ToolbarProvider from './Block/Toolbar/ToolbarProvider';
import WatchMouse from './WatchMouse';
import WatchRulesMouse from './WatchRulesMouse';
import BlockZones from './BlockZones';
import HtmlEditor from './HtmlEditor';
import ElementPath from './ElementPath';
import Blocks from './Blocks';
import Block from './Block';
import CanvasFrame from './CanvasFrame';
import { Container } from './styles';

const mapStateToProps = state => ({
  html:           state.builder.html,
  blocks:         state.builder.blocks,
  isEmpty:        state.builder.isEmpty,
  editing:        state.builder.editing,
  draggable:      state.builder.draggable,
  draggableID:    state.builder.draggableID,
  iframeReady:    state.builder.iframeReady,
  previewDevice:  state.ui.previewDevice,
  isRuleEditing:  state.rules.isEditing,
  isEditingHtml:  state.rules.isEditingHtml,
  isExpandedHtml: state.rules.isExpandedHtml,
  rulesMode:      state.rules.mode,
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions)
)
export default class Canvas extends React.PureComponent {
  static propTypes = {
    blocks:               PropTypes.instanceOf(BlockCollection).isRequired,
    isEmpty:              PropTypes.bool.isRequired,
    html:                 PropTypes.string.isRequired,
    iframeReady:          PropTypes.bool.isRequired,
    editing:              PropTypes.bool.isRequired,
    isRuleEditing:        PropTypes.bool.isRequired,
    isEditingHtml:        PropTypes.bool.isRequired,
    isExpandedHtml:       PropTypes.bool.isRequired,
    rulesMode:            PropTypes.string.isRequired,
    draggable:            PropTypes.object,
    draggableID:          PropTypes.number.isRequired,
    builderIFrameRefresh: PropTypes.func.isRequired,
    builderScrollTop:     PropTypes.func.isRequired,
    builderCanvasHeight:  PropTypes.func.isRequired,
    builderCanvasMounted: PropTypes.func.isRequired
  };

  static defaultProps = {};

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.iframe = null;
    this.canvas = React.createRef();
    this.state  = {
      iframeTimeout: false
    };
  }

  /**
   *
   */
  componentDidMount() {
    const { builderCanvasHeight, builderCanvasMounted } = this.props;

    builderCanvasMounted(this.canvas.current);
    builderCanvasHeight(this.canvas.current.scrollHeight);
    setTimeout(() => {
      this.setState({ iframeTimeout: true });
    }, 2000);
  }

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { editing, html, builderIFrameRefresh } = this.props;
    const { editing: pEditing, html: pHTML } = prevProps;

    // When the "Start editing" button is clicked.
    if (editing !== pEditing) {
      builderIFrameRefresh();
    }

    // Letting us know when the preview device changes. (Which uses a transition
    // effect.)
    if (html !== pHTML) {
      this.canvas.current.addEventListener('transitionend', this.handleTransitionEnd, false);
    }
  }

  /**
   * @returns {null|*}
   */
  getEmptyBlock = () => {
    const { blocks } = this.props;

    return blocks.find((b) => {
      if (b.empty) {
        return b;
      }
      return null;
    });
  };

  /**
   * @param {Event} e
   */
  handleScroll = (e) => {
    const { builderScrollTop } = this.props;

    builderScrollTop(e.target.scrollTop);
  };

  /**
   * @param {Event} e
   */
  handleTransitionEnd = (e) => {
    const { builderCanvasHeight, builderIFrameRefresh } = this.props;

    // Event fires multiple times for each css transition. Make sure it only
    // fires once.
    if (e.target === this.canvas.current && e.propertyName === 'width') {
      setTimeout(() => {
        builderCanvasHeight(this.canvas.current.scrollHeight);
        builderIFrameRefresh();
      }, 50);
    }
  };

  /**
   * @returns {*}
   */
  renderBody = () => {
    const {
      html,
      editing,
      isRuleEditing,
      isEmpty,
      blocks,
      draggable,
      draggableID,
      iframeReady,
      previewDevice,
    } = this.props;
    const { iframeTimeout } = this.state;

    const classes = classNames({
      'builder-canvas-dragging':           draggableID !== -1,
      'builder-canvas-dragging-section':   draggableID !== -1 && draggable.type === BLOCK_SECTION,
      'builder-canvas-dragging-component': draggableID !== -1 && draggable.type === BLOCK_COMPONENT,
    });
    const iframeStyles = {
      width: previewDevice === 'desktop' ? '100%' : 375
    };

    return (
      <div className={classes}>
        {html !== '' && (
          <>
            {isRuleEditing ? (
              <>
                <WatchRulesMouse />
                <BlockZones />
              </>
            ) : (
              <>
                <WatchMouse />
                <ToolbarProvider>
                  <Blocks />
                </ToolbarProvider>
              </>
            )}

            <CanvasFrame html={html} style={iframeStyles} />
          </>
        )}
        {(isEmpty && !editing && blocks.length > 0) && (
          <Block
            block={this.getEmptyBlock()}
            hoverID={-1}
            activeID={-1}
            dropZoneID={-1}
            zIndex={1}
            menuRef={{}}
            gridVisible
          />
        )}
        {(!iframeReady && !iframeTimeout) && (
          <Loading />
        )}
      </div>
    );
  };

  /**
   * @returns {*}
   */
  render() {
    const { html, editing, isRuleEditing, isEditingHtml, isExpandedHtml, rulesMode, previewDevice } = this.props;
    const { iframeTimeout } = this.state;

    const expandedHeight = isExpandedHtml ? '0%' : '65%';

    // Handle preview device styles.
    const height = (window.innerHeight - builder.headerHeight);
    const containerStyles = {
     // overflow: isEditingHtml ? 'hidden' : 'auto',
      width:  previewDevice === 'desktop' ? '100%' : 375,
      height: isEditingHtml ? expandedHeight : height,
    };
    if (isRuleEditing && !isEditingHtml && rulesMode !== 'editable') {
      containerStyles.height -= 30;
    }
    if (previewDevice !== 'desktop') {
      containerStyles.minHeight  = 'auto';
      containerStyles.marginLeft = 'auto';
      if (!editing) {
        containerStyles.marginRight = '37.5%';
      }
    }

    const children = (
      <>
        {(html === '' && !iframeTimeout) ? (
          <Loading />
        ) : (
          this.renderBody()
        )}
      </>
    );

    if (isRuleEditing) {
      return (
        <div className="d-flex flex-column flex-grow-1">
          <Container
            ref={this.canvas}
            style={containerStyles}
            onScroll={this.handleScroll}
            editing={editing}
            expandedHeight={expandedHeight}
            isEditingHtml={isEditingHtml}
            isRuleEditing={isRuleEditing && !isEditingHtml && rulesMode !== 'editable'}
            className="builder-canvas"
          >
            {children}
          </Container>
          {isRuleEditing && !isEditingHtml && rulesMode !== 'editable' && (
            <ElementPath />
          )}
          {isEditingHtml && (
            <HtmlEditor />
          )}
        </div>
      );
    }

    return (
      <div className="d-flex flex-grow-1">
        <Container
          ref={this.canvas}
          style={containerStyles}
          onScroll={this.handleScroll}
          editing={editing}
          isEditingHtml={isEditingHtml}
          isRuleEditing={isRuleEditing}
          className="builder-canvas"
        >
          {children}
        </Container>
      </div>
    );
  }
}
