import styled from 'styled-components';
import { iconColor } from 'theme';

export const Container = styled.a`
  position: fixed;
  bottom: 15px;
  right: 15px;
  z-index: 10000;

  .icon {
    height: 20px;
    width: 20px;
    ${p => iconColor(p.theme.colorBox)}
    transition: color 0.2ms ease-in;
  }
`;
