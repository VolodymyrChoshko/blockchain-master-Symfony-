import styled from 'styled-components';

export const Container = styled.h4`
  padding: ${p => p.theme.gutter2};
  background-color: ${p => p.theme.isDarkMode ? '#4f4f4f' : '#F6F6F6'};
  border-bottom: 1px solid ${p => p.theme.isDarkMode ? '#484848' : p.theme.colorBorder};
  font-size: ${p => p.theme.fontSizeSm};
  font-weight: 400;
  text-align: left;
`;
