import styled from 'styled-components';
import { iconColor } from 'theme';

export const Container = styled.div`
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  button {
    background-color: transparent;
    border: 0;
    outline: 0;
    cursor: pointer;
    border-radius: ${p => p.theme.borderRadiusSm};

    &:hover {
      background-color: rgba(0, 0, 0, 0.08);
    }
  }

  .emoji {
    font-size: 18px;
  }

  .btn-settings {
    .icon {
      ${iconColor('#afafaf')};
    }

    &:hover {
      .icon {
        ${iconColor('#8d8d8d')};
      }
    }
  }
`;
