import styled from 'styled-components';

export const Container = styled.div`
  height: 100%;
  display: flex;
  flex-direction: column;
`;

export const Body = styled.div`
  display: flex;
  flex-grow: 2;
  position: relative;
  font-size: 1rem;
  background-color: ${p => p.theme.colorBuilderBG};
  overflow: hidden;
`;
