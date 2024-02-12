import styled, { css, keyframes } from 'styled-components';

const containerIn = keyframes`
  0% {
    transform: scale(0);
  }
  100% {
    transform: scale(1);
  }
`;

export const Container = styled.div`
  padding: ${p => p.theme.gutter1} ${p => p.theme.gutter2};
  border-radius: ${p => p.theme.borderRadiusMd};
  display: inline-flex;
  align-items: center;
  color: ${p => p.theme.isDarkMode ? '#FFF' : '#525252'};
  font-size: 0.9rem;
  transition: background-color 100ms;
  cursor: pointer;
  animation: ${containerIn} 150ms ease-in;
  background-color: ${p => p.theme.isDarkMode ? '#5a5a5a' : '#E9E9E9'};
  border: 1px solid ${p => p.theme.isDarkMode ? '#343434' : '#d9d9d9'};
  border-left: 3px solid #1297BE;

  ${({ activated, theme }) => activated && css`
    border-left: 3px solid #1caed9;
    background-color: ${theme.isDarkMode ? '#6b6b6b' : '#dedede'};
  `}

  &:hover {
    background-color: ${p => p.theme.isDarkMode ? '#6b6b6b' : '#dedede'};
  }

  &:active {
    background-color: ${p => p.theme.isDarkMode ? '#727272' : '#d2d2d2'};
  }

  .icon {
    height: 14px;
    width: 14px;
    margin-right: ${p => p.theme.gutter2};
  }
`;
