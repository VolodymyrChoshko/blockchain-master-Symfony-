import styled, { keyframes } from 'styled-components';
import { lighten, darken } from 'polished';
import { iconColor } from 'theme';

export const Body = styled.div`
  font-size: 1rem;
  line-height: 1.6rem;
  color: ${p => p.theme.isDarkMode ? '#FFF' : '#2c2c2c'};

  a {
    color: ${p => p.theme.isDarkMode ? '#FFF' : '#2c2c2c'} !important;
  }

  strong {
    font-weight: 700;
  }

  i,
  em {
    font-style: italic;
  }

  ol,
  ul {
    margin-left: 1rem;
  }

  p {
    margin-bottom: 0.25rem;

    &:last-child {
      margin-bottom: 0;
    }
  }

  img {
    max-width: 100%;
    object-fit: contain;
  }
`;

export const Name = styled.span`
  font-size: 1rem;
  line-height: 1;
`;

export const Status = styled.div`
  font-size: 1rem;
  line-height: 1;
  font-weight: 400;
  color: ${p => p.theme.isDarkMode ? darken(0.2, p.theme.colorText) : lighten(0.2, p.theme.colorText)};
  cursor: pointer;
`;

export const Time = styled.div`
  font-weight: 400;
  font-size: 0.9rem;
  color: ${p => p.theme.isDarkMode ? darken(0.2, p.theme.colorText) : lighten(0.2, p.theme.colorText)};
`;

export const ParentWrapper = styled.div`
  margin-left: 0.15rem;
  margin-top: ${p => p.theme.gutter3};
  margin-bottom: ${p => p.theme.gutter2};
  padding-left: ${p => p.theme.gutter2};
  border-left: 4px solid ${p => p.theme.isDarkMode ? '#5e5e5e' : '#AFAFAD'};

  ${Body} {
    font-size: 0.9rem;
  }

  ${Name} {
    font-size: 0.9rem;
  }

  ${Status} {
    font-size: 0.9rem;
  }
`;

const emojiIn = keyframes`
  0% {
    transform: scale(0);
  }
  100% {
    transform: scale(1);
  }
`;

export const Emojis = styled.div`
  display: flex;

  span {
    cursor: pointer;
    font-size: 14px;
    line-height: 14px;
  }
`;

export const EmojiWrap = styled.div`
  background-color: ${p => p.theme.colorSelectedBG};
  padding: 4px 6px;
  margin-right: 0.25rem;
  border-radius: 8px;
  animation: ${emojiIn} 150ms ease-in;

  i {
    font-size: 12px;
    margin-left: 4px;
  }
`;

export const Footer = styled.div`
  display: flex;
  margin-left: -${p => p.theme.gutter2};
  margin-right: -${p => p.theme.gutter2};

  button:not(.editing-btn) {
    background-color: transparent;
    border: 0;
    outline: 0;
    cursor: pointer;
    position: relative;
    border-radius: 8px;
    padding: 4px 6px;
    color: ${p => p.theme.isDarkMode ? darken(0.2, p.theme.colorText) : lighten(0.2, p.theme.colorText)};

    .icon {
      ${p => iconColor(p.theme.isDarkMode ? darken(0.2, p.theme.colorText) : lighten(0.2, p.theme.colorText))};
    }

    &:hover,
    &.selected {
      color: ${p => p.theme.colorText};
      background-color: ${p => p.theme.colorSelectedBG};

      .icon {
        ${p => iconColor(p.theme.colorText)};
      }
    }

    svg {
      height: 16px;
      width: 16px;
    }
  }
`;
