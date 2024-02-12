import styled from 'styled-components';

export const Item = styled.div`
  a {
    color: ${p => p.theme.colorBuilderSidebar};

    &:hover {
      text-decoration: underline;
    }
  }
`;
