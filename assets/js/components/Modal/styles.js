import styled from 'styled-components';
import { lighten } from 'polished';
import { iconColor } from 'theme';

/**
 withTabs={tabs !== ''}
 */

/**
 * @param p
 * @returns {string}
 */
const getWidth = (p) => {
  if (p.sm) {
    if (p.narrow) {
      return '30vw';
    }
    return '25vw';
  }
  if (p.md) {
    return '60vw';
  }
  if (p.lg) {
    if (p.narrow) {
      return '30vw';
    }
    return '60vw';
  }

  return '30vw';
};

/**
 * @param p
 * @returns {string}
 */
const getHeight = (p) => {
  if (p.autoHeight) {
    return 'auto';
  }
  if (p.sm) {
    return '25vh';
  }
  if (p.md) {
    return '60vh';
  }
  if (p.lg) {
    return '62vh';
  }

  return '75vh';
};

export const Container = styled.div`
  // overflow: hidden;
  display: flex;
  flex-direction: column;
  text-align: left;
  width: ${p => getWidth(p)};
  height: ${p => getHeight(p)};
  min-width: 600px;
  color: ${p => p.theme.colorBox};
  background-color: ${p => p.theme.colorBoxBG};
  z-index: ${p => p.theme.zIndexMask + 1};
  border-radius: ${p => p.theme.borderRadiusLg};
  box-shadow: ${p => p.theme.boxShadow};
  transition-duration: 300ms;
  transition-timing-function: ease;
  transform: ${p => (p.visible ? 'translateY(0)' : 'translateY(-50%)')};
  opacity: ${p => (p.visible ? 1 : 0)};
  margin-top: ${p => (p.flexStart ? '20%' : 'auto')};
`;

export const Header = styled.div`
  display: inline-flex;
  width: 100%;
  font-size: ${p => p.theme.fontSizeLg};
  padding: ${p => p.theme.gutter3};
  color: ${p => p.theme.colorBox};
  background-color: ${p => lighten(0.04, p.theme.colorBoxBG)};
  border-bottom: ${p => (p.withTabs ? 0 : `${p.theme.colorBorder} 1px solid`)};
  border-top-left-radius: ${p => p.theme.borderRadiusLg};
  border-top-right-radius: ${p => p.theme.borderRadiusLg};

  .icon {
    margin-left: auto;
    margin-right: 4px;
    width: auto;
    cursor: pointer;
    font-size: 18px;
    opacity: 0.8;
    ${p => iconColor(p.theme.colorText)}

    &:hover {
      opacity: 1;
    }
  }
`;

export const Inner = styled.div`
  height: 100%;
`;

export const Body = styled.div`
  height: 100%;
  font-size: 15px;
  line-height: 1.3em;
  padding: ${p => p.theme.gutter3};

  hr {
    border-top: 1px solid #d2d2d2;
    border-left: 0;
  }
`;

export const Footer = styled.div`
  overflow: hidden;
  padding: ${p => p.theme.gutter3} ${p => p.theme.gutter2};
  color: ${p => p.theme.colorBox};
  background-color: ${p => lighten(0.04, p.theme.colorBoxBG)};
  border-top: ${p => p.theme.colorBorder} 1px solid;
  border-bottom-left-radius: ${p => p.theme.borderRadiusLg};
  border-bottom-right-radius: ${p => p.theme.borderRadiusLg};
`;
