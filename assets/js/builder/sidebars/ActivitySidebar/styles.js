import styled, { css } from 'styled-components';

export const Container = styled.div`
  display: flex;
  flex-direction: column;
  height: 100%;
  color: ${p => p.theme.colorText};

  .form-control {
    height: 34px;
    font-size: 1rem;
    line-height: 1.5rem;
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
    transition: height 150ms ease-in-out;

    &.expanded {
      height: 3.5rem;
      min-height: auto;

      &.shrinking {
        min-height: unset;
        height: 34px;
      }
    }

    &.expanded-complete {
      height: auto;
      min-height: 3.5rem;
    }
  }
`;

export const Inner = styled.div`
  flex-grow: 2;
  overflow: hidden;
`;

export const Panel = styled.div`

`;

export const Pane = styled.div`
  padding: ${p => p.theme.gutter2};
  border-bottom: 1px solid ${p => p.theme.isDarkMode ? '#484848' : p.theme.colorBorder};

  ${({ highlighted, theme }) => highlighted && css`
    background-color: ${theme.isDarkMode ? '#46474c' : '#eef6f9'};
  `}
`;
