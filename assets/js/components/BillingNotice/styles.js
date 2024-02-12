import styled from 'styled-components';

export const Container = styled.div`
  margin: 0 auto;
  width: 720px;
  color: #444444 !important;
  text-align: center !important;
  background-color: #ffe782 !important;
  font-weight: 400;
  padding: ${p => p.theme.gutter3};
  box-shadow: ${p => p.theme.boxShadow};
  border-radius: ${p => p.theme.borderRadiusLg};

  a {
    color: ${p => p.theme.colorDark};
  }
`;
