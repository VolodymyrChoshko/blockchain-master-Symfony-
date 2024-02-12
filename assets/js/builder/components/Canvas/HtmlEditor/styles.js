import styled from 'styled-components';
import { darken } from 'polished';
import { iconColor } from 'theme';

export const Container = styled.div`
  height: ${p => p.isExpandedHtml ? '100%' : '35%'};
  width: 100%;
`;

export const Inner = styled.div`
  height: 100%;
  width: 100%;
`;

export const Toolbar = styled.div`
  padding: 0.25rem;
  background-color: ${p => p.theme.colorBoxBG};
  border-top: 1px solid ${p => darken(0.1, p.theme.colorBoxBG)};
  border-bottom: 1px solid ${p => darken(0.1, p.theme.colorBoxBG)};
  display: flex;
  align-items: center;
  justify-content: space-between;

  .icon {
    ${p => iconColor(p.theme.colorText)};
  }
`;
