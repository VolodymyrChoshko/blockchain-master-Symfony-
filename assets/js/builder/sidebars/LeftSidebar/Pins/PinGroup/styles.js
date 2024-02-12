import styled from 'styled-components';

export const Container = styled.div`
  border-top: #ccc 1px solid;
  text-align: left;
  font-size: 14px;
  font-weight: 400;
  background-color: ${p => p.theme.colorBG};
`;

export const Header = styled.div`
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: ${p => p.theme.gutter1} ${p => p.theme.gutter2} 0.2rem ${p => p.theme.gutter2};

  p {
    font-size: 16px;
    line-height: 1.3rem;
    user-select: none;
  }
`;

export const Body = styled.div`
  height: ${p => p.isCollapsed ? '0px' : 'auto'};
  overflow: hidden;
  padding: 0 ${p => p.theme.gutter2};
`;

export const Rename = styled.span`
  margin-right: 0.5rem;
  cursor: pointer;
  line-height: 1;
`;
