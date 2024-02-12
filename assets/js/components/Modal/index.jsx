import React from 'react';
import PropTypes from 'prop-types';
import { Scrollbars } from 'react-custom-scrollbars';
import renderScrollbars from 'utils/scrollbars';
import Mask from '../Mask';
import MaskChild from '../MaskChild';
import Icon from '../Icon';
import { Container, Header, Inner, Body, Footer } from './styles';

export default class Modal extends React.PureComponent {
  static propTypes = {
    sm:         PropTypes.bool,
    md:         PropTypes.bool,
    lg:         PropTypes.bool,
    tabs:       PropTypes.node,
    auto:       PropTypes.bool,
    title:      PropTypes.node,
    open:       PropTypes.bool,
    black:      PropTypes.bool,
    style:      PropTypes.object,
    bodyStyle:  PropTypes.object,
    innerRef:   PropTypes.object,
    footer:     PropTypes.node,
    scrollbars: PropTypes.bool,
    flexStart:  PropTypes.bool,
    className:  PropTypes.string,
    animation:  PropTypes.string,
    children:   PropTypes.node,
    onVisible:  PropTypes.func,
    onHidden:   PropTypes.func
  };

  static defaultProps = {
    sm:         false,
    md:         false,
    lg:         false,
    auto:       false,
    tabs:       '',
    title:      '',
    style:      {},
    bodyStyle:  {},
    open:       false,
    black:      false,
    flexStart:  false,
    scrollbars: true,
    animation:  'slideInDown',
    footer:     '',
    className:  '',
    children:   '',
    innerRef:   null,
    onVisible:  () => {
    },
    onHidden:   () => {
    }
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.scrollbars = React.createRef();
    this.state = {
      open:    props.open,
      visible: false
    };
  }

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { open } = this.props;
    const { open: pOpen } = prevProps;

    if (open && !pOpen) {
      this.setState({ open: true });
    }
  }

  /**
   *
   */
  scroll = (left, top = 0) => {
    if (this.scrollbars.current) {
      this.scrollbars.current.view.scroll({
        top,
        left,
        behavior: 'smooth'
      });
    }
  };

  /**
   * @param e
   */
  handleEscape = (e) => {
    const { onHidden } = this.props;

    if (e.key === 'Escape') {
      e.preventDefault();
      document.removeEventListener('keydown', this.handleEscape);
      onHidden();
    }
  };

  /**
   *
   */
  handleMaskClick = () => {
    this.setState({ open: false });
    document.removeEventListener('keydown', this.handleEscape);
  };

  /**
   * @param {Event} e
   */
  handleMaskVisible = (e) => {
    const { onVisible } = this.props;

    this.setState({ visible: true });
    onVisible(e);
    document.addEventListener('keydown', this.handleEscape);
  };

  /**
   * @param {Event} e
   */
  handleClick = (e) => {
    e.stopPropagation();
  };

  /**
   * @returns {*}
   */
  render() {
    const {
      sm,
      md,
      lg,
      auto,
      style,
      title,
      tabs,
      black,
      footer,
      innerRef,
      bodyStyle,
      flexStart,
      animation,
      onHidden,
      scrollbars,
      className,
      children
    } = this.props;
    const { open, visible } = this.state;

    return (
      <Mask
        open={open}
        black={black}
        onHidden={onHidden}
        flexStart={flexStart}
        onVisible={this.handleMaskVisible}
        onClick={this.handleMaskClick}
      >
        <MaskChild animation={animation}>
          <Container
            ref={innerRef}
            style={style}
            className={className}
            onClick={this.handleClick}
            onMouseDown={this.handleClick}
            sm={sm}
            md={md}
            lg={lg}
            tabIndex={-1}
            visible={visible}
            autoHeight={auto && scrollbars}
            flexStart={flexStart}
            narrow={!lg && !sm && !md}
          >
            <Header withTabs={tabs !== ''} className={typeof title === 'string' ? '' : 'pt-2 pb-2'}>
              <span>{title}</span>
              <Icon
                name="be-symbol-delete"
                title="Close"
                onClick={this.handleMaskClick}
                className={typeof title === 'string' ? '' : 'mt-2'}
              />
            </Header>
            {tabs}
            <Inner>
              {auto ? (
                <Body style={bodyStyle}>
                  {children}
                </Body>
              ) : (
                <Body style={bodyStyle}>
                  <Scrollbars
                    ref={this.scrollbars}
                    renderTrackHorizontal={renderScrollbars.renderTrackHorizontal}
                    renderThumbHorizontal={renderScrollbars.renderThumbHorizontal}
                  >
                    {children}
                  </Scrollbars>
                </Body>
              )}
            </Inner>
            {footer && (
              <Footer>
                {footer}
              </Footer>
            )}
          </Container>
        </MaskChild>
      </Mask>
    );
  }
}
