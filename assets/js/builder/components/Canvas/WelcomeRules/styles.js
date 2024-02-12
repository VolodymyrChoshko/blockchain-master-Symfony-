import styled, { keyframes } from 'styled-components';

const fadeIn = keyframes`
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
`;

export const Container = styled.div`
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  animation-name: ${fadeIn};
  animation-duration: 200ms;

  .be-box {
    position: absolute;
    left: 375px;
    top: 92px;
    font-size: 1rem;
    width: 350px;
  }
`;
