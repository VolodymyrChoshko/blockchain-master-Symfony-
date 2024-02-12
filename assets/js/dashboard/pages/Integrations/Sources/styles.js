import styled from 'styled-components';

export const Table = styled.table`
  border-collapse: collapse;

  tr {
    width: 100%;
    border-bottom: ${p => p.theme.colorBorder} 1px solid;

    td {
      height: 50px;
      padding: 0 1.5rem;
      vertical-align: middle;
    }
  }

  .integration-col-icon {
    width: 35%;

    img {
      display: inline-block;
      margin-right: 5px;
      width: 30px;
      height: 30px;
      vertical-align: middle;
      text-align: center;
      line-height: 30px;
      font-weight: 500;
      color: #fff;
      font-size: 14px;
    }
  }

  .integration-col-name {
    width: 40%;
  }

  .integration-col-actions {
    width: 25%;
    padding-right: 15px;
    text-align: right;
  }
`;
