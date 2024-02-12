import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { useBuilderActions } from 'builder/actions/builderActions';
import { useUIActions } from 'builder/actions/uiActions';
import Mask from 'components/Mask';
import MaskChild from 'components/MaskChild';
import Input from 'components/forms/Input';
import Widget from 'components/forms/Widget';
import Button from 'components/Button';
import Flex from 'components/Flex';
import { Container } from './styles';

const PinGroupSettingsModal = ({ pinGroup, onClick }) => {
  const uiActions = useUIActions();
  const builderActions = useBuilderActions();
  const [name, setName] = useState('');

  /**
   *
   */
  useEffect(() => {
    if (pinGroup) {
      setName(pinGroup.name);
    }
  }, [pinGroup]);

  /**
   *
   */
  const handleDeleteClick = () => {
    uiActions.confirm('', 'Are you sure you want to delete this pin group? This action does not delete the pins in the group.', () => {
      builderActions.pinGroupDelete(pinGroup.id);
      onClick();
    });
  };

  /**
   *
   */
  const handleSaveClick = () => {
    builderActions.pinGroupUpdate(pinGroup.id, name);
    onClick();
  };

  return (
    <Mask onClick={onClick} open>
      <MaskChild animation="zoomIn" clickable>
        <Container>
          <h3 className="mb-2">
            Pin Group Settings
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

PinGroupSettingsModal.propTypes = {
  pinGroup: PropTypes.object.isRequired,
  onClick:  PropTypes.func.isRequired,
};

export default PinGroupSettingsModal;
