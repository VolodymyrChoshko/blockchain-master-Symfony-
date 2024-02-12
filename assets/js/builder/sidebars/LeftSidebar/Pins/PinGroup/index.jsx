import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import Icon from 'components/Icon';
import Draggable from 'builder/sidebars/LeftSidebar/Draggables/Draggable';
import { useSelector } from 'react-redux';
import { get, set } from 'lib/storage';
import { Container, Header, Body, Rename } from './styles';

const PinGroup = ({ pinGroup, libraries, onPinEdit, onGroupEdit }) => {
  const [isCollapsed, setCollapsed] = useState(false);
  const [isOver, setOver] = useState(false);
  const previewDevice = useSelector(state => state.ui.previewDevice);
  const key = `editor.pinGroups.collapsed.${pinGroup.id}`;

  /**
   *
   */
  useEffect(() => {
    const is = get(key, false);
    setCollapsed(is);
  }, [key]);

  /**
   *
   */
  const handleCollapseClick = () => {
    const is = !isCollapsed;
    setCollapsed(is);
    set(key, is);
  };

  return (
    <Container>
      <Header
        onMouseEnter={() => setOver(true)}
        onMouseLeave={() => setOver(false)}
      >
        <p
          className="mr-auto mb-0 text-truncate pointer"
          style={{ maxWidth: 250, padding: '5px 0' }}
          onClick={handleCollapseClick}
        >
          {pinGroup.name}
        </p>
        {isOver && (
          <Rename onClick={(e) => onGroupEdit(e, pinGroup)}>RENAME</Rename>
        )}
        <Icon
          name={isCollapsed ? 'be-symbol-arrow-down' : 'be-symbol-arrow-up'}
          onClick={handleCollapseClick}
          className="pointer"
        />
      </Header>
      <Body isCollapsed={isCollapsed}>
        {libraries.map((library) => {
          if (library.pinGroup !== pinGroup.id) {
            return null;
          }

          return (
            <Draggable
              key={library.id}
              draggable={library}
              previewDevice={previewDevice}
              onPinEdit={onPinEdit}
            />
          );
        })}
      </Body>
    </Container>
  );
};

PinGroup.propTypes = {
  pinGroup:    PropTypes.object.isRequired,
  libraries:   PropTypes.array.isRequired,
  onPinEdit:   PropTypes.func.isRequired,
  onGroupEdit: PropTypes.func.isRequired,
};

export default PinGroup;
