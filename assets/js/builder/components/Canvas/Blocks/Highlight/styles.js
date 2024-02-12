import styled from 'styled-components';

export const Container = styled.div`
  border: 1px solid ${p => p.theme.bColorSectionHover};
  box-shadow: 0 0 4px ${p => p.theme.bColorSectionHover};
  position: absolute;
`;
