import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import browser from 'utils/browser';
import { anchorStyleIndexDefault } from 'builder/reducers/editableReducer';
import TrackingParams from './TrackingParams';
import Menu from './Menu';

const mapStateToProps = state => ({
  tpaEnabled:         state.builder.tpaEnabled,
  epaEnabled:         state.builder.epaEnabled,
  templateLinkParams: state.builder.templateLinkParams,
  emailLinkParams:    state.builder.emailLinkParams,
  tmpAliasEnabled:    state.builder.tmpAliasEnabled,
  emaAliasEnabled:    state.builder.emaAliasEnabled
});

@connect(
  mapStateToProps
)
export default class LinkPrompt extends React.PureComponent {
  static propTypes = {
    open:               PropTypes.bool,
    href:               PropTypes.string,
    alt:                PropTypes.string,
    iframe:             PropTypes.object,
    anchorStyles:       PropTypes.array,
    anchorStyleIndex:   PropTypes.number.isRequired,
    tpaEnabled:         PropTypes.bool.isRequired,
    epaEnabled:         PropTypes.bool.isRequired,
    templateLinkParams: PropTypes.array.isRequired,
    emailLinkParams:    PropTypes.object.isRequired,
    tmpAliasEnabled:    PropTypes.bool.isRequired,
    emaAliasEnabled:    PropTypes.bool.isRequired,
    onUpdate:           PropTypes.func,
    onOptClick:         PropTypes.func
  };

  static defaultProps = {
    open:         false,
    href:         '',
    alt:          '',
    anchorStyles: [],
    onUpdate:     () => {},
    onOptClick:   () => {}
  };

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.origHref = props.href;
    this.href     = React.createRef();
    this.alt      = React.createRef();
    this.state    = {
      url:        props.href,
      alias:      props.alt,
      isError:    false,
      errorMsg:   '',
      linkParams: {}
      // linkParams: Object.assign({}, props.emailLinkParams)
    };
  }

  /**
   *
   */
  componentDidMount() {
    const { iframe } = this.props;

    const doc = browser.iFrameDocument(iframe);
    doc.addEventListener('click', this.handleDocClick, true);
    doc.addEventListener('mousedown', this.handleDocMouseDown, true);
  }

  /**
   *
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { open, href, alt, tpaEnabled, epaEnabled, templateLinkParams, emailLinkParams } = this.props;
    const { open: pOpen } = prevProps;

    if (open && !pOpen) {
      if (!tpaEnabled || !epaEnabled) {
        this.setState({ url: href, alias: alt });
      } else {
        this.setState({
          url:   TrackingParams.getSanitizedHref(href, templateLinkParams),
          alias: alt
        });

        const searchParams = TrackingParams.getSearchParams(href);
        if (searchParams) {
          const linkParams = Object.assign({}, emailLinkParams);
          Object.keys(searchParams).forEach((key) => {
            if (templateLinkParams.indexOf(key) !== -1) {
              linkParams[key] = searchParams[key];
            }
          });

          this.setState({ linkParams });
        } else if (!href) {
          const linkParams = Object.assign({}, emailLinkParams);
          this.setState({ linkParams });
        }
      }

      setTimeout(() => {
        this.origHref = href;
        this.href.current.focus();
        this.href.current.select();
      }, 100);
    }
  }

  /**
   *
   */
  componentWillUnmount() {
    const { iframe } = this.props;

    const doc = browser.iFrameDocument(iframe);
    doc.removeEventListener('mousedown', this.handleMouseDown, true);
    doc.removeEventListener('click', this.handleDocClick, true);
    setTimeout(() => {
      iframe.contentWindow.getSelection().removeAllRanges();
    }, 100);
  }

  /**
   * @param {Event} e
   */
  handleDocMouseDown = (e) => {
    const { onUpdate } = this.props;

    if (this.props.open) { // eslint-disable-line
      e.preventDefault();
      onUpdate('src', false, false);
    }
  };

  /**
   *
   */
  handleDocClick = () => {
    const { open, href, onUpdate } = this.props;

    if (open) { // eslint-disable-line
      if (this.origHref === href && this.origHref !== '') {
        onUpdate('src', false, false);
      } else {
        onUpdate('src', null, null);
      }
    }
  };

  /**
   *
   */
  handleClick = () => {
    const { tpaEnabled, epaEnabled, onUpdate } = this.props;
    const { url, alias, linkParams } = this.state;

    if (!url) {
      this.setState({ isError: true, errorMsg: 'Missing URL' });
      return;
    }
    this.setState({ isError: false, errorMsg: '' });

    if (!tpaEnabled || !epaEnabled) {
      onUpdate('src', url, alias);
    } else {
      try {
        const src = TrackingParams.getURLWithParams(url, linkParams);
        onUpdate('src', src, alias);
      } catch (error) {
        this.setState({ isError: true, errorMsg: error.message });
      }
    }
  };

  /**
   *
   */
  handleUnlinkClick = () => {
    const { iframe, onUpdate } = this.props;

    this.setState({ isError: false, errorMsg: '' });
    onUpdate('src', null, null);
    iframe.contentWindow.getSelection().removeAllRanges();
  };

  /**
   * @param {KeyboardEvent} e
   */
  handleKeyDown = (e) => {
    if (e.keyCode === 13) {
      this.handleClick();
    }
  };

  /**
   * @returns {*}
   */
  render() {
    const {
      open,
      href,
      anchorStyles,
      anchorStyleIndex,
      onOptClick,
      tmpAliasEnabled,
      emaAliasEnabled
    } = this.props;
    const { url, alias, linkParams, errorMsg, isError } = this.state;

    return (
      <Menu position="bottom" open={open}>
        {provided => (
          <div
            ref={provided.menuRef}
            className={classNames('builder-menu builder-menu-prompt builder-no-canvas-click', { error: isError })}
          >
            {errorMsg && (
              <div className="builder-menu-prompt-error">
                {errorMsg}
              </div>
            )}
            <input
              ref={this.href}
              value={url}
              name="src"
              placeholder="URL"
              className="form-control form-control-url mb-2"
              onChange={e => this.setState({ url: e.target.value })}
              onKeyDown={this.handleKeyDown}
            />
            {(tmpAliasEnabled && emaAliasEnabled) && (
              <input
                ref={this.alt}
                value={alias}
                name="alt"
                placeholder="Alias"
                className="form-control mb-2"
                onKeyDown={this.handleKeyDown}
                onChange={e => this.setState({ alias: e.target.value })}
              />
            )}
            <TrackingParams
              href={url}
              params={linkParams}
              onChange={(e, lp) => this.setState({ linkParams: lp })}
            />
            <div className="builder-menu-buttons">
              <button className="btn mr-2" onClick={this.handleClick}>
                Link
              </button>
              <button className="btn" onClick={this.handleUnlinkClick}>
                Unlink
              </button>
              {anchorStyles.length > 0 && (
                <>
                  <label>Styles:</label>
                  <button
                    className={`btn ${anchorStyleIndex === anchorStyleIndexDefault ? 'btn-active' : ''}`}
                    onClick={e => onOptClick(e, anchorStyleIndexDefault, href)}
                  >
                    1
                  </button>
                  {anchorStyles.map((as, i) => {
                    let className = 'btn';
                    if (i === anchorStyleIndex) {
                      className += ' btn-active';
                    }
                    return (
                      <button key={i} className={className} onClick={e => onOptClick(e, i, href)}>
                        {i + 2}
                      </button>
                    );
                  })}
                </>
              )}
            </div>
          </div>
        )}
      </Menu>
    );
  }
}
