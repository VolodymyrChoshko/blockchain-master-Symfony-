import styled from 'styled-components';

export const Container = styled.ul`
  list-style: none;
`;

export const ListItem = styled.li`
  overflow: hidden;
  height: ${p => (p.isCollapsed ? '0' : 'auto')};
`;
