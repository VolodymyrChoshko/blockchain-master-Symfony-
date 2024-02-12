import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { tones } from 'emojiCodes';

const hasTone = [
  '1F44D',
  '1f44f',
];

const Emoji = ({ code, tone, className }) => {
  const modifier = (hasTone.indexOf(code) === -1 || tone === -1) ? 'FE0F' : tones[tone];

  return (
    <span
      role="img"
      aria-label="Check"
      className={classNames('emoji', className)}
      dangerouslySetInnerHTML={{ __html: `&#x${code};&#x${modifier};` }}
    />
  );
};

Emoji.propTypes = {
  code:      PropTypes.string.isRequired,
  tone:      PropTypes.number,
  className: PropTypes.string,
};

Emoji.defaultProps = {
  tone: -1,
};

export default Emoji;
