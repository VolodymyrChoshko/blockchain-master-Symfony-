import styled from 'styled-components';
import { lighten } from 'polished';
import { iconColor } from 'theme';

/**
 * @param p
 * @returns {string}
 */
const getBackgroundColor = (p) => {
  switch (p.variant) {
    case 'main':
      return p.theme.colorBtnMain;
    case 'alt':
      return p.theme.colorBtnAlt;
    case 'danger':
      return p.theme.colorBtnDanger;
    case 'edit':
      return p.theme.colorBtnEdit;
    case 'save':
      return p.theme.colorBtnSave;
  }

  return 'transparent';
};

/**
 * @param p
 * @returns {string}
 */
const getDisabledBackground = (p) => {
  switch (p.variant) {
    case 'main':
      return '#ececec';
    case 'alt':
      return '#ececec';
    case 'danger':
      return '#ee2f29';
    case 'edit':
      return '#aaa';
    case 'save':
      return '#aaa';
  }

  return 'transparent';
};

/**
 * @param p
 * @returns {string|*}
 */
const getColor = (p) => {
  switch (p.variant) {
    case 'link':
      return '#118de4';
    case 'transparent':
      return p.theme.colorText;
  }

  return p.theme.colorLight;
};

/**
 * @param p
 * @returns {`0 ${string}`|`0 ${string} 2px ${string}`|`${string} ${string}`}
 */
const getPadding = (p) => {
  if (p.sm) {
    return `0 ${p.theme.gutter2}`;
  }
  if (p.lg) {
    return `${p.theme.gutter2} ${p.theme.gutter3}`;
  }

  return `0 ${p.theme.gutter3} 2px ${p.theme.gutter3}`;
};

export const Container = styled.button`
  margin: 0;
  border: 0;
  color: ${p => getColor(p)};
  cursor: pointer;
  padding: ${p => getPadding(p)};
  font-size: ${p => p.theme.fontSizeSm};
  line-height: 30px;
  font-weight: 400;
  border-radius: 50px;
  display: ${p => (p.wide ? 'block' : 'inline-block')};
  width: ${p => (p.wide ? '100%' : 'auto')};
  box-sizing: border-box;
  text-decoration: none;
  font-family: Helvetica Neue, Helvetica, Arial, sans-serif;
  border-bottom-width: 2px;
  white-space: nowrap;
  background: ${p => getBackgroundColor(p)};
  z-index: 2;
  position: relative;

  &:hover {
    background: ${p => lighten(0.03, getBackgroundColor(p))};
  }

  &:active,
  &:focus {
   outline: 0;
    background: ${p => lighten(0.05, getBackgroundColor(p))};
  }

  &.disabled,
  &:disabled {
    color: #777;
    cursor: default;
    background: ${p => getDisabledBackground(p)} !important;

    .icon {
      ${() => iconColor('#777')};
    }
  }

  &.builder-toolbar-button-active {
    background-color: ${p => p.theme.isDarkMode ? '#444444' : '#ECECEC'} !important;
  }
`;
