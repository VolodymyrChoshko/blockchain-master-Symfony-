import styled from 'styled-components';

export const Container = styled.div`
  color: ${p => p.theme.colorBuilderSidebar};
  background-color: ${p => p.theme.colorBuilderSidebarBg};
  width: ${p => p.open ? p.theme.widthBuilderSidebar : 0}px;
  min-width: ${p => p.open ? p.theme.widthBuilderSidebar : 0}px;
  border-right: 1px solid ${p => p.theme.colorBorder};
  font-size: 13px;
  line-height: 1.3em;
  overflow: hidden;
  z-index: 2;
`;

export const Inner = styled.div`
  // padding: ${p => p.theme.gutter2};
  height: 100%;
`;
