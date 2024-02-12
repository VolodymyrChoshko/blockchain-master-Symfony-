import styled from 'styled-components';

export const Container = styled.div`
  cursor: ${p => p.dragging ? 'grabbing' : 'grab'};
  margin-bottom: calc(${p => p.theme.gutter2} + 6px);
  overflow: hidden;
  list-style-type: none;
  font-size: 0;
  user-select: none;

  img,
  .builder-sidebar-draggable-script {
    width: 100%;
    border-radius: ${p => p.theme.borderRadiusSm};
    border: 1px solid ${p => p.theme.colorBuilderSidebarBorder};
  }

  p {
    font-size: 14px;
    line-height: 1.3em;
    font-weight: 300;
  }
`;

export const Generating = styled.div`
  background-color: grey;
  min-height: 150px;
  position: relative;

  .fancy-loading {
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    margin: auto;
    user-select: none;
    pointer-events: none;
  }
`;

export const GeneratingLabel = styled.p`
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  margin: auto;
  user-select: none;
  pointer-events: none;
  height: 1rem;
  color: #FFF;
  text-align: center;
  position: absolute;
`;

export const AmpScript = styled.div`
  height: 80px;
  background-color: #FFF;
  color: #000;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;

  span {
    font-size: 4rem;
    color: grey;
    position: relative;
    top: -5px;
  }
`;
