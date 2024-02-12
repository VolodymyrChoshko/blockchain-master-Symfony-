import styled from 'styled-components';
import { lighten, darken } from 'polished';

export const Filename = styled.div`
  background-color: ${p => p.theme.isDarkMode ? lighten(0.05, p.theme.colorBoxBG) : darken(0.1, p.theme.colorBoxBG)};
  padding: 4px 8px;
  font-size: 15px;
  white-space: pre-line;
  border-radius: ${p => p.theme.borderRadiusSm};
`;
