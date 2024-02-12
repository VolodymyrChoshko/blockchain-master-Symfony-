import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Input } from 'components/forms';

const TrackingParams = ({
  href,
  params,
  templateLinkParams,
  tpaEnabled,
  epaEnabled,
  onChange
}) => {
  if (templateLinkParams.length === 0 || !tpaEnabled || !epaEnabled) {
    return null;
  }

  const [linkParams, setLinkParams] = useState(params);

  useEffect(() => {
    setLinkParams(params);
  }, [params, href]);

  /**
   * @param {Event} e
   * @param {string} tpa
   */
  const handleLinkParamChange = (e, tpa) => {
    const newLinkParams = Object.assign({}, linkParams);
    newLinkParams[tpa]  = e.target.value;
    setLinkParams(newLinkParams);
    onChange(e, newLinkParams);
  };

  let firstSeparator = '?';
  if (href.indexOf('?') !== -1) {
    firstSeparator = '&';
  }

  return (
    <table className="w-100">
      <tbody>
        {templateLinkParams.map((tpa, i) => (
          <tr key={tpa}>
            <td className="pr-2 pb-1 pt-1" style={{ verticalAlign: 'middle' }}>
              <label htmlFor={`email-settings-input-tpa-${tpa}`}>
                {i === 0 ? firstSeparator : '&'}
                {tpa}=
              </label>
            </td>
            <td className="pb-1 pt-1" style={{ verticalAlign: 'middle' }}>
              <Input
                name={`tpa_${tpa}`}
                value={linkParams[tpa] || ''}
                id={`email-settings-input-tpa-${tpa}`}
                onChange={e => handleLinkParamChange(e, tpa)}
              />
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};

TrackingParams.propTypes = {
  href:               PropTypes.string.isRequired,
  params:             PropTypes.object.isRequired,
  templateLinkParams: PropTypes.array.isRequired,
  tpaEnabled:         PropTypes.bool.isRequired,
  epaEnabled:         PropTypes.bool.isRequired,
  onChange:           PropTypes.func.isRequired
};

// See: https://stackoverflow.com/a/26987741/13175931
// eslint-disable-next-line max-len
const domainRegex = new RegExp('^(((?!-))(xn--|_{1,1})?[a-z0-9-]{0,61}[a-z0-9]{1,1}\\.)*(xn--)?([a-z0-9][a-z0-9\\-]{0,60}|[a-z0-9-]{1,30}\\.[a-z]{2,})$');

/**
 * @param {string} href
 * @param {array} templateLinkParams
 * @returns {string}
 */
TrackingParams.getSanitizedHref = (href, templateLinkParams) => {
  if (!href) {
    return '';
  }

  if (href[0] === '#' || href[0] === '{' || href.indexOf('mailto:') === 0) {
    return href;
  }

  try {
    const u = new URL(href);
    templateLinkParams.forEach((param) => {
      u.searchParams.delete(param);
    });
    return u.toString();
    // eslint-disable-next-line no-empty
  } catch (error) {
    console.error(error);
  }

  return href;
};

/**
 * @param {string} href
 * @returns {{}|null}
 */
TrackingParams.getSearchParams = (href) => {
  try {
    const url    = new URL(href);
    let found    = false;
    const params = {};
    url.searchParams.forEach((value, key) => {
      params[key] = value;
      found = true;
    });

    if (!found) {
      return null;
    }

    return params;
  } catch (error) {
    return null;
  }
};

/**
 * @param {string} href
 * @param {*} linkParams
 * @returns {string}
 */
TrackingParams.getURLWithParams = (href, linkParams) => {
  if (href[0] === '#' || href[0] === '{' || href.indexOf('mailto:') === 0) {
    return href;
  }

  const parts = href.split('://', 2);
  if (['http', 'https', 'ftp', 'mailto'].indexOf(parts[0]) === -1) {
    throw new Error('URL needs to include http(s)');
  }

  try {
    const url = new URL(href);
    Object.keys(linkParams).forEach((key) => {
      if (!linkParams[key]) {
        url.searchParams.delete(key);
      } else {
        url.searchParams.set(key, linkParams[key]);
      }
    });

    return url.toString();
  } catch (error) {
    console.error(error);
    return '';
  }
};

const mapStateToProps = state => ({
  templateLinkParams: state.builder.templateLinkParams,
  tpaEnabled:         state.builder.tpaEnabled,
  epaEnabled:         state.builder.epaEnabled
});

export default connect(mapStateToProps)(TrackingParams);
