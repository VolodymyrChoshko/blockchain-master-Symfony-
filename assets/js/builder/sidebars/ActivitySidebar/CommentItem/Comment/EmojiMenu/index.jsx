import React, { useState } from 'react';
import PropTypes from 'prop-types';
import useMe from 'dashboard/hooks/useMe';
import PopupMenu from 'components/PopupMenu';
import Emoji from 'components/Emoji';
// import Icon from 'components/Icon';
import EmojiSettingsModal from 'components/EmojiSettingsModal';
import { Container } from './styles';

const emojiCodes = [
  '1f4af',
  '2705',
  '2764',
  '1F440',
  '1F44D',
  '1f44f',
  '1f389',
  '1F525'
];

const hasTone = [
  '1F44D',
  '1f44f',
];

const EmojiMenu = ({ name, element, onSelect, onClose }) => {
  const me = useMe();
  // const [showingSettings, setShowingSettings] = useState(false);
  const [skinTone, setSkinTone] = useState(me.skinTone);
  // const settingsRef = useRef(null);

  return (
    <PopupMenu
      name={name}
      element={element}
      onClose={onClose}
      offsetY={20}
      unClickable="modal-emoji-settings"
    >
      <Container>
        {emojiCodes.map((code) => (
          <button
            key={code}
            onClick={() => {
              onSelect(code);
            }}
          >
            <Emoji
              code={code}
              tone={hasTone.indexOf(code) !== -1 ? skinTone : -1}
            />
          </button>
        ))}
        {/* <button
          ref={settingsRef}
          onClick={() => setShowingSettings(true)}
          className="btn-settings"
          title="Settings"
        >
          <Icon name="be-symbol-settings" />
        </button> */}
      </Container>

      {/* {showingSettings && (
        <EmojiSettingsModal
          onHidden={() => setShowingSettings(false)}
          onSelect={(t) => {
            setSkinTone(t);
            setShowingSettings(false);
          }}
        />
      )} */}
    </PopupMenu>
  );
};

EmojiMenu.propTypes = {
  name:     PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  element:  PropTypes.object,
  onSelect: PropTypes.func.isRequired,
  onClose:  PropTypes.func.isRequired,
};

export default EmojiMenu;
