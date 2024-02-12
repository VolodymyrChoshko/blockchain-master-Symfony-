import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { Container } from './styles';

const CharCount = ({ maxChars, inputRef }) => {
  const [remaining, setRemaining] = useState(maxChars);

  /**
   *
   */
  const handleKeyUp = () => {
    setRemaining(maxChars - inputRef.current.textContent.length);
  };

  /**
   * @param e
   */
  const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      setRemaining(maxChars);
    }
  };

  /**
   *
   */
  useEffect(() => {
    if (inputRef.current) {
      inputRef.current.addEventListener('input', handleKeyUp, false);
      inputRef.current.addEventListener('keydown', handleKeyDown, false);

      return () => {
        if (inputRef.current) {
          inputRef.current.removeEventListener('input', handleKeyUp);
          inputRef.current.removeEventListener('keydown', handleKeyDown, false);
        }
      };
    }

    return () => {};
  }, []);

  return (
    <Container>
      {remaining.toLocaleString()}
    </Container>
  );
};

CharCount.propTypes = {
  inputRef: PropTypes.object.isRequired,
  maxChars: PropTypes.number.isRequired,
};

CharCount.defaultProps = {};

export default CharCount;
