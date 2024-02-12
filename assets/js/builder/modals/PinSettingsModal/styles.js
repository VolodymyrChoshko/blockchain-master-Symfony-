import styled from 'styled-components';
import { lighten } from 'polished';
import Box from 'dashboard/components/Box';

export const Container = styled(Box)`
  width: 450px;
  text-align: center;
  pointer-events: all;
  background-color: ${p => lighten(0.03, p.theme.colorBoxBG)};
`;
