import React, { useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import useMe from 'dashboard/hooks/useMe';
import router from 'lib/router';
import Avatar from 'dashboard/components/Avatar';
import PopupMenu from 'components/PopupMenu';
import { Container, MenuInner, AvatarWrap } from './styles';

const UserMenu = ({ useRealLinks }) => {
  const me = useMe();
  const [popupElement, setPopupElement] = useState(null);
  const avatarWrapRef = useRef();

  /**
   *
   */
  const handleAvatarClick = () => {
    setPopupElement(avatarWrapRef.current);
  };

  if (!me) {
    return null;
  }

  let avatar = '';
  if (me.avatar) {
    avatar = me.avatar.replace('-60x60', '');
  }

  return (
    <Container>
      <AvatarWrap ref={avatarWrapRef}>
        <Avatar
          user={{ ...me, avatar }}
          className="mr-0"
          onClick={handleAvatarClick}
          md
        />

        <PopupMenu
          element={popupElement}
          name="user-menu"
          position="bottom"
          offsetX={5}
          offsetY={20}
          onClose={() => setPopupElement(null)}
        >
          <MenuInner
            onClick={() => setPopupElement(null)}
          >
            {useRealLinks ? (
              <a className="menu-item" href={router.generate('profile_index')}>
                My Profile
              </a>
            ) : (
              <Link className="menu-item" to={router.generate('profile_index')}>
                My Profile
              </Link>
            )}

            {me.parentID ? (
              <a className="menu-item" href={router.generate('logout')}>
                Log Out
              </a>
            ) : (
              <a className="menu-item" href={router.generate('logout', {}, 'absolute')}>
                Log Out
              </a>
            )}

            {me.isSiteAdmin && (
              <a className="menu-item" href={router.generate('admin_dashboard_index')}>
                Admin Tools
              </a>
            )}
          </MenuInner>
        </PopupMenu>
      </AvatarWrap>
    </Container>
  );
};

UserMenu.propTypes = {
  useRealLinks:  PropTypes.bool,
};

export default UserMenu;
