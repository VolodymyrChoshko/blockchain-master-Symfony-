import React, { useEffect, useState, useMemo, useRef } from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import PopupMenu from 'components/PopupMenu';
import Avatar from 'dashboard/components/Avatar';
import { Container } from './styles';

const Mentions = ({ element, offset, hint, candidate, lastKey, onSelect, onClose }) => {
  const people = useSelector(state => state.builder.people);
  const [selected, setSelected] = useState(0);
  /** @type {{ current: HTMLElement }} */
  const containerRef = useRef();
  const peopleIndexRef = useRef(0);

  /**
   * @type {unknown}
   */
  const peopleFiltered = useMemo(() => {
    if (!hint) {
      if (people.length > 0) {
        candidate.current = people[0];
        setSelected(people[0].id);
      }

      return people;
    }

    const found = people.filter((p) => p.name.toLowerCase().indexOf(hint) !== -1);
    if (found.length > 0) {
      candidate.current = found[0];
      setSelected(found[0].id);
    }

    return found;
  }, [people, hint, candidate]);

  /**
   *
   */
  useEffect(() => {
    if (containerRef.current) {
      // containerRef.current.focus();
    }
  }, [people]);

  /**
   * @param {string} key
   */
  const handleKeyDown = (key) => {
    if (key === 'ArrowDown') {
      peopleIndexRef.current += 1;
      if (peopleIndexRef.current > peopleFiltered.length - 1) {
        peopleIndexRef.current = 0;
      }
      candidate.current = peopleFiltered[peopleIndexRef.current];
      setSelected(peopleFiltered[peopleIndexRef.current].id);
    } else if (key === 'ArrowUp') {
      peopleIndexRef.current += 1;
      if (peopleIndexRef.current < 0) {
        peopleIndexRef.current = peopleFiltered.length - 1;
      }
      candidate.current = peopleFiltered[peopleIndexRef.current];
      setSelected(peopleFiltered[peopleIndexRef.current].id);
    } else if (key === 'Enter' && selected !== 0) {
      const person = peopleFiltered.find((p) => p.id === selected);
      if (person) {
        onSelect(person);
      }
    }
  };

  /**
   *
   */
  useEffect(() => {
      handleKeyDown(lastKey.split('.', 2)[0]);
  }, [lastKey, peopleFiltered]);

  return (
    <PopupMenu
      name="mentions"
      position="bottom"
      element={element}
      location={offset}
      offsetY={20}
      onClose={onClose}
      tipped={false}
    >
      <Container
        ref={containerRef}
        tabIndex={0}
        onKeyDown={handleKeyDown}
      >
        {peopleFiltered.map((person) => (
          <li
            key={person.id}
            className={selected === person.id ? 'selected' : ''}
            onClick={() => onSelect(person)}
          >
            <Avatar user={person} className="mr-2" sm />
            {person.name}
          </li>
        ))}
      </Container>
    </PopupMenu>
  );
};

Mentions.propTypes = {
  element:   PropTypes.object.isRequired,
  offset:    PropTypes.object.isRequired,
  hint:      PropTypes.string.isRequired,
  candidate: PropTypes.object.isRequired,
  lastKey:   PropTypes.string.isRequired,
  onClose:   PropTypes.func.isRequired,
  onSelect:  PropTypes.func.isRequired,
};

Mentions.defaultProps = {};

export default Mentions;
