import React from 'react';
import PropTypes from 'prop-types';
import { useChecklistActions } from 'builder/actions/checklistActions';
import { Item, Title, Description, CustomCheckbox } from './styles';

const ChecklistItem = ({ item }) => {
  const checklistActions = useChecklistActions();

  return (
    <Item
      key={item.id}
      className="d-flex pt-2 pr-2 pb-2"
      style={{ paddingLeft: '0.75rem' }}
    >
      <div>
        <CustomCheckbox
          id={`item-${item.id}`}
          checked={item.checked}
          label={<span className="checkmark" />}
          onChange={(e) => {
            checklistActions.check(item.key, e.target.checked);
          }}
        />
      </div>
      <div className="d-flex flex-column">
        <Title checked={item.checked}>
          {item.title}
        </Title>
        <Description checked={item.checked}>
          {item.description}
        </Description>
      </div>
    </Item>
  );
};

ChecklistItem.propTypes = {
  item: PropTypes.object.isRequired
};

export default ChecklistItem;
