import styled from 'styled-components';

export const Container = styled.div`
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: ${p => p.theme.gutter2};
`;

export const Card = styled.div`
  display: flex;
  align-items: center;
  border: ${p => p.theme.colorBorder} 1px solid;
  border-radius: ${p => p.theme.borderRadiusLg};
  height: 60px;
  color: ${p => p.theme.colorBox};
  background-color: ${p => p.theme.colorBoxBG};
  padding: ${p => p.theme.gutter2};
`;

export const Icon = styled.img`
  width: 40px;
`;

export const Name = styled.div`
  flex: 2;
  padding: 0 5px;
`;
