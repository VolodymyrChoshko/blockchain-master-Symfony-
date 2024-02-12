import styled from 'styled-components';

export const Container = styled.div`
  background-color: #555;
  margin-bottom: ${p => p.theme.gutter2};
  border-radius: ${p => p.theme.borderRadiusSm};
  border: 1px solid transparent;
  cursor: pointer;
  height: 100px;
  overflow: hidden;
  position: relative;

  &.hover {
    border: 1px solid #0ca7a2;
  }

  &.active {
    border: 1px solid #0ca7a2;
  }

  &.expanded {
    height: auto;
  }

  .icon {
    position: absolute;
    top: ${p => p.theme.gutter2};
    right: ${p => p.theme.gutter2};
  }

  strong {
    font-weight: 700;
    display: block;
    text-transform: capitalize;
    padding: ${p => p.theme.gutter2};
    background-color: lighten(#555, 5%);
    border-bottom: 1px solid #464646;
  }

  small,
  pre {
    font-family: monospace;
    font-size: 0.7rem;
    display: block;
    overflow-wrap: break-word;
  }

  ul {
    margin-top: ${p => p.theme.gutter2};
    font-size: 0.9rem;
  }

  li {
    text-transform: capitalize;
  }
`;

export const Body = styled.div`
  padding: ${p => p.theme.gutter2};
  border-top: 1px solid #616161;
  position: relative;

  button {
    position: absolute;
    bottom: ${p => p.theme.gutter2};
    right: ${p => p.theme.gutter2};
    background-color: #666;
    border: 0;
    color: #FFF;
    cursor: pointer;

    &:hover {
      background-color: #777;
    }
  }
`;
