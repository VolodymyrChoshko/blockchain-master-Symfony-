import styled from 'styled-components';

export const Container = styled.div`
  text-align: left;
  margin-bottom: ${p => p.theme.gutter3};

  &:last-child {
    margin-bottom: 0;
  }

  label {
    display: block;
    margin-bottom: 5px;
    color: ${p => p.theme.colorText};
  }

  .form-control {
    & + .btn {
      margin-left: ${p => p.theme.gutter2};
    }
  }

  .select2-container {
    margin-bottom: 0;
  }

  &.underlined {
    padding: 15px 0;
    border-bottom: 1px solid #ccc;
  }
`;
