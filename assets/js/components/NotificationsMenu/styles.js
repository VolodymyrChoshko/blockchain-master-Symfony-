import styled from 'styled-components';
import { darken } from 'polished';

export const Container = styled.div`
  height: 40px;
  width: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  margin-right: ${p => p.theme.gutter3};

  .icon {
    height: 20px;
    width: 20px;
  }
`;

export const Pill = styled.div`
  color: #FFF;
  background-color: #E03829;
  font-size: 0.7rem;
  font-weight: 400;
  line-height: 1;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: absolute;
  top: 0;
  right: 0;
`;

export const Dot = styled.div`
  background-color: #E03829;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  position: absolute;
  top: 4px;
  right: 4px;
`;

export const Notifications = styled.div`
  overflow: hidden;
  width: 325px;
`;

export const Header = styled.div`
  display: flex;
  padding: ${p => p.theme.gutter1} ${p => p.theme.gutter1} ${p => p.theme.gutter2} ${p => p.theme.gutter1};
  border-bottom: 1px solid ${p => p.theme.colorBorder};

  button,
  a {
    font-size: 0.9rem;
    line-height: 1;
    color: ${p => p.theme.colorText};
    background-color: transparent;
    outline: 0;
    border: 0;
    cursor: pointer;
    transition: all 100ms;
    padding: ${p => p.theme.gutter1};
    border-radius: ${p => p.theme.borderRadiusSm};

    &:hover {
      background-color: ${p => p.theme.colorSelectedBG};
    }

    &:active {
      background-color: ${p => darken(0.08, p.theme.colorSelectedBG)};
    }
  }
`;

export const Empty = styled.div`
  padding: ${p => p.theme.gutter3}
    ${p => p.theme.gutter2}
    calc(${p => p.theme.gutter3} - 0.25rem)
    ${p => p.theme.gutter2};
  width: 225px;
`;
