import styled from 'styled-components';
import Box from 'dashboard/components/Box';

export const Container = styled(Box)`
  position: absolute;
  top: 58px;
  left: calc(50% - 720px / 2);
  max-height: 300px;
  overflow-y: auto;
  z-index: 100002;
  background-color: #FFF;
`;
