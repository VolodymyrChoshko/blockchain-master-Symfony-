import styled, { css } from 'styled-components';

export const Container = styled.div`
  position: absolute;
  z-index: 3001;
  opacity: 0;
  transition: opacity 50ms ease-in;
  filter: drop-shadow(0 0 10px rgba(0, 0, 0, 0.25));
  background-color: ${p => p.theme.isDarkMode ? '#505050' : '#FFFFFF'};
  border: 1px solid ${p => p.theme.colorBorder};
  border-radius: ${p => p.theme.borderRadiusMd};
  padding: ${p => p.theme.gutter1};

  ${({ reversed, tipped }) => (tipped && reversed) && css`
    &:before {
      content:"";
      position: absolute;
      right: 11px;
      bottom: -10px;
      width: 0;
      height: 0;
      border-style: solid;
      border-width: 10px 10px 0 10px;
      border-color: ${p => p.theme.isDarkMode ? '#505050' : '#FFFFFF'} transparent transparent transparent;
      z-index:9999;
    }
  `}

  ${({ reversed, tipped }) => (tipped && !reversed) && css`
    &:before {
      content:"";
      position: absolute;
      right: 11px;
      top: -10px;
      width: 0;
      height: 0;
      border-style: solid;
      border-width: 0 10px 10px 10px;
      border-color: transparent transparent ${p => p.theme.isDarkMode ? '#505050' : '#FFFFFF'} transparent;
      z-index:9999;
    }
  `}
`;
