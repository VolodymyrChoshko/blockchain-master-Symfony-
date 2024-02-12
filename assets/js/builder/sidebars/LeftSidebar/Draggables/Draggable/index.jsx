import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import browser from 'utils/browser';
import { builderActions, uiActions } from 'builder/actions';
import Loading from 'components/Loading';
import { Icon } from 'components';
import { Container, Generating, GeneratingLabel, AmpScript } from './styles';

const mapStateToProps = state => ({
  mode:      state.builder.mode,
  iframe:    state.builder.iframe,
  editing:   state.builder.editing,
  upgrading: state.builder.upgrading,
  scrollTop: state.builder.scrollTop
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions, uiActions)
)
export default class Draggable extends React.PureComponent {
  static propTypes = {
    mode:             PropTypes.string.isRequired,
    upgrading:        PropTypes.array.isRequired,
    iframe:           PropTypes.object,
    editing:          PropTypes.bool.isRequired,
    scrollTop:        PropTypes.number.isRequired,
    draggable:        PropTypes.object.isRequired,
    builderDragStart: PropTypes.func.isRequired,
    builderDragEnd:   PropTypes.func.isRequired,
    onPinEdit:        PropTypes.func.isRequired,
  };

  static defaultProps = {};

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.clone     = null;
    this.rect      = null;
    this.lastX     = 0;
    this.lastY     = 0;
    this.dragX     = 0;
    this.dragY     = 0;
    this.draggable = React.createRef();
    this.state = {
      isDragging: false
    };
  }

  /**
   *
   */
  componentDidMount() {
    this.canvas     = document.querySelector('.builder-canvas');
    this.canvasRect = this.canvas.getBoundingClientRect();
  }

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { editing } = this.props;

    if (editing && !prevProps.editing) {
      // Wait for the sidebar to slide out or the rect will be wrong.
      setTimeout(() => {
        this.canvasRect = this.canvas.getBoundingClientRect();
      }, 100);
    }
  }

  /**
   * @returns {string}
   */
  getScreenshotUrl = () => {
    const { draggable } = this.props;

    if (draggable.thumb) {
      return draggable.thumb;
    }
    if (draggable.thumbnail) {
      return draggable.thumbnail;
    }
    if (draggable.mobile) {
      return draggable.screenshotMobile;
    }
    return draggable.screenshotDesktop;
  };

  /**
   * @param {Event} e
   */
  handleWindowMouseMove = (e) => {
    const { pageX, pageY } = e;

    this.handleMouseMove(pageX, pageY);
  };

  /**
   * @param {Event} e
   */
  handleFrameMouseMove = (e) => {
    const { scrollTop } = this.props;
    const { pageX, pageY } = e;

    const offsetPageX = Math.floor(pageX + this.canvasRect.left);
    const offsetPageY = Math.floor(pageY + this.canvasRect.top - scrollTop);
    this.handleMouseMove(offsetPageX, offsetPageY);
  };

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  handleMouseMove = (pageX, pageY) => {
    const deltaX = this.lastX - pageX;
    const deltaY = this.lastY - pageY;
    this.lastX   = pageX;
    this.lastY   = pageY;
    this.dragX   -= deltaX;
    this.dragY   -= deltaY;

    this.clone.style.transform = `translate3d(${this.dragX}px, ${this.dragY}px, 0)`;
  };

  /**
   * @param {Event} e
   */
  handleWindowMouseUp = (e) => {
    const { pageX, pageY } = e;

    this.handleMouseUp(pageX, pageY);
  };

  /**
   * @param {Event} e
   */
  handleFrameMouseUp = (e) => {
    const { pageX, pageY } = e;

    const offsetPageX = Math.floor(pageX + this.canvasRect.left);
    const offsetPageY = Math.floor(pageY + this.canvasRect.top);
    this.handleMouseUp(offsetPageX, offsetPageY);
  };

  /**
   * @param {number} pageX
   * @param {number} pageY
   */
  handleMouseUp = (pageX, pageY) => {
    const { iframe, builderDragEnd } = this.props;

    this.lastX = 0;
    this.lastY = 0;
    this.dragX = 0;
    this.dragY = 0;

    document.body.style.cursor = 'default';
    if (this.clone) {
      this.clone.remove();
    }
    window.removeEventListener('mousemove', this.handleWindowMouseMove, false);
    window.removeEventListener('mouseup', this.handleWindowMouseUp, false);

    const doc = browser.iFrameDocument(iframe);
    doc.removeEventListener('mousemove', this.handleFrameMouseMove, false);
    doc.removeEventListener('mouseup', this.handleFrameMouseUp, false);

    this.setState({ isDragging: false });
    builderDragEnd(pageX, pageY);
  };

  /**
   * @param {Event} e
   */
  handleMouseDown = (e) => {
    const { iframe, draggable, builderDragStart } = this.props;
    const { current } = this.draggable;

    this.rect               = current.getBoundingClientRect();
    this.clone              = current.cloneNode(true);
    this.clone.style.left   = `${this.rect.x}px`;
    this.clone.style.top    = `${this.rect.y}px`;
    this.clone.style.width  = `${current.offsetWidth}px`;
    this.clone.style.height = `${current.offsetHeight}px`;
    this.clone.classList.add('builder-sidebar-draggable-clone');

    // Hide library edit button and name.
    const hide = this.clone.querySelector('.dragging-hide');
    if (hide) {
      let inner = current.querySelector('img');
      if (!inner) {
        inner = current.querySelector('.builder-sidebar-draggable-library-generating');
        const loading = this.clone.querySelector('.fancybox-loading');
        if (loading) {
          loading.remove();
        }
      }
      this.clone.style.height = `${inner.offsetHeight}px`;
      hide.remove();
    }

    this.lastX = e.pageX;
    this.lastY = e.pageY;

    document.body.style.cursor = 'move';
    document.querySelector('.builder').appendChild(this.clone);
    setTimeout(() => {
      this.clone.classList.add('visible');
    }, 50);

    const title = this.clone.querySelector('.layout-title');
    if (title) {
      this.clone.style.height = `${current.offsetHeight - title.offsetHeight - 10}px`;
      title.remove();
    } else {
      this.clone.style.height = `${current.offsetHeight - 10}px`;
    }

    window.addEventListener('mousemove', this.handleWindowMouseMove, false);
    window.addEventListener('mouseup', this.handleWindowMouseUp, false);

    const doc = browser.iFrameDocument(iframe);
    doc.addEventListener('mousemove', this.handleFrameMouseMove, false);
    doc.addEventListener('mouseup', this.handleFrameMouseUp, false);

    this.setState({ isDragging: true });
    builderDragStart(draggable, this.clone);
  };

  /**
   * @param {Event} e
   */
  handleLibraryEdit = (e) => {
    const { draggable, onPinEdit } = this.props;

    onPinEdit(e, draggable);
  }

  /**
   * @returns {*}
   */
  render() {
    const { mode, upgrading, draggable } = this.props;
    const { isDragging } = this.state;

    const classes = classNames({
      'builder-sidebar-draggable-layout': draggable.isLibrary,
      'builder-sidebar-draggable-script': draggable.capabilities !== undefined,
    });

    const showUpgradeButton = mode === 'template' || (mode === 'email' && upgrading.length > 0);

    let child = '';
    if (draggable.capabilities !== undefined) {
      child = (
        <AmpScript>
          <span>{'{'}</span>
          AMPscript
          <span>{'}'}</span>
        </AmpScript>
      );
    } else if (draggable.isLibrary) {
      if (draggable.screenshotDesktop === '') {
        child = (
          <Generating className="builder-sidebar-draggable-library-generating">
            <GeneratingLabel>
              Generating Thumbnail
            </GeneratingLabel>
            <Loading fixed={false} />
          </Generating>
        );
      } else {
        child = (
          <img src={this.getScreenshotUrl()} alt="Screenshot" />
        );
      }
      child = (
        <div className="position-relative">
          {child}
          <div className="d-flex align-items-center dragging-hide" style={{ marginTop: -3 }}>
            <div className="builder-sidebar-draggable-layout-edit">
              {(showUpgradeButton && !draggable.isUpgradable) && (
                <span title="This pin can not be updated and needs to be resaved." className="mr-2">
                  <Icon
                    name="be-symbol-caution"
                  />
                </span>
              )}
              <Icon
                name="be-symbol-edit"
                title="Edit"
                onClick={this.handleLibraryEdit}
                className="pointer"
              />
            </div>
            <p className="layout-title mb-0 text-truncate" style={{ maxWidth: 250 }}>
              {draggable.name}
            </p>
          </div>
        </div>
      );
    } else {
      child = (
        <div className="position-relative">
          <img src={this.getScreenshotUrl()} alt="Screenshot" />
          {draggable.title && (
            <p className="layout-title mb-0">
              {draggable.title}
            </p>
          )}
        </div>
      );
    }

    return (
      <Container
        ref={this.draggable}
        draggable="false"
        dragging={isDragging}
        className={classes}
        onMouseDown={this.handleMouseDown}
        onMouseUp={this.handleMouseUp}
        onDragStart={e => e.preventDefault()}
        data-draggable-id={draggable.id}
      >
        {child}
      </Container>
    );
  }
}
