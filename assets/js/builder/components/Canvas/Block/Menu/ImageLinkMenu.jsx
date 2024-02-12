import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { builderActions } from 'builder/actions';
import TrackingParams from './TrackingParams';
import Menu from './Menu';

const ImageLinkMenu = ({
  block,
  open,
  value,
  alt,
  tpaEnabled,
  epaEnabled,
  templateLinkParams,
  tmpAliasEnabled,
  emaAliasEnabled,
  emailLinkParams,
  onClose,
  builderUpdateBlock
}) => {
  const [link, setLink] = useState(value);
  const [altValue, setAltValue] = useState(alt);
  const [linkParams, setLinkParams] = useState(emailLinkParams);
  const [isError, setError] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const href = useRef(null);

  useEffect(() => {
    if (!tpaEnabled || !epaEnabled) {
      setLink(value);
    } else {
      setLink(TrackingParams.getSanitizedHref(value, templateLinkParams));

      const searchParams = TrackingParams.getSearchParams(value);
      if (searchParams) {
        const lp = Object.assign({}, emailLinkParams);
        Object.keys(searchParams).forEach((key) => {
          if (templateLinkParams.indexOf(key) !== -1) {
            lp[key] = searchParams[key];
          }
        });

        setLinkParams(lp);
      }
    }
  }, [value, emailLinkParams, templateLinkParams]);

  /**
   *
   */
  const handleClick = () => {
    const { element } = block;

    setError(false);
    setErrorMsg('');

    let src;
    if (!tpaEnabled || !epaEnabled) {
      src = link;
    } else {
      try {
        src = TrackingParams.getURLWithParams(link, linkParams);
      } catch (error) {
        setError(true);
        setErrorMsg(error.message);
        return;
      }
    }

    if (element.parentNode.tagName !== 'A') {
      builderUpdateBlock(block.id, 'href', src, altValue);
    } else if (link !== element.parentNode.href || altValue !== element.parentNode.getAttribute('alt')) {
      element.parentNode.href = src;
      element.parentNode.setAttribute('alias', altValue);
      builderUpdateBlock(block.id, 'element', element);
    }

    onClose();
  };

  /**
   *
   */
  const handleUnlinkClick = () => {
    const { element } = block;

    if (element.parentNode.tagName === 'A') {
      builderUpdateBlock(block.id, 'href', '');
    } else if (link !== element.parentNode.href) {
      element.parentNode.href = '';
      builderUpdateBlock(block.id, 'element', element);
    }

    onClose();
  };

  /**
   * @param e
   */
  const handleChange = (e) => {
    if (isError && href.current.value) {
      setError(false);
      setErrorMsg('');
    }
    setLink(e.target.value);
  };

  /**
   * @param e
   */
  const handleAltChange = (e) => {
    setAltValue(e.target.value);
  };

  /**
   * @param e
   */
  const handleKeyDown = (e) => {
    if (e.keyCode === 13) {
      handleClick();
    }
  };

  return (
    <Menu position="top" nextPositions={['middle']} open={open}>
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
            ref={href}
            value={link}
            name="src"
            placeholder="URL"
            className="form-control form-control-url mr-2 mb-2"
            onChange={handleChange}
            onKeyDown={handleKeyDown}
          />
          {(tmpAliasEnabled && emaAliasEnabled) && (
            <input
              value={altValue}
              name="alt"
              placeholder="Alias"
              className="form-control mr-2 mb-2"
              onChange={handleAltChange}
              onKeyDown={handleKeyDown}
            />
          )}
          <TrackingParams
            href={link}
            params={linkParams}
            onChange={(e, lp) => setLinkParams(lp)}
          />
          <div className="builder-menu-buttons">
            <button className="btn mr-2" onClick={handleClick}>
              Link
            </button>
            <button className="btn" onClick={handleUnlinkClick}>
              Unlink
            </button>
          </div>
        </div>
      )}
    </Menu>
  );
};

ImageLinkMenu.propTypes = {
  open:               PropTypes.bool.isRequired,
  value:              PropTypes.string.isRequired,
  alt:                PropTypes.string.isRequired,
  tpaEnabled:         PropTypes.bool.isRequired,
  epaEnabled:         PropTypes.bool.isRequired,
  tmpAliasEnabled:    PropTypes.bool.isRequired,
  emaAliasEnabled:    PropTypes.bool.isRequired,
  onClose:            PropTypes.func.isRequired,
  builderUpdateBlock: PropTypes.func.isRequired,
  templateLinkParams: PropTypes.array.isRequired,
  emailLinkParams:    PropTypes.object.isRequired
};

const mapStateToProps = state => ({
  tpaEnabled:         state.builder.tpaEnabled,
  epaEnabled:         state.builder.epaEnabled,
  tmpAliasEnabled:    state.builder.tmpAliasEnabled,
  emaAliasEnabled:    state.builder.emaAliasEnabled,
  templateLinkParams: state.builder.templateLinkParams,
  emailLinkParams:    state.builder.emailLinkParams
});

export default connect(
  mapStateToProps,
  mapDispatchToProps(builderActions)
)(ImageLinkMenu);
