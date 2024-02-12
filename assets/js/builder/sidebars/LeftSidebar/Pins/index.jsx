import React, { useState } from 'react';
import { useSelector } from 'react-redux';
import { useBuilderActions } from 'builder/actions/builderActions';
import { useUIActions } from 'builder/actions/uiActions';
import Button from 'components/Button';
import Icon from 'components/Icon';
import SidebarTitle from 'builder/components/SidebarTitle';
import PinSettingsModal from 'builder/modals/PinSettingsModal';
import PinGroupSettingsModal from 'builder/modals/PinGroupSettingsModal';
import Draggable from 'builder/sidebars/LeftSidebar/Draggables/Draggable';
import PinGroup from './PinGroup';

const Pins = () => {
  const uiActions = useUIActions();
  const builderActions = useBuilderActions();
  const libraries = useSelector(state => state.builder.libraries);
  const pinGroups = useSelector(state => state.builder.pinGroups);
  const previewDevice = useSelector(state => state.ui.previewDevice);
  const templateVersion = useSelector(state => state.builder.templateVersion);
  const [editingLibrary, setEditingLibrary] = useState(null);
  const [editingGroup, setEditingGroup] = useState(null);

  const newPinGroups = pinGroups.sort((a, b) => {
    return a.name > b.name ? 1 : -1;
  });

  const newLibraries = libraries.filter((library) => {
    if (library.mobile && previewDevice === 'desktop') {
      return null;
    }
    if (!library.mobile && previewDevice === 'mobile') {
      return null;
    }

    return library;
  });

  let hasUpgrade = false;
  for (let i = 0; i < newLibraries.length; i++) {
    if (templateVersion > newLibraries[i].tmp_version && newLibraries[i].isUpgradable) {
      hasUpgrade = true;
      break;
    }
  }

  /**
   * @param {Event} e
   */
  const handleUpdateAllClick = (e) => {
    e.stopPropagation();
    // eslint-disable-next-line max-len
    uiActions.confirm('', 'There has been an update to the template. Do you want to update your pins to take into account the latest template changes?', [
      {
        text:    'Okay',
        variant: 'main',
        action:  () => {
          builderActions.pinsUpgradeAll();
        }
      },
      {
        text:    'Later',
        variant: 'alt'
      }
    ]);
  };

  /**
   *
   */
  const handlePinGroupClick = () => {
    uiActions.prompt('Pin Group Name', '', '', (value) => {
      if (value) {
        builderActions.pinGroupSave(value);
      }
    });
  };

  /**
   * @param e
   * @param library
   */
  const handleLibrarySettingsClick = (e, library) => {
    setEditingLibrary(library);
  };

  /**
   * @param e
   * @param pinGroup
   */
  const handleGroupSettingsClick = (e, pinGroup) => {
    setEditingGroup(pinGroup);
  };

  return (
    <div className="builder-sidebar-draggables-sections">
      <SidebarTitle>
        Drag a pin below into your email.
      </SidebarTitle>
      <div className="p-2 text-muted">
        <Button variant="main" onClick={handlePinGroupClick}>
          Add a pin group
        </Button>
        {hasUpgrade && (
          <div className="mt-2">
            <Button className="text-muted" onClick={handleUpdateAllClick}>
              <Icon
                name="be-symbol-update"
                title="Update"
                className="mr-2 pointer"
              />
              Update pins
            </Button>
          </div>
        )}
      </div>
      {newPinGroups.length > 0 && (
        <>
          {newPinGroups.map(pinGroup => (
            <PinGroup
              key={pinGroup.id}
              pinGroup={pinGroup}
              libraries={newLibraries}
              onPinEdit={handleLibrarySettingsClick}
              onGroupEdit={handleGroupSettingsClick}
            />
          ))}
          <div style={{ borderBottom: '#ccc 1px solid' }} />
        </>
      )}

      <div className="p-2">
        {newLibraries.map((library) => {
          if (library.pinGroup) {
            return null;
          }

          return (
            <Draggable
              key={library.id}
              draggable={library}
              previewDevice={previewDevice}
              onPinEdit={handleLibrarySettingsClick}
            />
          );
        })}
      </div>

      {editingLibrary && (
        <PinSettingsModal
          library={editingLibrary}
          onClick={() => setEditingLibrary(null)}
        />
      )}

      {editingGroup && (
        <PinGroupSettingsModal
          pinGroup={editingGroup}
          onClick={() => setEditingGroup(null)}
        />
      )}
    </div>
  );
};

export default Pins;
