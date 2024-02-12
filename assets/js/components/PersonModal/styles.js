import styled from 'styled-components';
import Box from 'dashboard/components/Box';

export const Container = styled(Box)`
  width: 400px;
  height: 350px;
`;

export const Role = styled.div`
  padding: 4px 10px;
  margin-bottom: ${p => p.theme.gutter3};
  background-color: #000;
  border-radius: 0.25rem;
  margin-top: -4px;
  font-size: 14px;
  text-transform: uppercase;
  color: ${p => p.theme.colorLight};
`;

export const Name = styled.div`
  font-size: 24px;
  font-weight: 400;
  margin-bottom: ${p => p.theme.gutter2};
`;

export const Time = styled.div`
  font-size: 16px;
  font-weight: 400;
`;
