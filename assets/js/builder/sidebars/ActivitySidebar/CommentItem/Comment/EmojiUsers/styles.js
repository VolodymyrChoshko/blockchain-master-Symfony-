import styled from 'styled-components';
import { lighten, darken } from 'polished';

export const Container = styled.div`
  flex-direction: column;
  padding: ${p => p.theme.gutter2} ${p => p.theme.gutter3};

  .emoji-users-big {
    font-size: 26px;
    line-height: 26px;
    background-color: ${p => p.theme.colorSelectedBG};
    border-radius: 8px;
    padding: 8px;
    display: inline-block;
    margin: 0 auto;
  }
`;

export const User = styled.div`
  font-size: 0.9rem;
  padding: ${p => p.theme.gutter1} 0;
  cursor: pointer;
  display: flex;

  .avatar {
    margin-right: ${p => p.theme.gutter2};
  }
`;

export const Time = styled.div`
  font-size: 12px;
  line-height: 12px;
  color: ${p => p.theme.isDarkMode ? darken(0.2, p.theme.colorText) : lighten(0.2, p.theme.colorText)};
`;
