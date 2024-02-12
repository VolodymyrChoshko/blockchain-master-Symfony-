import styled, { css } from 'styled-components';

export const Container = styled.div`
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: none;
  z-index: ${p => p.zIndex};
  transition: background 120ms ease-in-out;

  ${({ mounted, flexStart }) => mounted && css`
    background: rgba(0, 0, 0, 0);
    display: flex;
    align-items: ${flexStart ? 'flex-start' : 'center'};
    justify-content: center;
  `}

  ${({ visible, black, opaque }) => (visible && !opaque) && css`
    background: ${black ? '#000' : 'rgba(3, 4, 4, 0.4)'};
  `};

  ${({ visible, opaque }) => (visible && opaque) && css`
    background: transparent;
    align-items: flex-start;
    margin-top: 10px;
    pointer-events: none;
  `};
`;
