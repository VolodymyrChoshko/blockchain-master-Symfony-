import React, { useEffect, useState, useRef, useContext } from 'react';
import { createPortal } from 'react-dom';
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import { iFrameDocument, hasParentClass } from 'utils/browser';
import { PopupMenuContext } from 'components/PopupMenuProvider';
import { Container } from './styles';

const { floor } = Math;

const PopupMenu = ({
  name,
  element,
  children,
  position,
  className,
  tipped,
  offsetX,
  offsetY,
  location,
  unClickable,
  onClose
}) => {
  const popupContext = useContext(PopupMenuContext);
  const [top, setTop] = useState(0);
  const [left, setLeft] = useState(0);
  const [opacity, setOpacity] = useState(0);
  const [reversed, setReversed] = useState(false);
  const iframe = useSelector(state => state.builder.iframe);
  const container = useRef(0);

  /**
   *
   */
  const handleDocClick = (e) => {
    if (!hasParentClass(e.target, 'builder-popup-menu')) {
      if (unClickable && hasParentClass(e.target, unClickable)) {
        return;
      }
      popupContext.setOpened('');
      onClose();
    }
  };

  /**
   *
   */
  useEffect(() => {
    if (!element) {
      return;
    }

    const { innerHeight, innerWidth } = window;
    const cr = container.current.getBoundingClientRect();
    const er = element.getBoundingClientRect();

    if (location) {
      let y = location.y + offsetY;
      let x = location.x + offsetX;
      const bottom = y + cr.height;
      const right = x + cr.width;

      if (bottom > innerHeight) {
        y -= cr.height;
      }
      if (right > innerWidth) {
        x -= cr.width;
      }

      setTop(y);
      setLeft(x);
      setTimeout(() => setOpacity(1), 100);
      return;
    }

    if (position === 'bottom') {
      let y = (er.y + er.height);
      if (y + cr.height > (innerHeight - 20)) {
        y = (innerHeight - 20) - (cr.height + 14);
        setReversed(true);
      }

      setTop(floor(y) + offsetY);
      setLeft(floor((er.x - cr.width) + 36) + offsetX);
      setTimeout(() => setOpacity(1), 100);
    } else {
      setReversed(true);
      setTop(floor((er.y - cr.height) - 20) + offsetY);
      setLeft(floor((er.x - cr.width) + 36) + offsetX);
      setTimeout(() => setOpacity(1), 100);
    }
  }, [element, location, position]);

  /**
   *
   */
  useEffect(() => {
    if (element) {
      popupContext.setOpened(name);

      if (iframe) {
        iFrameDocument(iframe).addEventListener('click', handleDocClick, false);
      }
      document.addEventListener('click', handleDocClick, false);

      return () => {
        if (iframe) {
          iFrameDocument(iframe).removeEventListener('click', handleDocClick);
        }
        document.removeEventListener('click', handleDocClick);
      };
    }

    return () => {};
  }, [iframe, element, name]);

  /**
   *
   */
  useEffect(() => {
    if (popupContext.opened !== '' && popupContext.opened !== name) {
      popupContext.setOpened('');
      onClose();
    }
  }, [popupContext]);

  if (!element) {
    return null;
  }

  const mount = document.getElementById('builder-popup-menu-mount');
  if (!mount) {
    console.error('PopupMenu did not find mount point.');
    return null;
  }

  return createPortal((
    <Container
      ref={container}
      tipped={tipped}
      reversed={reversed}
      style={{ top, left, opacity }}
      className={classNames('builder-popup-menu', className)}
    >
      {children}
    </Container>
  ), mount);
};

PopupMenu.propTypes = {
  name:        PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  element:     PropTypes.object,
  position:    PropTypes.oneOf(['top', 'bottom']),
  tipped:      PropTypes.bool,
  className:   PropTypes.string,
  unClickable: PropTypes.string,
  onClose:     PropTypes.func.isRequired,
  children:    PropTypes.node.isRequired,
  offsetX:     PropTypes.number,
  offsetY:     PropTypes.number,
  location:    PropTypes.shape({
    x: PropTypes.number.isRequired,
    y: PropTypes.number.isRequired,
  }),
};

PopupMenu.defaultProps = {
  tipped:   true,
  position: 'bottom',
  offsetX:  0,
  offsetY:  0,
};

export default PopupMenu;
