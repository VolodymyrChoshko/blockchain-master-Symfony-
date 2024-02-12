import React from 'react';
import PropTypes from 'prop-types';
import FancySelect from 'components/forms/FancySelect';

const SelectOrgs = ({ organizations }) => {
  /**
   * @param {Event} e
   * @param {string} value
   */
  const handleChange = (e, value) => {
    const id = parseInt(value, 10);
    for (let i = 0; i < organizations.length; i++) {
      if (organizations[i].org_id === id) {
        document.location = organizations[i].domain;
        break;
      }
    }
  };

  const options = organizations.map((org) => {
    const option = { value: org.id, label: org.name, href: org.domain };
    if (org.isOwner) {
      option.isStar = true;
    }
    return option;
  });

  return (
    <FancySelect
      id="header-select-orgs"
      label="Select organization."
      options={options}
      initialValue={window.oid}
      onChange={handleChange}
    />
  );
};

SelectOrgs.propTypes = {
  organizations: PropTypes.array.isRequired
};

export default SelectOrgs;
