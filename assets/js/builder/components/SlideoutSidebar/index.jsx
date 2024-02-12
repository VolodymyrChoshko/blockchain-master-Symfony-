import styled from 'styled-components';

/**
 * @param p
 * @returns {string}
 */
const getTranslateX = (p) => {
  if (p.open) {
    return `-${p.theme.widthToolbar - 1}px`;
  }
  return `${p.theme.widthBuilderSidebar + p.theme.widthToolbar}px`;
};

const SlideoutSidebar = styled.div`
  color: ${p => p.theme.colorBuilderSidebar};
  background-color: ${p => p.theme.colorBuilderSidebarBg};
  width: ${p => p.theme.widthBuilderSidebar}px;
  min-width: ${p => p.theme.widthBuilderSidebar}px;
  border-left: 1px solid ${p => p.theme.colorBorder};
  transform: translateX(${p => getTranslateX(p)});
  transition: transform 250ms ease-in-out;
  text-align: left;
  z-index: ${p => p.zIndex || 2};
  height: 100%;
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
`;

export default SlideoutSidebar;
