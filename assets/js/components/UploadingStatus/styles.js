import styled from 'styled-components';
import { lighten, darken } from 'polished';
import Box from 'dashboard/components/Box';

export const Container = styled(Box)`
  width: 450px;
  text-align: center;
  pointer-events: all;
  background-color: ${p => lighten(0.03, p.theme.colorBoxBG)};
`;

export const Spinner = styled.div`
  height: 60px;
  width: 60px;
`;

export const Percent = styled.div`
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  width: 50px;
  height: 50px;
  top: calc(50% - 26px);
  left: calc(50% - 23px);
`;

export const ErrorHandle = styled.div`
  text-align: left;
  margin-bottom: ${p => p.theme.gutter1};
  font-size: 0.9rem;
  color: #f76c6c;
  cursor: pointer;

  svg {
    height: 14px;
  }
`;

export const ErrorsContainer = styled.div`
  font-size: 0.9rem;
`;

export const ErrorsWrap = styled.div`
  background-color: ${p => darken(0.05, p.theme.colorBoxBG)};
  height: ${p => p.open ? 'auto' : '0'};
  overflow: hidden;
`;

export const ErrorsList = styled.ul`
  list-style: none;
  text-align: left;
  border: 1px solid ${p => p.theme.colorBorder};

  li {
    padding: ${p => p.theme.gutter2};
    background-color: ${p => darken(0.05, p.theme.colorBoxBG)};
    font-size: 0.9rem;

    &:nth-child(even) {
      background-color: ${p => darken(0.08, p.theme.colorBoxBG)};
    }
  }
`;
