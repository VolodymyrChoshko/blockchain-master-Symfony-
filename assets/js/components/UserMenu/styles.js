import styled from 'styled-components';
import { darken } from 'polished';

export const Container = styled.div`

`;

export const MenuInner = styled.div`
  padding: ${p => p.theme.gutter1} 0;
  width: 150px;

  .menu-item {
    padding: ${p => p.theme.gutter2} calc(${p => p.theme.gutter3} - 0.25rem);
    color: ${p => p.theme.colorBox};
    border-radius: ${p => p.theme.borderRadiusSm};
    display: block;
    text-align: left;

    &:hover {
      background-color: ${p => p.theme.colorSelectedBG};
    }

    &:active {
      background-color: ${p => darken(0.08, p.theme.colorSelectedBG)};
    }

    &:last-child {
      margin-bottom: 0;
    }
  }
`;

export const AvatarWrap = styled.div`
  position: relative;
  cursor: pointer;
`;
