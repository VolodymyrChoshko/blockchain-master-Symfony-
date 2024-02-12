import styled from 'styled-components';

export const Container = styled.input`
  display: block;
  margin: 0;
  width: 100%;
  padding: ${p => p.theme.gutter2} ${p => p.theme.gutter2};
  color: #414141;
  border: ${p => (p.error ? 'red' : '#dfdfdf')} 1px solid;
  background-color: #FFF;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 300;
  font-family: Helvetica Neue, Helvetica, Arial, sans-serif;
  box-sizing: border-box;

  &:focus {
    outline: 0;
  }

  &:disabled {
    color: ${p => p.theme.colorMuted};
    opacity: 0.8;
    border: #cbcbcb 1px solid;
  }
`;
