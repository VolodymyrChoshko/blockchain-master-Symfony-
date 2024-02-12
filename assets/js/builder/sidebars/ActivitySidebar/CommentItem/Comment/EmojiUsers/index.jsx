import React, { useState } from 'react';
import PropTypes from 'prop-types';
import Avatar from 'dashboard/components/Avatar';
import PersonModal from 'components/PersonModal';
import PopupMenu from 'components/PopupMenu';
import Emoji from 'components/Emoji';
import { formatDate, formatTime } from 'utils/dates';
import { Container, User, Time } from './styles';

const EmojiUsers = ({ name, element, emojis, onClose }) => {
  const [modalUser, setModalUser] = useState(null);

  if (!emojis) {
    return null;
  }

  return (
    <PopupMenu
      name={name}
      element={element}
      onClose={onClose}
      position="top"
    >
      <Container>
        <Emoji code={emojis[0].code} tone={emojis[0].tone || -1} className="emoji-users-big mb-2" />

        {emojis.map((emoji, i) => (
          <User key={i} onClick={() => setModalUser(emoji.user)}>
            <Avatar user={emoji.user} className="avatar" />
            <div className="d-flex flex-column align-items-start">
              {emoji.user.name}
              <Time>
                {formatDate(new Date(emoji.timeAdded * 1000))} {formatTime(new Date(emoji.timeAdded * 1000))}
              </Time>
            </div>
          </User>
        ))}
      </Container>
      {modalUser && (
        <PersonModal user={modalUser} onClose={() => setModalUser(null)} />
      )}
    </PopupMenu>
  );
};

EmojiUsers.propTypes = {
  name:    PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  element: PropTypes.object,
  emojis:  PropTypes.array,
  onClose: PropTypes.func.isRequired,
};

export default EmojiUsers;
