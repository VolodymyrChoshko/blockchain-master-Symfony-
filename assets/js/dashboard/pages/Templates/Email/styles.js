import styled from 'styled-components';
import { lighten } from 'polished';
import Icon from 'components/Icon';

/**
 * @param p
 * @returns {string|*|string}
 */
const getBackgroundColor = (p) => {
  if (p.highlighted) {
    if (p.theme.isDarkMode) {
      return '#4e461b';
    }
    return '#fdfdb7';
  }
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
  user-select: none;
  color: ${p => p.theme.colorBox};
  padding: ${p => p.theme.gutter1} ${p => p.theme.gutter2} ${p => p.theme.gutter1} ${p => p.theme.gutter3};
  border-bottom: 1px solid ${p => getBorderColor(p)};
  background-color: ${p => getBackgroundColor(p)};
  opacity: ${p => p.disabled ? 0.5 : 1};
`;

export const TitleWrap = styled.div`
  width: 550px;
  text-align: left;
  display: flex;
  align-items: ${p => (p.isSearch ? 'flex-start' : 'center')};
  justify-content: space-between;
  flex-direction: ${p => (p.isSearch ? 'column' : 'row')};

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

export const Title = styled.a`
  text-align: left;
  font-size: ${p => p.theme.fontSizeLg};
  font-weight: ${p => p.theme.fontWeightThin};
  padding-left: ${p => p.depth * 1.75}rem;
  color: ${p => p.theme.colorBox};
  width: 500px;
  // word-break: break-all;
  height: 40px;
  overflow: hidden;
  display: flex;
  align-items: center;
  line-height: 1;
`;

export const TitleDisabled = styled.span`
  text-align: left;
  font-size: ${p => p.theme.fontSizeLg};
  font-weight: ${p => p.theme.fontWeightThin};
  padding-left: ${p => p.depth * 1.75}rem;
  color: ${p => p.theme.colorBox};
`;

export const TemplateTitle = styled.a`
  color: ${p => p.theme.colorText};
  font-size: ${p => p.theme.fontSizeSm};
  margin-top: 2px;
  display: block;
  text-decoration: none;
`;

export const Controls = styled.div`
  width: 125px;
  text-align: left;
  display: flex;
  align-items: center;
`;

export const Author = styled.div`
  width: 220px;
  color: #b6b6b6;
  font-size: ${p => p.theme.fontSizeSm};
  text-align: left;
`;

export const Remove = styled.div`
  text-align: right;
  flex-grow: 1;
  display: flex;
  justify-content: flex-end;
  align-items: center;
`;

export const Button = styled.button`
  color: ${p => p.theme.colorDark};
  background-color: transparent;
  border: 0;
  outline: 0;
  cursor: pointer;
  display: inline-flex;
  align-items: center;

  &:hover {
    color: ${p => lighten(0.1, p.theme.colorDark)};
  }
`;

export const EmailIcon = styled(Icon)`
  color: ${p => p.theme.colorBox};
  fill: ${p => p.theme.colorBox};
  font-size: ${p => p.theme.fontSizeLg};
`;
