import styled from 'styled-components';

export const Container = styled.iframe`
  width: 100%;
  height: 100%;
  min-height: calc(100vh - ${p => p.theme.heightBuilderHeader}px);
`;
