import styled from 'styled-components';
import Checkbox from 'components/forms/Checkbox';
import { darken, lighten } from 'polished';

export const Item = styled.div`
  border-bottom: ${p => p.theme.colorBorder} 1px solid;
`;

export const Title = styled.div`
  font-weight: 400;
  font-size: ${p => p.theme.fontSizeMd};

  ${({ checked }) => checked && `
    opacity: 0.6;
  `};
`;

export const Description = styled.div`
  font-size: 13px;

  ${({ checked }) => checked && `
    opacity: 0.6;
  `};
`;

export const CustomCheckbox = styled(Checkbox)`
  display: block;
  position: relative;
  padding-left: 35px;
  margin-bottom: 12px;
  cursor: pointer;
  font-size: 22px;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;

  input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
  }

  .checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 22px;
    width: 22px;
    background-color: #fefefe;
    border-radius: 0.25rem;
    border: ${p => p.theme.isDarkMode ? lighten(0.08, p.theme.colorBorder) : darken(0.08, p.theme.colorBorder)} 1px solid;
  }

  /* On mouse-over, add a grey background color */
  &:hover input ~ .checkmark {
    background-color: #fefefe;
  }

  /* When the checkbox is checked, add a blue background */
  & input:checked ~ .checkmark {
    background-color: #48BE77;
  }

  /* Create the checkmark/indicator (hidden when not checked) */
  .checkmark:after {
    content: "";
    position: absolute;
    display: none;
  }

  /* Show the checkmark when checked */
  & input:checked ~ .checkmark:after {
    display: block;
  }

  /* Style the checkmark/indicator */
  & .checkmark:after {
    left: 6px;
    top: 2px;
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 3px 3px 0;
    -webkit-transform: rotate(45deg);
    -ms-transform: rotate(45deg);
    transform: rotate(45deg);
  }
`;
