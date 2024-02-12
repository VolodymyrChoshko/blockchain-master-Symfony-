import React, { useRef } from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import { useUIActions } from 'builder/actions/uiActions';
import { useRuleActions } from 'builder/actions/ruleActions';
import { useBuilderActions } from 'builder/actions/builderActions';
import { Button, ButtonGroup, Icon } from 'components';

const TemplateButtons = ({ builder }) => {
  const uiActions = useUIActions();
  const ruleActions = useRuleActions();
  const builderActions = useBuilderActions();
  const isEditing = useSelector(state => state.rules.isEditing);
  const fileRef = useRef(null);

  /**
   * @param {Event} e
   */
  const handleFileChange = (e) => {
    const { files } = e.target;

    builderActions.uploadNewVersion(files[0]);
  };

  return (
    <div className="builder-header-buttons d-flex align-items-center">
      <ButtonGroup className="text-nowrap d-flex align-items-center">
        {/* <div className="btn">
          <Icon name="view" mr /> Preview Mode
        </div> */}
        {(builder.isOwner || builder.isAdmin) && (
          <>
            {isEditing ? (
              <Button
                variant="save"
                className="mb-0 mr-2"
                onClick={() => ruleActions.setEditing(false)}
              >
                SAVE
              </Button>
            ) : (
              <Button
                variant="edit"
                className="mb-0 mr-2"
                onClick={() => ruleActions.setEditing(true)}
              >
                EDIT
              </Button>
            )}
          </>
        )}
        {isEditing && (
          <Button
            variant="transparent"
            className="mb-0 d-inline-flex align-items-center"
            onClick={() => ruleActions.cancelEditing()}
          >
            <Icon name="be-symbol-delete" mr />
            Cancel Changes
          </Button>
        )}
        {!isEditing && (builder.isOwner || builder.isAdmin) && (
          <Button variant="transparent" className="mb-0" onClick={() => fileRef.current.click()}>
            <Icon name="be-symbol-plus" className="builder-header-icon" mr />
            Import New Version
          </Button>
        )}
        {!isEditing && (
          <Button variant="transparent" className="mb-0" onClick={() => uiActions.modal('share', true)}>
            <Icon name="be-symbol-share" className="builder-header-icon" mr />
            Share &amp; Export
          </Button>
        )}
      </ButtonGroup>
      <input
        ref={fileRef}
        type="file"
        accept=".html,.zip"
        className="form-hidden-file-input"
        onChange={handleFileChange}
      />
    </div>
  );
};

TemplateButtons.propTypes = {
  builder: PropTypes.object.isRequired,
};

export default TemplateButtons;
