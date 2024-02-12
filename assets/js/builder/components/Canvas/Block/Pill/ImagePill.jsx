import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { connect, useSelector } from 'react-redux';
import { cssExtractURL } from 'utils/browser';
import Warning from '../Warning';
import Pill from './Pill';

const ImagePill = ({ block, dimensions, imageDims }) => {
  const [children, setChildren] = useState('');
  const tpaEnabled = useSelector(state => state.builder.tpaEnabled);
  const { element } = block;

  /**
   *
   */
  useEffect(() => {
    let message = '';
    if (element.tagName === 'IMG') {
      message = `${element.naturalWidth}x${element.naturalHeight}`;
    } else if (element.getAttribute('background')) {
      const src = element.getAttribute('background');
      if (src && imageDims[src]) {
        const { width, height } = imageDims[src];
        message = `${width}x${height}`;
      }
    } else if (element.style.background) {
      const src = cssExtractURL(element.style.background);
      if (src && imageDims[src]) {
        const { width, height } = imageDims[src];
        message = `${width}x${height}`;
      }
    } else if (element.style.backgroundImage) {
      const src = cssExtractURL(element.style.backgroundImage);
      if (src && imageDims[src]) {
        const { width, height } = imageDims[src];
        message = `${width}x${height}`;
      }
    }

    if (element.parentNode && element.parentNode.tagName === 'A') {
      message = `${message}|${element.parentNode.getAttribute('href')}`;
    }
    setChildren(message);
  }, []);

  if (!children) {
    return null;
  }

  let warning = '';
  const warnings = [];
  const then = (new Date().getTime());
  if (element.tagName === 'IMG' && String(element.getAttribute('alt') || '') === '') {
    warnings.push('Doesn\'t have alt text');
  }
  if (element.tagName === 'IMG' && !element.getAttribute('data-be-custom-src') && then > 1675451118767) {
    warnings.push('Default image not changed');
  }
  if (element.tagName === 'A' && !element.getAttribute('data-be-anchor') && then > 1675451118767) {
    warnings.push('Default link not changed');
  }
  if (
    element.parentNode
    && element.parentNode.tagName === 'A'
    && (element.parentNode.getAttribute('href') === '' || element.parentNode.getAttribute('href') === '#')
  ) {
    warnings.push('Empty link');
  }
  if (
    element.parentNode
    && tpaEnabled
    && element.parentNode.tagName === 'A'
    && element.parentNode.getAttribute('href').indexOf('?') === -1
  ) {
    warnings.push('Missing tracking parameters');
  }
  if (warnings.length > 0) {
    warning = `${warnings.join(', ')[0].toUpperCase()}${warnings.join(', ').substring(1).toLowerCase()}`;
  }

  return (
    <>
      <Pill dimensions={dimensions} className="builder-pill-block">
        <span>{children}</span>
      </Pill>
      {warnings.length > 0 && (
        <Pill dimensions={{ ...dimensions, top: dimensions.top + 20 }} className="builder-pill-block">
          <Warning>
            {warning}
          </Warning>
        </Pill>
      )}
    </>
  );
};

ImagePill.propTypes = {
  block:      PropTypes.object.isRequired,
  imageDims:  PropTypes.object.isRequired,
  dimensions: PropTypes.object.isRequired
};

const mapStateToProps = state => ({
  imageDims: state.builder.imageDims,
});

export default connect(
  mapStateToProps
)(ImagePill);
