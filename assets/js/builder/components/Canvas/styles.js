import styled from 'styled-components';

export const Container = styled.div`
  position: sticky;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  margin: auto;
  width: 100%;
  font-size: 0;
  min-height: calc(${p => p.isEditingHtml ? p.expandedHeight : '100vh'} - ${p => p.theme.heightBuilderHeader}px);
  overflow-y: auto;
  overflow-x: hidden;
  background-color: #FFF;

  ${({ editing }) => editing && `
      margin-left: 0;
  `};

  ${({ isRuleEditing, isEditingHtml, expandedHeight, theme }) => isRuleEditing && `
    min-height: calc(${isEditingHtml ? expandedHeight : '100vh'} - ${theme.heightBuilderHeader}px - 30px);
  `}
`;
