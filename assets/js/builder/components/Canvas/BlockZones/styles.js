import styled, { css } from 'styled-components';

export const Zone = styled.div`
  position: absolute;
  pointer-events: none;

  ${({ active, editable }) => !editable && css`
    border: ${active ? '2px solid #888888' : '1px dashed #888888'};
  `}
  ${({ active, editable, theme }) => editable && css`
    border: ${active ? `2px solid ${theme.bColorEditHover}` : `1px dashed ${theme.bColorEditHover}`};
  `}
  ${({ active, section, theme }) => section && css`
    border: ${active ? `2px solid ${theme.bColorSectionHover}` : `1px dashed ${theme.bColorSectionHover}`};
  `}
  ${({ active, component, theme }) => component && css`
    border: ${active ? `2px solid ${theme.bColorComponentHover}` : `1px dashed ${theme.bColorComponentHover}`};
  `}
  ${({ active, region, theme }) => region && css`
    border: ${active ? `2px solid ${theme.bColorRegionHover}` : `1px dashed ${theme.bColorRegionHover}`};
  `}
`;
