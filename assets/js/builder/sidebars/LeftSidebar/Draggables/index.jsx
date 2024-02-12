import React from 'react';
import PropTypes from 'prop-types';
import SidebarTitle from 'builder/components/SidebarTitle';
import Draggable from './Draggable';

const Draggables = ({ sections, components, previewDevice }) => {
  if (sections.length > 0) {
    const usedStyles  = [];
    const newSections = sections.filter((section) => {
      if (section.mobile && previewDevice === 'desktop') {
        return null;
      }
      if (!section.mobile && previewDevice === 'mobile') {
        return null;
      }

      const { style } = section;
      if (style !== '' && style !== null && usedStyles.indexOf(style) !== -1) {
        return null;
      }
      usedStyles.push(style);
      return section;
    });

    return (
      <div className="builder-sidebar-draggables-sections">
        <SidebarTitle>
          Drag a section below into your email.
        </SidebarTitle>
        <div className="p-2">
          {newSections.map(section => (
            <Draggable
              key={section.id}
              draggable={section}
              previewDevice={previewDevice}
              onPinEdit={() => {}}
            />
          ))}
        </div>
      </div>
    );
  }

  const usedStyles    = [];
  const newComponents = components.filter((component) => {
    if (component.mobile && previewDevice === 'desktop') {
      return null;
    }
    if (!component.mobile && previewDevice === 'mobile') {
      return null;
    }

    const { style } = component;
    if (style !== '' && usedStyles.indexOf(style) !== -1) {
      return null;
    }
    usedStyles.push(style);
    return component;
  });

  return (
    <div className="builder-sidebar-draggables-sections">
      <SidebarTitle>
        Drag a component below into your email.
      </SidebarTitle>
      <div className="p-2">
        {newComponents.map(component => (
          <Draggable
            key={component.id}
            draggable={component}
            previewDevice={previewDevice}
            onPinEdit={() => {}}
          />
        ))}
      </div>
    </div>
  );
};

Draggables.propTypes = {
  sections:      PropTypes.array,
  components:    PropTypes.array,
  previewDevice: PropTypes.string.isRequired
};

Draggables.defaultProps = {
  sections:   [],
  components: []
};

export default Draggables;
