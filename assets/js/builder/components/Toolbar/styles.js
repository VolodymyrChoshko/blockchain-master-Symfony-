import styled from 'styled-components';
import { iconColor } from 'theme';
import BEButton from 'components/Button';

export const Container = styled.div`
  width: ${p => p.theme.widthToolbar}px;
  color: ${p => p.theme.colorText};
  background-color: ${p => p.theme.colorBoxBG};
  border-right: ${p => p.direction === 'left' ? `1px solid ${p.theme.colorBorder}` : '0'};
  border-left: ${p => p.direction === 'right' ? `1px solid ${p.theme.colorBorder}` : '0'};
  z-index: 3000;
`;

export const Button = styled(BEButton)`
  border-radius: ${p => p.theme.borderRadiusSm};
  background-color: ${p => p.active ? p.theme.colorToolbarButtonActiveBG : 'transparent'} !important;
  border: 0;
  display: flex;
  align-items: center;
  margin: ${p => p.theme.gutter1};
  transition: background-color 150ms;
  padding: ${p => p.theme.gutter1} ${p => p.theme.gutter2};

  &:hover {
    background-color: ${p => p.theme.colorToolbarButtonHoverBG};
  }

  .icon {
    width: 24px;
    height: 24px;
    ${p => iconColor(p.theme.colorBox)};
  }
`;

export const Separator = styled.div`
  border-bottom: 1px solid ${p => p.theme.colorBorder};
  margin-bottom: ${p => p.theme.gutter2};
`;
