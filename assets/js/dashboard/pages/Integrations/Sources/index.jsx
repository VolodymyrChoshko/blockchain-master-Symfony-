import React from 'react';
import { useSelector } from 'react-redux';
import { useIntegrationsActions } from 'dashboard/actions/integrationsActions';
import { useUIActions } from 'builder/actions/uiActions';
import Button from 'components/Button';
import Icon from 'components/Icon';
import { Table } from './styles';

const Sources = () => {
  const sources = useSelector(state => state.integrations.sources);
  const integrationsActions = useIntegrationsActions();
  const uiActions = useUIActions();

  /**
   * @param source
   */
  const handleRemoveClick = (source) => {
    uiActions.confirm('', 'Are you sure you want to remove this integration?', () => {
      integrationsActions.removeSource(source);
    });
  };

  /**
   * @param source
   */
  const handleEditClick = (source) => {
    uiActions.modal('integrationSettings', true, {
      source
    });
  };

  return (
    <Table className="w-100">
      <tbody>
        {sources.map(s => (
          <tr key={s.id}>
            <td className="integration-col-icon">
              <img src={s.integration.iconURL} alt="Icon" className="integration-icon" />
              <span>{s.name}</span>
            </td>
            <td className="integration-col-name">
              {s.integration.displayName}
            </td>
            <td className="integration-col-actions text-nowrap d-flex align-items-center">
              <Button variant="alt" onClick={() => handleEditClick(s)}>
                Edit
              </Button>
              <Button variant="transparent" className="d-flex align-items-center" onClick={() => handleRemoveClick(s)}>
                <Icon name="be-symbol-delete" className="mr-2" />
                Remove
              </Button>
            </td>
          </tr>
        ))}
      </tbody>
    </Table>
  );
};

export default Sources;
