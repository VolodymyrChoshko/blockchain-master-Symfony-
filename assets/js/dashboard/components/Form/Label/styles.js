import styled from 'styled-components';

export const Container = styled.label`
  margin-bottom: ${p => p.theme.gutter2};
  display: inline-block;
`;

export const Error = styled.div`
  color: red;
  font-size: ${p => p.theme.fontSizeSm};
`;
