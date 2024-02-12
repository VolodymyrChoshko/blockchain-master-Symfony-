import React, { useState } from 'react';
import PropTypes from 'prop-types';
import useMe from 'dashboard/hooks/useMe';
import { useUsersActions } from 'dashboard/actions/usersActions';
import { useCommentActions } from 'builder/actions/commentActions';
import { useSocketActions } from 'builder/actions/socketActions';
import Emoji from 'components/Emoji';
import Button from 'components/Button';
import { Container } from './styles';

const EmojiSettingsModal = ({ onSelect, onHidden }) => {
  const me = useMe();
  const userActions = useUsersActions();
  const commentActions = useCommentActions();
  const socketActions = useSocketActions();
  const [skinTone, setSkinTone] = useState(me.skinTone);
  const tones = Array.from(Array(5).keys());

  /**
   *
   */
  const handleSaveClick = () => {
    userActions.setSkinTone(skinTone);
    commentActions.updateSkinTone(me.id, skinTone);
    socketActions.updateSkinTone(me.id, skinTone);
    onSelect(skinTone);
  };

  const footer = (
    <Button variant="main" onClick={handleSaveClick}>Save</Button>
  );

  return (
    <Container
      title="Emoji Settings"
      className="modal-emoji-settings"
      footer={footer}
      onHidden={onHidden}
      auto
      open
      sm
    >
      <label className="d-block mb-2">Skin Tone</label>
      <div className="d-flex">
        <button
          key={-1}
          onClick={() => setSkinTone(-1)}
          className={skinTone === -1 ? 'btn-tone selected' : 'btn-tone'}
        >
          <Emoji
            tone={-1}
            code="1F44D"
          />
        </button>
        {tones.map((key) => (
          <button
            key={key}
            onClick={() => setSkinTone(key)}
            className={skinTone === key ? 'btn-tone selected' : 'btn-tone'}
          >
            <Emoji
              tone={key}
              code="1F44D"
            />
          </button>
        ))}
      </div>
    </Container>
  );
};

EmojiSettingsModal.propTypes = {
  onSelect: PropTypes.func.isRequired,
  onHidden: PropTypes.func.isRequired,
};

export default EmojiSettingsModal;
