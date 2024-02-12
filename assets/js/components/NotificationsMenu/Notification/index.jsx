import { Emojis, EmojiWrap } from 'builder/sidebars/ActivitySidebar/CommentItem/Comment/styles';
import React, { useEffect, useState, useRef, useMemo } from 'react';
import PropTypes from 'prop-types';
import router from 'lib/router';
import { useUsersActions } from 'dashboard/actions/usersActions';
import Icon from 'components/Icon';
import Emoji from 'components/Emoji';
import Avatar from 'dashboard/components/Avatar';
import PersonModal from 'components/PersonModal';
import { formatDate, formatTime } from 'utils/dates';
import { Container, RightSide, LeftSide, Title, Body, When, Who } from './styles';

const Notification = ({ /** @type Notification */ notification, onClick }) => {
  const userActions = useUsersActions();
  const [modalUser, setModalUser] = useState(null);
  const { action, mention, dateCreated } = notification;
  /** @var {{ current: HTMLElement }} */
  const bodyRef = useRef();
  const when = new Date(dateCreated * 1000);

  /**
   * @param {MouseEvent} e
   */
  const handleActivityAvatarClick = (e) => {
    e.stopPropagation();
    e.preventDefault();
    let userInfoRaw = e.currentTarget.getAttribute('data-user-info');
    if (userInfoRaw) {
      userInfoRaw = JSON.parse(decodeURIComponent(userInfoRaw.replace(/\+/g, '%20')));
      if (userInfoRaw) {
        setModalUser(userInfoRaw);
      }
    }
  };

  /**
   *
   */
  useEffect(() => {
    if (bodyRef.current) {
      const avatars = bodyRef.current.querySelectorAll('.activity-avatar-sm');
      avatars.forEach((el) => {
        el.classList.add('pointer');
        el.addEventListener('click', handleActivityAvatarClick, false);
      });
    }
  }, [notification]);

  /**
   * @type {{}}
   */
  const [emojis, emojiUsers] = useMemo(() => {
    if (notification.action === 'emoji') {
      const emojis_ = {};
      const users_ = [];
      for (let i = 0; i < notification.comment.emojis.length; i++) {
        const e   = notification.comment.emojis[i];
        e.tone    = e.user.skinTone;
        const key = `${e.code}-${e.tone}`;
        if (!emojis_[key]) {
          emojis_[key] = [];
        }
        emojis_[key].push(e);

        const index = users_.findIndex((u) => u.id === e.user.id);
        if (index === -1) {
          users_.push(e.user);
        }
      }

      let emojiUsers_ = '';
      if (users_.length > 2) {
        const len = users_.length - 2;
        const who = len === 1 ? 'other' : 'others';
        emojiUsers_ = `${users_[0].name.split(' ')[0]}, ${users_[1].name.split(' ')[0]}, & ${len} ${who}`;
      } else if (users_.length === 2) {
        emojiUsers_ = `${users_[0].name.split(' ')[0]} and ${users_[1].name.split(' ')[0]}`;
      } else if (users_[0]) {
        emojiUsers_ = users_[0].name.split(' ')[0];
      }

      return [emojis_, emojiUsers_];
    }

    return [{}, []];
  }, [notification]);

  /**
   * @param {MouseEvent} e
   */
  const handleClick = async (e) => {
    e.preventDefault();
    const href = e.currentTarget.getAttribute('href');
    if (notification.status === 'unread') {
      await userActions.setNotificationStatus(notification.id, 'read');
    }
    document.location.href = href;
    onClick(e);
  };

  /**
   * @param e
   */
  const handleDeleteClick = async (e) => {
    e.stopPropagation();
    e.preventDefault();
    // userActions.deleteNotification(notification.id);
    if (notification.status === 'unread') {
      await userActions.setNotificationStatus(notification.id, 'read');
    } else {
      await userActions.setNotificationStatus(notification.id, 'unread');
    }
  };

  if (action === 'mention' || action === 'reply') {
    const comment = action === 'mention' ? mention.comment : notification.comment;
    const href = router.generate('build_email', {
      id: comment.email.id,
      tid: comment.email.tid
    });

    return (
      <Container
        className="notification"
        status={notification.status}
        href={`${href}#activity-c-${comment.id}`}
        onClick={handleClick}
      >
        <LeftSide>
          <Avatar
            user={comment.user}
            onClick={(e) => {
              e.stopPropagation();
              e.preventDefault();
              setModalUser(comment.user);
            }}
            md
          />
        </LeftSide>
        <RightSide>
          <div className="d-flex align-items-center">
            <Title>
              {action === 'mention' ? (
                <>{comment.user.name.split(' ')[0]} mentioned you.</>
              ) : (
                <>{comment.user.name.split(' ')[0]} replied to you.</>
              )}
            </Title>
            <button
              className="ml-auto"
              title={notification.status === 'unread' ? 'Mark read' : 'Mark unread'}
              onClick={handleDeleteClick}
            >
              <Icon name={notification.status === 'unread' ? 'be-symbol-delete' : 'be-symbol-plus'} />
            </button>
          </div>
          <Body ref={bodyRef} dangerouslySetInnerHTML={{ __html: comment.content }} />
          <When>
            {formatDate(when)} {formatTime(when)}
          </When>
          <When>
            {comment.email.title}
          </When>
        </RightSide>
        {modalUser && (
          <PersonModal user={modalUser} onClose={() => setModalUser(null)} />
        )}
      </Container>
    );
  }

  if (action === 'emoji') {
    const comment = notification.comment;
    const href = router.generate('build_email', {
      id: comment.email.id,
      tid: comment.email.tid
    });

    return (
      <Container
        className="notification"
        status={notification.status}
        href={`${href}#activity-c-${comment.id}`}
        onClick={handleClick}
      >
        <LeftSide>
          <Avatar
            user={comment.user}
            onClick={(e) => {
              e.stopPropagation();
              e.preventDefault();
              setModalUser(comment.user);
            }}
            md
          />
        </LeftSide>
        <RightSide>
          <button
            style={{ float: 'right' }}
            title={notification.status === 'unread' ? 'Mark read' : 'Mark unread'}
            onClick={handleDeleteClick}
          >
            <Icon name={notification.status === 'unread' ? 'be-symbol-delete' : 'be-symbol-plus'} />
          </button>
          <Body ref={bodyRef} className="pt-0" dangerouslySetInnerHTML={{ __html: comment.content }} />
          <Who>
            {emojiUsers} reacted to your comment.
          </Who>
          <Emojis className="emoji-wrapper mb-1">
            {Object.keys(emojis).map((key) => (
              <EmojiWrap
                key={key}
                onClick={() => {}}
                onMouseEnter={() => {}}
                onMouseLeave={() => {}}
              >
                <Emoji code={key.split('-')[0]} tone={emojis[key][0].tone} />
                {emojis[key].length > 1 && (
                  <i>{emojis[key].length}</i>
                )}
              </EmojiWrap>
            ))}
          </Emojis>

          <When>
            {formatDate(when)} {formatTime(when)}
          </When>
          <When>
            {comment.email.title}
          </When>
        </RightSide>
      </Container>
    );
  }

  return null;
};

Notification.propTypes = {
  notification: PropTypes.object.isRequired,
  onClick: PropTypes.func.isRequired,
};

export default Notification;
