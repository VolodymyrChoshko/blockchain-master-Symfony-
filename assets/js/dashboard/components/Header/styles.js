import styled from 'styled-components';
import { Link } from 'react-router-dom';
import { iconColor } from 'theme';

export const Container = styled.header`
  height: 65px;
  color: ${p => p.theme.colorBox};
  background-color: ${p => p.theme.colorBoxBG};
  border-bottom: 1px solid ${p => p.theme.colorBorder};
`;

export const SettingsAnchor = styled(Link)`
  .icon {
    ${p => iconColor(p.theme.colorBox)}
    transition: color 0.2ms ease-in;
  }
`;
