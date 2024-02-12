import React from 'react';
import { useSelector } from 'react-redux';
import { ButtonGroup, Icon } from 'components';
import useMe from 'dashboard/hooks/useMe';

const TemplatePreviewButtons = () => {
  const me = useMe();
  const mode = useSelector(state => state.builder.mode);

  return (
    <div className="builder-header-buttons d-flex align-items-center text-left mr-2" style={{ right: me ? '' : 15 }}>
      <ButtonGroup>
        <div>
          <Icon name="be-symbol-view" mr /> Preview Mode
        </div>
        {(mode.indexOf('email') === -1) && (
          <div>
            Click content areas to make edits. Changes are not saved.
          </div>
        )}
      </ButtonGroup>
    </div>
  );
};

export default TemplatePreviewButtons;
