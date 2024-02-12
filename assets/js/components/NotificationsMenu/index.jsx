import React, { useState, useRef } from 'react';
import { useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { Scrollbars } from 'react-custom-scrollbars';
import renderScrollbars from 'utils/scrollbars';
import { useUsersActions } from 'dashboard/actions/usersActions';
import useMe from 'dashboard/hooks/useMe';
import Icon from 'components/Icon';
import PopupMenu from 'components/PopupMenu';
import Notification from './Notification';
import { Container, Notifications, Header, Empty, Pill, Dot } from './styles';

const NotificationsMenu = () => {
  const me = useMe();
  const userActions = useUsersActions();
  const notifications = useSelector(state => state.users.notifications);
  const [popupElement, setPopupElement] = useState(null);
  const containerRef = useRef();

  let unreadCount = 0;
  for (let i = 0; i < notifications.length; i++) {
    if (notifications[i].status === 'unread') {
      unreadCount += 1;
    }
  }

  /**
   *
   */
  const handleClickOut = () => {
    setPopupElement(null);
  };

  return (
    <Container ref={containerRef}>
      <Icon
        name="be-symbol-inbox"
        onClick={() => setPopupElement(containerRef.current)}
      />
      {(me.isShowingCount && unreadCount > 0) && (
        <Pill onClick={() => setPopupElement(containerRef.current)}>
          {unreadCount < 100 ? unreadCount : '99+'}
        </Pill>
      )}
      {(!me.isShowingCount && unreadCount > 0) && (
        <Dot onClick={() => setPopupElement(containerRef.current)} />
      )}
      <PopupMenu
        name="notifications-menu"
        unClickable="notification"
        position="bottom"
        offsetX={5}
        offsetY={20}
        element={popupElement}
        onClose={() => setPopupElement(null)}
        className="px-0"
      >
        <Notifications>
          <Header>
            <button
              onClick={() => {
                handleClickOut();
                userActions.setNotificationsStatus('read');
              }}
            >
              Mark all read
            </button>
            <Link to="/profile#notifications" className="ml-auto" onClick={handleClickOut}>
              Notification settings
            </Link>
          </Header>
          {notifications.length === 0 && (
            <Empty>
              Your notifications are empty
            </Empty>
          )}
          <Scrollbars
            autoHide
            autoHeight
            autoHeightMax="60vh"
            renderTrackHorizontal={renderScrollbars.renderTrackHorizontal}
            renderThumbHorizontal={renderScrollbars.renderThumbHorizontal}
          >
            {notifications.map((n) => (
              <Notification key={n.id} notification={n} onClick={() => setPopupElement(null)} />
            ))}
          </Scrollbars>
        </Notifications>
      </PopupMenu>
    </Container>
  );
};

export default NotificationsMenu;
