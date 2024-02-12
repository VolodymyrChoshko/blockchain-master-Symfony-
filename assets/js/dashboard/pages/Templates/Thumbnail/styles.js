import styled from 'styled-components';

export const Container = styled.a`
  height: ${p => (p.peopleCount <= 20 ? 150 : 208)}px;
  width: 200px;
  overflow: hidden;
  display: block;
  text-decoration: none;
  background-color: #FFF;
  border-top: 1px solid #CCC;
  border-right: 1px solid #CCC;
  border-left: 1px solid #CCC;

  img {
    width: 100%;
  }
`;
