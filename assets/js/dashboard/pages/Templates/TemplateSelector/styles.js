import styled from 'styled-components';
import { lighten } from 'polished';
import Button from 'components/Button';
import FancySelect from 'components/forms/FancySelect';

export const TemplateSelect = styled(FancySelect)`
  font-size: ${p => p.theme.fontSizeLg};
  margin-left: -${p => p.theme.gutter2};

  & .selected {
    font-size: 24px;
    line-height: 30px;
    font-weight: ${p => p.theme.fontWeightRegular};
    width: 100%;

    span {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      padding-right: 30px;
      width: 100%;
    }
  }
`;

export const OptionButton = styled(Button)`
  color: ${p => p.theme.colorDark};
  font-size: 1.3rem;

  &:hover {
    color: ${p => lighten(0.08, p.theme.colorDark)};
  }

  &:active {
    color: ${p => lighten(0.1, p.theme.colorDark)};
  }

  .icon {
    color: ${p => p.theme.colorDark};
    fill: ${p => p.theme.colorDark};
  }
`;
