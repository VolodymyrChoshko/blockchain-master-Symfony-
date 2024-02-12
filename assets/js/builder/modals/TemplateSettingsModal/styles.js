import styled from 'styled-components';
import { SwitchWrap as SwitchWrapOrig } from 'builder/sidebars/RulesSidebar/styles';

export const SwitchWrap = styled(SwitchWrapOrig)`
  padding-bottom: ${p => p.theme.gutter2};
  border-bottom: ${p => p.theme.colorBorder} 1px solid;
  display: block;

  &.no-underline {
    border-bottom: 0;
  }

  label {
    font-size: 1rem;
  }
`;
