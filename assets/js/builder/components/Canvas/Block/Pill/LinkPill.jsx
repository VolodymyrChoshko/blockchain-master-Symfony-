import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import router from 'lib/router';
import { useSelector } from 'react-redux';
import Warning from '../Warning';
import Pill from './Pill';

const isBrokenCheck = {};
const isBusy = {};

const LinkPill = ({ block, href, dimensions }) => {
  const [children, setChildren] = useState('&nbsp;');
  const [isBroken, setBroken] = useState(null);
  const tpaEnabled = useSelector(state => state.builder.tpaEnabled);

  /**
   *
   */
  useEffect(() => {
    const val = href !== undefined ? href : block.element.getAttribute('href');
    setChildren(val);

    if (val !== '#' && val !== '' && isBroken === null && isBrokenCheck[val] === undefined && !isBusy[val]) {
      isBusy[val] = true;
      fetch(`${router.generate('build_template_fetch')}?url=${encodeURIComponent(val)}`)
        .then((resp) => resp.json())
        .then((status) => {
          if (status > 299) {
            setBroken(true);
            isBrokenCheck[val] = true;
          } else {
            setBroken(false);
            isBrokenCheck[val] = false;
          }
        }).finally(() => {
          isBusy[val] = false;
        });
    } else if (isBrokenCheck !== undefined) {
      setBroken(isBrokenCheck[val]);
    }
  }, [href, isBroken]);

  let warning = '';
  const warnings = [];
  const then = (new Date().getTime());
  if (children === '' || children === '#') {
    warnings.push('Empty link');
  }
  if (tpaEnabled) {
    if (href && href.indexOf('?') === -1) {
      warnings.push('Missing tracking parameters');
      // eslint-disable-next-line max-len
    } else if (block && block.element && block.element.getAttribute('href') && block.element.getAttribute('href').indexOf('?') === -1) {
      warnings.push('Missing tracking parameters');
    }
  }
  if (block && !block.element.getAttribute('data-be-anchor') && then > 1675451118767) {
    warnings.push('Default link not changed');
  }
  if (isBroken === true) {
    warnings.push('URL appears broken');
  }
  if (warnings.length > 0) {
    // eslint-disable-next-line max-len
    warning = `${warnings.join(', ')[0].toUpperCase()}${warnings.join(', ').substring(1).toLowerCase()}`.replace('Url', 'URL');
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

LinkPill.propTypes = {
  block:      PropTypes.object,
  href:       PropTypes.string,
  dimensions: PropTypes.object.isRequired
};

export default LinkPill;
