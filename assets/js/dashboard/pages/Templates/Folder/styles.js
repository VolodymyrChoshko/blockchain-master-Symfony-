import { lighten } from 'polished';
import styled from 'styled-components';
import { iconColor } from 'theme';
import Icon from 'components/Icon';

/**
 * @param p
 * @returns {string|*|string}
 */
const highlight = (p) => {
  if (p.isOver) {
    if (p.theme.isDarkMode) {
      return '#5c5c5c';
    }
    return '#e9e9e9';
  }

  return p.theme.colorBoxBG;
};

/**
 * @param p
 * @returns {string|string|*}
 */
const getBorderColor = (p) => {
  if (p.isOver) {
    if (p.theme.isDarkMode) {
      return '#5c5c5c';
    }
    return '#e9e9e9';
  }

  return p.theme.colorBorder;
};

export const Container = styled.div`
  display: flex;
  align-items: center;
  color: ${p => p.theme.colorBox};
  padding:
    ${p => p.isRenaming ? p.theme.gutter2 : p.theme.gutter3}
    ${p => p.theme.gutter2}
    ${p => p.isRenaming ? p.theme.gutter2 : p.theme.gutter3}
    ${p => p.theme.gutter3};
  user-select: none;
  border-bottom: 1px solid ${p => getBorderColor(p)};
  background-color: ${p => highlight(p)};
  text-align: left;
  transition: background-color 25ms;
`;

export const TitleWrap = styled.div`
  width: 500px;
  text-align: left;
  display: flex;
  align-items: center;
  justify-content: space-between;

  &:hover {
    .rename-wrap {
      opacity: 1;
    }
  }

  .rename-wrap {
    opacity: 0;
    transition: opacity 50ms;

    button {
      background-color: transparent;
      border: 0;
      outline: 0;
      cursor: pointer;
      color: #b6b6b6;
    }
  }
`;

export const Name = styled.div`
  display: flex;
  align-items: center;
  cursor: pointer;
  padding-left: ${p => p.depth * 1.75}rem;
  font-size: ${p => p.theme.fontSizeLg};

  .icon {
    ${p => iconColor(p.theme.colorText)};
  }
`;

export const Remove = styled.div`
  text-align: right;
  flex-grow: 1;
  display: flex;
  justify-content: flex-end;
  align-items: center;
`;

export const Button = styled.button`
  color: ${p => p.theme.colorText};
  background-color: transparent;
  border: 0;
  outline: 0;
  cursor: pointer;
  display: inline-flex;
  align-items: center;

  &:hover {
    color: ${p => lighten(0.1, p.theme.colorText)};
  }

  .icon {
    ${p => iconColor(p.theme.colorText)};
  }
`;

export const EmailIcon = styled(Icon)`
  ${p => iconColor(p.theme.colorText)};
  font-size: ${p => p.theme.fontSizeLg};
`;
