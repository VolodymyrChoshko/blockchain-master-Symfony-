import styled from 'styled-components';

export const Container = styled.div`
  display: flex;
  align-items: center;
  color: ${p => p.theme.colorBox};
  padding:
    ${p => (p.big ? p.theme.gutter2 : 0)}
    ${p => p.theme.gutter3}
    ${p => (p.big ? p.theme.gutter2 : 0)}
    ${p => (p.big ? p.theme.gutter3 : 0)};
  border-bottom: ${p => (p.big ? `1px solid ${p.theme.colorBorder}` : '0')};
  background-color: ${p => p.theme.colorBoxBG};
  width: 100%;
`;

export const Title = styled.div`
  // width: 100%;
  font-size: ${p => p.theme.fontSizeLg};
  text-align: left;
  flex-grow: 2;
`;

export const Controls = styled.div`
  text-align: left;
  padding-left: ${p => p.theme.gutter3};

  button:first-child {
    margin-right: ${p => p.theme.gutter1};
  }
`;
