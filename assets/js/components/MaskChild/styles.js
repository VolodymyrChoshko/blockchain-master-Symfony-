import styled from 'styled-components';

export const Container = styled.div`
  opacity: ${p => p.visible ? '1' : '0'};
  animation-duration: 250ms;
`;
