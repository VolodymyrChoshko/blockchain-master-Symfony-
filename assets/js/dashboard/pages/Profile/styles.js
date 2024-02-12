import styled from 'styled-components';

export const AvatarWrap = styled.div`
  position: relative;
  width: 120px;
  height: 120px;
  margin: 0 auto;

  button {
    position: absolute;
    right: -6px;
    bottom: -6px;
    background-color: ${p => p.theme.colorBtnMain};
    outline: 0;
    border: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    color: #FFF;
    cursor: pointer;
    transition: all 100ms;
    display: flex;
    align-items: center;
    justify-content: center;

    &:hover {
      background-color: ${p => p.theme.colorBtnMainHover};
    }

    &:active {
      background-color: ${p => p.theme.colorBtnMainActive};
    }

    .icon {
      height: 16px;
      width: 16px;
    }
  }
`;

export const EmojiWrap = styled.div`
  .emoji {
    font-size: 16px;
  }

  .btn-tone {
    background-color: ${p => p.theme.isDarkMode ? '#3a3a3a' : '#f9f9f9'};
    border: 0;
    outline: 0;
    padding: ${p => p.theme.gutter2};
    border-radius: ${p => p.theme.borderRadiusSm};
    cursor: pointer;
    transition: all 100ms;

    &.selected {
      background-color: ${p => p.theme.isDarkMode ? '#2a2a2a' : '#e9e9e9'};
    }

    &:hover {
      background-color: ${p => p.theme.isDarkMode ? '#2a2a2a' : '#e9e9e9'};
    }
  }
`;
