import styled, { css } from 'styled-components';
import { lighten, darken } from 'polished';
import { iconColor } from 'theme';

export const Container = styled.a`
  color: ${p => p.theme.colorBox};
  background-color: ${p => p.theme.colorBoxBG};
  border-bottom: 1px solid ${p => p.theme.colorBorder};
  padding:
    ${p => p.theme.gutter2}
    calc(${p => p.theme.gutter2} - 4px)
    ${p => p.theme.gutter2}
    calc(${p => p.theme.gutter2} - 4px);
  display: flex;
  text-align: left;

  &:hover {
    background-color: ${p => p.theme.isDarkMode ? lighten(0.02, p.theme.colorBoxBG) : darken(0.01, p.theme.colorBoxBG)};
  }

  ${({ status, theme }) => status === 'unread' && css`
    background-color: ${theme.isDarkMode ? '#25454f' : darken(0.05, theme.colorBoxBG)};

    &:hover {
      background-color: ${theme.isDarkMode
        ? lighten(0.02, '#25454f')
        : darken(0.07, theme.colorBoxBG)};
    }
  `}

  .emoji-wrapper div {
    animation: none;
  }
`;

export const RightSide = styled.div`
  flex-grow: 1;

  button {
    background-color: transparent;
    outline: 0;
    border: 0;
    cursor: pointer;
    transition: opacity 50ms;

    &:hover {
      .icon {
        ${p => iconColor(p.theme.colorBox)};
        opacity: 1;
      }
    }

    .icon {
      width: 12px;
      height: 12px;
      opacity: 0.7;
      ${p => iconColor(p.theme.colorBox)};
    }
  }
`;

export const LeftSide = styled.div`
  padding-right: ${p => p.theme.gutter1};
`;

export const Title = styled.h6`
  font-size: 1rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: ${p => p.theme.isDarkMode ? '#b9b9b9' : '#727272'};
`;

export const Body = styled.div`
  padding: ${p => p.theme.gutter1} ${p => p.theme.gutter2} ${p => p.theme.gutter1} 0;
  font-size: 1rem;
  line-height: 1.5rem;
  max-height: 80px;
  overflow: hidden;

  .avatar {
    height: 20px;
    width: 20px;
  }
`;

export const When = styled.small`
  font-size: 0.9rem;
  color: ${p => p.theme.isDarkMode ? '#b9b9b9' : '#727272'};
  display: block;
`;

export const Who = styled.div`
  font-size: 0.9rem;
  padding-bottom: ${p => p.theme.gutter1};
  padding-right: ${p => p.theme.gutter1};
  color: ${p => p.theme.isDarkMode ? '#b9b9b9' : '#727272'};
`;
