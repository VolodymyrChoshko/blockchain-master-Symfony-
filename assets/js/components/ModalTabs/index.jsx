import React from 'react';
import PropTypes from 'prop-types';
import { Scrollbars } from 'react-custom-scrollbars';
import renderScrollbars from 'utils/scrollbars';

const ModalTabs = ({ selected, onChange, items }) => {
  return (
    <Scrollbars
      renderTrackHorizontal={renderScrollbars.renderTrackHorizontal}
      renderThumbHorizontal={renderScrollbars.renderThumbHorizontal}
      style={{ height: 68 }}
      autoHide
    >
      <div className="modal-tabs">
        {items.map(item => (
          <div
            key={item.value}
            onClick={e => onChange(e, item.value)}
            className={selected === item.value ? 'active d-flex align-items-center' : 'd-flex align-items-center'}
          >
            {item.icon && (
              <img src={item.icon} className="mr-2" alt="" style={{ height: 25 }} />
            )}
            {item.label}
          </div>
        ))}
      </div>
    </Scrollbars>
  );
};

ModalTabs.propTypes = {
  items:    PropTypes.array.isRequired,
  selected: PropTypes.string.isRequired,
  onChange: PropTypes.func.isRequired
};

export default ModalTabs;
