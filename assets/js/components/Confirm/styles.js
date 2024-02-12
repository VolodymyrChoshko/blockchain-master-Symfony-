import styled from 'styled-components';
import { lighten } from 'polished';
import { iconColor } from 'theme';
import Box from 'dashboard/components/Box';

export const Container = styled(Box)`
  width: 400px;
  text-align: center;
  pointer-events: all;
  background-color: ${p => lighten(0.03, p.theme.colorBoxBG)};
`;

export const Inner = styled.div`
  padding: ${p => p.notice ? p.theme.gutter2 : 0};

  button {
    background-color: transparent;
    border: 0;
    outline: 0;
    padding: 0;
  }

  .icon {
    ${p => iconColor(p.theme.colorSubTitle)};
    opacity: 0.6;
    transition: opacity 50ms;

    &:hover {
      opacity: 1;
    }
  }
`;
