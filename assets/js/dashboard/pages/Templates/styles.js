import styled from 'styled-components';
import Button from 'components/Button';
import { iconColor } from 'theme';

export const AddFolderFooter = styled.div`
  margin-left: -1px;
  padding: ${p => p.theme.gutter2} ${p => p.theme.gutter3};
`;

export const AddFolderButton = styled.button`
  background-color: transparent;
  border: 0;
  outline: 0;
  cursor: pointer;
  font-weight: 500;
  padding-left: 0;
  font-size: ${p => p.theme.fontSizeMd};
  color: ${p => p.theme.colorText};
  display: flex;
  align-items: flex-end;

  .icon {
    ${p => iconColor(p.theme.colorText)};
  }
`;

export const NewEmailButton = styled(Button)`
  font-size: ${p => p.theme.fontSizeLg};
`;
