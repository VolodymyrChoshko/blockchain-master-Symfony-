import styled from 'styled-components';
import { darken } from 'polished';

const getWidth = (p) => {
  if (p.wide) {
    return '960px';
  }
  if (p.fluid) {
    return '90%';
  }
  if (p.narrow) {
    return '400px';
  }

  return '720px';
};

export const Section = styled.div`
  padding: ${p => p.theme.gutter3};
`;

export const Container = styled.div`
  margin: 0 auto;
  text-align: left;
  width: ${p => getWidth(p)};
  padding: ${p => p.padded ? p.theme.gutter3 : 0};
  color: ${p => p.theme.colorBox};
  background-color: ${p => (p.white ? p.theme.colorBoxBG : 'transparent')};
  border-radius: ${p => p.theme.borderRadiusLg};
  box-shadow: ${p => (p.shadow ? p.theme.boxShadow : 'none')};
  overflow: ${p => (p.overflowHidden ? 'hidden' : 'initial')};

  ${Section}:nth-child(even),
  ${Section}.dark {
    color: ${p => p.theme.colorSection};
    background-color: ${p => darken(0.05, p.theme.colorBoxBG)};
    // border-bottom: 1px solid ${p => p.theme.colorBorder};
    // border-top: 1px solid ${p => p.theme.colorBorder};
  }

  ${Section}.no-border-top {
    border-top: 0 !important;
  }

  ${({ borderTheme, theme }) => borderTheme !== 'none' && `
    border-left: 6px solid ${borderTheme === 'success' ? theme.colorSuccess : theme.colorDanger };
  `};
`;

export const Head = styled.div`
  width: 100%;
  position: relative;
  padding: ${p => p.theme.gutter3};
  overflow: hidden;
`;

export const Spacer = styled.div`
  padding: ${p => p.theme.gutter3};
`;
