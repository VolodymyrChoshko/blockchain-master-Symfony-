import styled from 'styled-components';

export const Container = styled.ul`
  margin: 0;
  padding: 0;
  list-style: none;

  &:focus {
    outline: 0;
  }

  li {
    display: flex;
    align-items: center;
    padding: ${p => p.theme.gutter2};
    cursor: pointer;
    transition: all 100ms ease-in-out;

    &:hover,
    &.selected {
      background-color: ${p => p.theme.colorSelectedBG};
    }
  }
`;
