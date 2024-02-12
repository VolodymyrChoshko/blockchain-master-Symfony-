import { lighten } from 'polished';
import styled from 'styled-components';
import Button from 'components/Button';
import { iconColor } from 'theme';

export const Container = styled.div`
  color: ${p => p.theme.colorText};
  position: relative;
  // width: 450px;
  z-index: 10000;
  cursor: pointer;

  .icon-selector {
    font-size: 0.7rem;
    z-index: 1001;
  }
`;

export const Selected = styled.div`
  font-size: 24px;
  line-height: 30px;
  font-weight: ${p => p.theme.fontWeightRegular};
  // padding-right: 30px;
  display: flex;
  align-items: center;
  color: ${p => p.theme.colorText};

  &.opened {
    padding: ${p => p.theme.gutter2};
    border-bottom: 1px solid ${p => p.theme.colorBorder};

    div {
      font-size: ${p => p.theme.fontSizeSm};
      color: #b6b6b6;
    }
  }

  span {
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
  }
`;

export const Dropdown = styled.div`
  position: absolute;
  top: -8px;
  left: -8px;
  width: 400px;
  color: ${p => p.theme.colorBox};
  background-color: ${p => p.theme.colorBoxBG};
  border-radius: ${p => p.theme.borderRadiusLg};
  box-shadow: ${p => p.theme.boxShadow};
  z-index: 10000;
`;

export const Item = styled.div`
  display: flex;
  justify-content: space-between;
  font-size: ${p => p.theme.fontSizeLg};
  padding: ${p => p.theme.gutter1} ${p => p.theme.gutter2};
  border-bottom: 1px solid ${p => p.theme.colorBorder};
  background-color: ${p => p.selected ? lighten(0.03, p.theme.colorBoxBG) : p.theme.colorBoxBG};
`;

export const UpdatedAt = styled.div`
  color: #b6b6b6;
  font-size: ${p => p.theme.fontSizeSm};
`;

export const Thumbnail = styled.div`
  width: 56px;
  height: 56px;
  border: 1px solid ${p => p.theme.colorBorder};
  overflow: hidden;

  img {
    width: 100%;
  }
`;

export const OptionButton = styled(Button)`
  color: ${p => p.theme.colorDark};
  font-size: 1.3rem;
  display: flex;

  &:hover {
    color: ${p => lighten(0.08, p.theme.colorDark)};
  }

  &:active {
    color: ${p => lighten(0.1, p.theme.colorDark)};
  }

  .icon {
    ${p => iconColor(p.theme.colorText)};
  }
`;
