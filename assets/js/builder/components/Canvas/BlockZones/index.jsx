import React from 'react';
import { useSelector } from 'react-redux';
import { Data } from 'builder/engine';
import { BLOCK_DATA_ELEMENT_ID } from 'builder/engine/constants';
import { Zone } from './styles';

const BlockZones = () => {
  const zones = useSelector(state => state.rules.zones);
  const hoverEdits = useSelector(state => state.rules.hoverEdits);
  const activeEdits = useSelector(state => state.rules.activeEdits);
  const isEditingHtml = useSelector(state => state.rules.isEditingHtml);

  const ids = [];
  for (let i = 0; i < hoverEdits.length; i++) {
    const id = Data.get(hoverEdits[i], BLOCK_DATA_ELEMENT_ID);
    if (id) {
      ids.push(id.toString());
    }
  }
  for (let i = 0; i < activeEdits.length; i++) {
    const id = Data.get(activeEdits[i], BLOCK_DATA_ELEMENT_ID);
    if (id) {
      ids.push(id.toString());
    }
  }

  if (isEditingHtml) {
    return null;
  }

  return (
    <>
      {Object.keys(zones).map((key) => {
        const zone = zones[key];

        return (
          <Zone
            key={key}
            active={ids.indexOf(key) !== -1}
            editable={zone.isEditable}
            section={zone.isSection}
            component={zone.isComponent}
            region={zone.isRegion}
            style={{
              top:    zone.top,
              left:   zone.left,
              width:  zone.width,
              height: zone.height,
            }}
          />
        );
      })}
    </>
  );
};

export default BlockZones;
