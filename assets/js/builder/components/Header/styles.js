import styled from 'styled-components';

export const Container = styled.header`
  height: 65px;
  color: ${p => p.theme.colorBox};
  background-color: ${p => p.theme.colorBoxBG};
  border-bottom: 1px solid ${p => p.theme.colorBorder};
`;

export const Editing = styled.span`
  color: ${p => p.theme.colorSubTitle};
`;
