import styled from 'styled-components';

export const Container = styled.div`
  color: ${p => p.theme.colorText};
  background-color: #0286af;
  height: 30px;
  font-size: 12px;
  padding-left: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: flex-start;

  button {
    color: #FFF;
    background-color: #009bcb;
    border: 0;
    outline: 0;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    cursor: pointer;

    &:hover {
      background-color: #01a7da;
    }

    &.active {
      background-color: #24586E;
    }
  }

  span {
    color: #FFF;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.5rem;
    line-height: 1;
  }
`;
