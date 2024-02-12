import styled, { css } from 'styled-components';
import { Link } from 'react-router-dom';
import { iconColor } from 'theme';

export const Container = styled.div`
  display: flex;
  align-items: center;
`;

export const List = styled.ul`
  list-style-type: none;
  display: inline-block;
  max-width: 700px;
  text-align: left;
`;

export const Item = styled.li`
  display: inline-block;
  margin-right: ${p => p.theme.gutter1};
  margin-bottom: ${p => p.theme.gutter1};
  vertical-align: middle;

  .avatar {
    margin-right: 0;
    cursor: pointer;
  }

  img {
    margin-right: 0;
  }
`;

export const AddIcon = styled(Link)`
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 100%;
  width: 30px;
  height: 30px;
  background-color: #ddd;
  font-size: 14px;
  line-height: 30px;
  text-align: center;
  cursor: pointer;

  ${p => p.theme.isDarkMode && css`
    background-color: ${pp => pp.theme.colorBoxBG};
  `};

  .icon {
    ${p => iconColor(p.theme.colorText)};
  }
`;

export const AddButton = styled(Link)`
  font-size: ${p => p.theme.fontSizeMd};
  color: ${p => p.theme.colorText};
  margin-left: ${p => p.theme.gutter2};
`;
