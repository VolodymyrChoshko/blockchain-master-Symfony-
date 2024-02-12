import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { useBuilderActions } from 'builder/actions/builderActions';
import { useUIActions } from 'builder/actions/uiActions';
import Mask from 'components/Mask';
import MaskChild from 'components/MaskChild';
import Input from 'components/forms/Input';
import Select from 'components/forms/Select';
import Widget from 'components/forms/Widget';
import Button from 'components/Button';
import Flex from 'components/Flex';
import { useSelector } from 'react-redux';
import { Container } from './styles';

const PinSettingsModal = ({ library, onClick }) => {
  const uiActions = useUIActions();
  const builderActions = useBuilderActions();
  const [name, setName] = useState('');
  const [pinGroup, setPinGroup] = useState(0);
  const pinGroups = useSelector(state => state.builder.pinGroups);

  /**
   *
   */
  useEffect(() => {
    if (library) {
      setName(library.name);
      setPinGroup(library.pinGroup ? library.pinGroup : 0);
    }
  }, [library]);

  /**
   *
   */
  const handleDeleteClick = () => {
    uiActions.confirm('', 'Are you sure you want to delete this pin?', () => {
      builderActions.libraryDelete(library.id);
      onClick();
    });
  };

  /**
   *
   */
  const handleSaveClick = () => {
    builderActions.libraryUpdate(library.id, name, pinGroup);
    onClick();
  };

  const pinGroupOptions = pinGroups.map(pg => ({ value: pg.id, label: pg.name }));
  pinGroupOptions.unshift({ value: 0, label: 'Pin Group' });

  // console.log(library);
  return (
    <Mask onClick={onClick} open>
      <MaskChild animation="zoomIn" clickable>
        <Container>
          <h3 className="mb-2">
            Pin Settings
          </h3>
          <div>
            <Widget>
              <Input
                id="input-library-name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Name"
              />
            </Widget>
            {pinGroups.length > 0 && (
              <Widget>
                <Select
                  id="input-library-pin-group"
                  options={pinGroupOptions}
                  value={pinGroup}
                  onChange={(e) => setPinGroup(Number(e.target.value))}
                />
              </Widget>
            )}
          </div>
          <Flex>
            <Button variant="danger" className="mr-auto" onClick={handleDeleteClick}>
              Delete
            </Button>
            <Button variant="main" className="mr-2" onClick={handleSaveClick}>
              Save
            </Button>
            <Button variant="alt" onClick={onClick}>
              Cancel
            </Button>
          </Flex>
        </Container>
      </MaskChild>
    </Mask>
  );
};

PinSettingsModal.propTypes = {
  library: PropTypes.object.isRequired,
  onClick: PropTypes.func.isRequired,
};

export default PinSettingsModal;
