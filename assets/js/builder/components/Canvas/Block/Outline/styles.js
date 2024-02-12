import styled from 'styled-components';


export const Container = styled.div`
  border-style: solid;
  position: absolute;
  transition: all 50ms;

  &.builder-block-b-edit,
  &.builder-block-b-background {
    border-style: dashed;
    border-color: ${p => p.theme.bColorEditHover};

    &.hover {
      border-color: ${p => p.theme.bColorEditHover};
    }

    &.active {
      border: 1px solid ${p => p.theme.bColorEditActive};
    }
  }

  &.builder-block-b-section {
    border-color: ${p => p.theme.bColorSectionHover};

    &.hover {
      border-color: ${p => p.theme.bColorSectionHover};
    }

    &.active {
      border: 1px solid ${p => p.theme.bColorSectionActive};
    }
  }

  &.builder-block-b-region {
    border-color: ${p => p.theme.bColorRegionHover};

    &.hover {
      border-style: dashed;
      border-color: ${p => p.theme.bColorRegionHover};
    }

    &.active {
      border: 1px dashed ${p => p.theme.bColorRegionActive};
    }
  }

  &.builder-block-b-component {
    border-color: ${p => p.theme.bColorComponentHover};

    &.hover {
      border-color: ${p => p.theme.bColorComponentHover};
    }

    &.active {
      border: 1px solid ${p => p.theme.bColorComponentActive};
    }
  }

  &.builder-block-b-bgcolor {
    border-color: transparent;

    &.hover {
      border-color: transparent;
    }

    &.active {
      border: 1px solid transparent;
    }
  }
`;
