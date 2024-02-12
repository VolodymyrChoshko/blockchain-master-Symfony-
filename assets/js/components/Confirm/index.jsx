import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { useUIActions } from 'builder/actions/uiActions';
import Mask from 'components/Mask';
import MaskChild from 'components/MaskChild';
import Button from 'components/Button';
import Box from 'dashboard/components/Box';
import Icon from 'components/Icon';
import { Container, Inner } from './styles';

const defaultButtons = [
  {
    text:    'Okay',
    variant: 'main',
    action:  () => {}
  },
  {
    text:    'Cancel',
    variant: 'alt',
    action:  () => {}
  }
];

const defaultOptions = {
  status:       '',
  loading:      false,
  theme:        'none',
  prompt:       false,
  variant:      'main',
  placeholder:  '',
  animation:    'zoomIn',
  closeOnClick: true
};

const Confirm = ({ index, open, title, options, notice, children, buttons }) => {
  const uiActions = useUIActions();
  const [promptValue, setPromptValue] = useState('');
  const inputRef = useRef();
  const prevOpenRef = useRef(false);

  /**
   * @param e
   */
  const handleEscape = (e) => {
    if (e.key === 'Escape') {
      uiActions.confirmClose(index);
    }
  };

  // eslint-disable-next-line consistent-return
  useEffect(() => {
    let t;
    if (open && !prevOpenRef.current) {
      if (notice) {
        // eslint-disable-next-line no-use-before-define
        t = setTimeout(handleCloseClick, 5000);
      } else if (options.prompt !== false) {
        setPromptValue(options.prompt);
        t = setTimeout(() => {
          if (inputRef.current) {
            inputRef.current.focus();
          }
        }, 500);
      }

      document.addEventListener('keydown', handleEscape);
      prevOpenRef.current = true;

      return () => {
        clearTimeout(t);
        document.removeEventListener('keydown', handleEscape);
      };
    }

    prevOpenRef.current = open;
  }, [open, notice]);

  /**
   * @param e
   * @param btn
   */
  const handleButtonClick = (e, btn) => {
    if (options.prompt !== false && btn.text === 'Cancel') {
      uiActions.confirmClose(index);
      return;
    }

    if (options.onConfirm && options.prompt !== false) {
      options.onConfirm(promptValue);
    } else if (options.onConfirm) {
      options.onConfirm(e);
    } else if (btn.action) {
      btn.action(e, promptValue);
    }
    if (options.closeOnClick) {
      uiActions.confirmClose(index);
    }
  };

  /**
   *
   */
  const handleCloseClick = () => {
    uiActions.confirmClose(index);
  };

  /**
   *
   */
  const handleMaskClick = () => {
    if (!notice) {
      uiActions.confirmClose(index);
    }
  };

  if (buttons === 'danger') {
    buttons = Array.from(defaultButtons);
    buttons[0].variant = 'danger';
  } else if (buttons === '') {
    buttons = Array.from(defaultButtons);
  } else if (typeof buttons === 'function') {
    const action = buttons;
    buttons = Array.from(defaultButtons);
    buttons[0].action = action;
    if (options.variant === 'danger') {
      buttons[0].variant = 'danger';
    }
  }
  options = { ...defaultOptions, ...options };

  let inner = children;
  if (options.prompt !== false) {
    inner = (
      <input
        ref={inputRef}
        type="text"
        className="form-control d-block mt-2"
        value={promptValue}
        placeholder={options.placeholder}
        onChange={e => setPromptValue(e.target.value)}
        onKeyDown={(e) => {
          if (e.key === 'Enter') {
            handleButtonClick(e, buttons[0]);
          }
        }}
      />
    );
  }

  const child = (
    <Container padded={false} borderTheme={options.theme} white>
      <Box.Section className="pb-0">
        {title && !options.loading && options.prompt === false && (
          <h2 className="mb-2">
            {title}
          </h2>
        )}
        {title && (options.loading || options.prompt !== false) && (
          <h3 className="mb-2">
            {title}
          </h3>
        )}
        <Inner notice={notice} className="d-flex justify-content-center">
          {inner}
          {notice && (
            <button
              className="pointer ml-auto"
              title="Close"
              onClick={handleCloseClick}
            >
              <Icon name="be-symbol-delete" />
            </button>
          )}
        </Inner>
        {options.loading && (
          <div className="fancybox-loading fancybox-loading-inline mt-3" />
        )}
        {options.status && (
          <small className="d-block mt-2">
            {options.status}
          </small>
        )}
      </Box.Section>
      {buttons ? (
        <div className="d-flex justify-content-center p-3">
          {Object.keys(buttons).map((key, i) => (
            <Button
              key={key}
              tabIndex={i}
              variant={buttons[key].variant}
              className={`mr-2 ${buttons[key].className || ''}`}
              onClick={e => handleButtonClick(e, buttons[key])}
            >
              {buttons[key].text}
            </Button>
          ))}
        </div>
      ) : (
        <div className="pt-3" />
      )}
    </Container>
  );

  return (
    <Mask open={open} opaque={notice} zIndex={10000 + index} onClick={handleMaskClick}>
      <MaskChild
        startTabIndex={options.prompt !== false ? 0 : -1}
        trapFocus={!notice}
        animation={options.animation}
      >
        {child}
      </MaskChild>
    </Mask>
  );
};

Confirm.propTypes = {
  index:   PropTypes.number.isRequired,
  open:    PropTypes.bool,
  title:   PropTypes.string,
  notice:  PropTypes.bool,
  buttons: PropTypes.oneOfType([
    PropTypes.string,
    PropTypes.func,
    PropTypes.arrayOf(PropTypes.shape({
      text:      PropTypes.string,
      variant:   Button.propTypes.variant,
      className: PropTypes.string,
      action:    PropTypes.func
    }))
  ]),
  options: PropTypes.shape({
    status:       PropTypes.string,
    loading:      PropTypes.bool,
    animation:    PropTypes.string,
    theme:        PropTypes.string,
    variant:      Button.propTypes.variant,
    prompt:       PropTypes.oneOfType([PropTypes.bool, PropTypes.string]),
    placeholder:  PropTypes.string,
    onConfirm:    PropTypes.func,
    closeOnClick: PropTypes.bool
  }),
  children: PropTypes.node,
};

Confirm.defaultProps = {
  buttons: defaultButtons,
  options: defaultOptions
};

export default Confirm;
