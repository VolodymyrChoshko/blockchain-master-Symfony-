import React, { useEffect, useState, useLayoutEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Scrollbars } from 'react-custom-scrollbars';
import { findIndexByID } from 'utils/arrays';
import { Link } from 'react-router-dom';
import Icon from 'components/Icon';

const FancySelect = ({ id, label, className, initialValue, options, minWidth, maxHeight, onChange }) => {
  const [width, setWidth]   = useState(99);
  const [value, setValue]   = useState(initialValue);
  const [isOpen, setOpen]   = useState(false);
  const [isReady, setReady] = useState(false);
  const ulRef               = useRef(null);
  const selectRef           = useRef(null);
  const scrollbarsRef       = useRef(null);
  const scrollTopRef        = useRef(-1);
  const prevValue           = useRef(initialValue);

  /**
   * @param e
   * @param v
   */
  const setValueChange = (e, v) => {
    setValue(v);
    onChange(e, v);
  };

  /**
   *
   */
  useEffect(() => {
    if (width === 99) {
      // setWidth(selectRef.current.offsetWidth + 85);
      setReady(true);
    }

    /**
     *
     */
    const handleDocClick = () => {
      if (isOpen) {
        setOpen(false);
      }
    };

    document.addEventListener('click', handleDocClick, false);

    return () => {
      document.removeEventListener('click', handleDocClick);
    };
  }, [isOpen, width]);

  /**
   *
   */
  useEffect(() => {
    if (isOpen) {
      /**
       * @param e
       */
      const handleKeyDown = (e) => {
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
          e.preventDefault();

          const index = findIndexByID(options, value, 'value');
          let newIndex = index + 1;
          if (e.key === 'ArrowUp') {
            newIndex = index - 1;
          }

          if (options[newIndex]) {
            setValue(options[newIndex].value);

            const li = ulRef.current.querySelector(`li[data-select-value="${options[newIndex].value}"]`);
            if (li) {
              scrollbarsRef.current.scrollTop(li.offsetTop);
            }
          }
        } else if (e.key === 'Enter') {
          onChange(e, value);
          setOpen(false);
        }
      };

      document.addEventListener('keydown', handleKeyDown, false);

      return () => {
        document.removeEventListener('keydown', handleKeyDown);
      };
    }

    return () => {};
  }, [isOpen, value, options]);

  /**
   *
   */
  useEffect(() => {
    if (initialValue !== prevValue.current) {
      prevValue.current = initialValue;
      setValue(initialValue);
    }
  }, [initialValue]);

  /**
   *
   */
  useLayoutEffect(() => {
    if (isOpen) {
      if (scrollTopRef.current !== -1) {
        scrollbarsRef.current.scrollTop(scrollTopRef.current);
        return;
      }

      const li = ulRef.current.querySelector(`li[data-select-value="${value}"]`);
      if (li) {
        const outerRect = scrollbarsRef.current.view.getBoundingClientRect();
        const liRect    = li.getBoundingClientRect();
        if (liRect.y < outerRect.y || liRect.y > (outerRect.y + outerRect.height)) {
          scrollbarsRef.current.view.scrollTo({
            top:      li.offsetTop,
            left:     0,
            behavior: 'auto'
          });
        }
      }
    }
  }, [isOpen, value, options]);

  /**
   * @param {Event} e
   */
  const handleChange = (e) => {
    setValueChange(e, e.target.value);
  };

  /**
   *
   */
  const handleListClick = () => {
    if (options.length > 1) {
      setOpen(!isOpen);
    }
  };

  /**
   * @param {Event} e
   * @param {number} v
   */
  const handleItemClick = (e, v) => {
    if (v === value) {
      e.preventDefault();
    } else {
      setValue(v);
      onChange(e, v);
    }
  };

  /**
   *
   */
  const handleScroll = () => {
    const top = scrollbarsRef.current.getScrollTop();
    if (top !== 0) {
      scrollTopRef.current = scrollbarsRef.current.getScrollTop();
    }
  };

  let realHeight = 38 * options.length;
  if (realHeight > maxHeight) {
    realHeight = maxHeight;
  }

  const scrollbarStyles = {
    minWidth,
    height: realHeight
  };

  const items = (
    <ul
      ref={ulRef}
      className="form-fancy-select-list"
      onClick={handleListClick}
      aria-hidden
    >
      {options.map((option) => {
        const children = [
          <span key="label">{option.label}</span>
        ];
        if (option.isStar && options.length > 1 && isOpen) {
          children.push(
            <Icon key="star" className="form-fancy-select-star" name="be-symbol-star" />
          );
        }

        const props = {
          className: (isOpen && option.value === value) ? 'active' : ''
        };
        if (option.href) {
          props.href = option.href;
        }
        const inner = React.createElement(option.href ? 'a' : 'div', props, children);
        if (options.length === 1) {
          return (
            <li>
              <Link to="/">
                {children}
              </Link>
            </li>
          );
        }

        return (
          <li
            key={option.value}
            data-select-value={option.value}
            onClick={e => handleItemClick(e, option.value)}
            style={{ display: (isOpen || option.value === value ? 'block' : 'none') }}
            className={option.value === value && !isOpen ? 'selected' : ''}
          >
            {inner}
          </li>
        );
      })}
    </ul>
  );

  return (
    <div className={classNames('form-fancy-select', className, { 'open': isOpen })} style={{ minWidth }}>
      {(isReady && !isOpen) && (
        items
      )}
      {(options.length > 1 && !isOpen) && (
        <Icon
          key="caret"
          className="form-fancy-select-caret pointer"
          name={isOpen ? 'be-symbol-arrow-up' : 'be-symbol-arrow-down'}
          onClick={handleListClick}
        />
      )}
      {(isReady && isOpen) && (
        <Scrollbars
          ref={scrollbarsRef}
          style={scrollbarStyles}
          className={classNames({ 'form-fancy-select-list open': isOpen })}
          onScroll={handleScroll}
        >
          {items}
        </Scrollbars>
      )}
    </div>
  );
};

FancySelect.propTypes = {
  id:           PropTypes.string.isRequired,
  label:        PropTypes.string.isRequired,
  options:      PropTypes.array.isRequired,
  minWidth:     PropTypes.number,
  maxHeight:    PropTypes.number,
  initialValue: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  className:    PropTypes.string,
  onChange:     PropTypes.func
};

FancySelect.defaultProps = {
  minWidth:     0,
  maxHeight:    300,
  className:    '',
  onChange:     () => {},
  initialValue: ''
};

export default FancySelect;
