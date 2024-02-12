import styled from 'styled-components';
import Modal from 'components/Modal';

export const Container = styled(Modal)`
  min-width: 300px;
  width: 14vw;

  .emoji {
    font-size: 22px;
  }

  .btn-tone {
    background-color: transparent;
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
