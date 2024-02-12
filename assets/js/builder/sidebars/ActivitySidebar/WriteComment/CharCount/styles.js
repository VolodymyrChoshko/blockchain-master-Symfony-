import styled from 'styled-components';

export const Container = styled.div`
  font-size: 12px;
  float: right;
  color: ${p => p.theme.isDarkMode ? '#959595' : '#b1b1b1'};
  margin-top: 0.25rem;
`;
