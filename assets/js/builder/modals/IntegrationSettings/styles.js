import styled from 'styled-components';

export const Form = styled.form`
  color: #FFF;

  .form-help {
    color: ${p => p.theme.colorText};
  }

  button[type="submit"] {
    display: none;
  }
`;

export const Icon = styled.img`
  height: 32px;
  width: 32px;
`;
