import styled from 'styled-components';

/**
 * @param p
 * @returns {string}
 */
const getSize = (p) => {
  if (p.lg) {
    return '120px';
  }
  if (p.md) {
    return '40px';
  }
  if (p.sm) {
    return '24px';
  }
  return '30px';
};

export const Container = styled.div`
  width: ${p => getSize(p)};
  height: ${p => getSize(p)};
  line-height: ${p => getSize(p)};
  font-size: ${p => (p.lg ? 60 : 14)}px;
  display: inline-block;
  overflow: hidden;
  border-radius: 100%;
  font-weight: 500;
  color: #fff;
  vertical-align: middle;
  text-align: center;
  background: linear-gradient(#67CF96, #91CF5C);

  img {
    width: 100%;
    height: 100%;
  }
`;
