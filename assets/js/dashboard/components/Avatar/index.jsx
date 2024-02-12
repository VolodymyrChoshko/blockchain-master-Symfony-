import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Container } from './styles';

const Avatar = ({ user, lg, md, sm, src, className, onClick }) => {
  if (src === '' && !user.avatar) {
    const n = name || user.name;
    const parts = n.split(' ').filter(v => v).map(word => word[0].toUpperCase());

    return (
      <Container lg={lg} md={md} sm={sm} className={classNames('avatar', className)} onClick={onClick}>
        {parts.join('')}
      </Container>
    );
  }

  let source = user.avatar;
  if (src) {
    source = src;
  } else if (lg && user.avatar240) {
    source = user.avatar240;
  } else if (md && user.avatar120) {
    source = user.avatar120;
  }

  return (
    <Container className={classNames('avatar', className)} lg={lg} md={md} sm={sm} onClick={onClick}>
      <img src={source} alt="Avatar" />
    </Container>
  );
};

Avatar.propTypes = {
  user:      PropTypes.object.isRequired,
  lg:        PropTypes.bool,
  md:        PropTypes.bool,
  sm:        PropTypes.bool,
  src:       PropTypes.string,
  name:      PropTypes.string,
  className: PropTypes.string,
  onClick:   PropTypes.func
};

Avatar.defaultProps = {
  lg:        false,
  md:        false,
  sm:        false,
  src:       '',
  name:      '',
  className: ''
};

export default Avatar;
