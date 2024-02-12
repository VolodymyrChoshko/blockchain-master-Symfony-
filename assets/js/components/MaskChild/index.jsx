import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { Container } from './styles';

const MaskChild = ({ open, className, animation, children, trapFocus, startTabIndex, clickable, ...props }) => {
  const [visible, setVisible] = useState(false);
  const prevOpen = useRef(false);
  const containerRef = useRef();
  const focusableRef = useRef([]);
  const focusedIndex = useRef(startTabIndex);

  /**
   * @param e
   */
  const handleKeyDown = (e) => {
    if (e.key !== 'Tab') {
      return;
    }

    if (e.shiftKey) {
      focusedIndex.current -= 1;
      if (focusedIndex.current < 0) {
        focusedIndex.current = focusableRef.current.length - 1;
      }
    } else {
      focusedIndex.current += 1;
      if (focusedIndex.current >= focusableRef.current.length) {
        focusedIndex.current = 0;
      }
    }

    focusableRef.current[focusedIndex.current].focus();
    e.preventDefault();
  };

  /**
   *
   */
  const handleTrapFocus = () => {
    // eslint-disable-next-line max-len
    focusableRef.current = containerRef.current.querySelectorAll('a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])');
    focusedIndex.current = startTabIndex;
    containerRef.current.addEventListener('keydown', handleKeyDown);
  };

  /**
   * @param e
   */
  const handleClick = (e) => {
    if (!clickable) {
      e.preventDefault();
    }
  };

  // eslint-disable-next-line consistent-return
  useEffect(() => {
    if (open && !prevOpen.current) {
      setTimeout(() => {
        setVisible(true);
        if (trapFocus) {
          setTimeout(handleTrapFocus, 250);
        }
      }, 120);

      return () => {
        containerRef.current.removeEventListener('keydown', handleKeyDown);
      };
    }
    prevOpen.current = open;
  }, [open]);

  return (
    <Container
      ref={containerRef}
      tabIndex={0}
      role="dialog"
      className={visible ? `be-mask-child animate__animated animate__${animation} ${className}` : `be-mask-child ${className}`}
      visible={visible}
      aria-modal="true"
      aria-hidden={!open}
      onMouseDown={handleClick}
      {...props}
    >
      {children}
    </Container>
  );
};

MaskChild.propTypes = {
  open:          PropTypes.bool,
  className:     PropTypes.string,
  animation:     PropTypes.string,
  children:      PropTypes.node,
  startTabIndex: PropTypes.number,
  trapFocus:     PropTypes.bool,
  clickable:     PropTypes.bool,
};

MaskChild.defaultProps = {
  open:          false,
  className:     '',
  animation:     'slideInDown',
  children:      '',
  startTabIndex: -1,
  trapFocus:     true,
  clickable:     false,
};


export default MaskChild;
