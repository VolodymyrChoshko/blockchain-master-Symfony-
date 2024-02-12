import React from 'react';
import { useSelector } from 'react-redux';
import { useUIActions } from 'builder/actions/uiActions';
import { useIntegrationsActions } from 'dashboard/actions/integrationsActions';
import Box from 'dashboard/components/Box';
import Button from 'components/Button';
import { Container, Card, Icon, Name } from './styles';

const Add = () => {
  const integrations = useSelector(state => state.integrations.integrations);
  const isIntegrationsDisabled = useSelector(state => state.integrations.isIntegrationsDisabled);
  const uiActions = useUIActions();
  const integrationsActions = useIntegrationsActions();

  /**
   * @param it
   */
  const handleAddClick = (it) => {
    integrationsActions.add(it.slug, (source) => {
      uiActions.modal('integrationSettings', true, {
        source
      });
    });
  };

  return (
    <Box.Section className="dark no-border-top">
      <h2 className="mb-3">
        Add another integration
      </h2>
      <Container>
        {integrations.map(it => (
          <Card key={it.slug}>
            <Icon src={it.iconURL} alt="Icon" />
            <Name>
              {it.displayName}<br />
              <a href={it.instructionsURL} target="_blank" rel="noopener noreferrer">
                Setup Instructions &rarr;
              </a>
            </Name>
            {isIntegrationsDisabled && (
              <Button variant="main" disabled>Add</Button>
            )}
            {!isIntegrationsDisabled && (
              <>
                {!it.canEnable ? (
                  <Button variant="main" disabled>Added</Button>
                ) : (
                  <Button
                    as="a"
                    variant="main"
                    onClick={() => handleAddClick(it)}
                  >
                    Add
                  </Button>
                )}
              </>
            )}
          </Card>
        ))}
      </Container>
    </Box.Section>
  );
};

export default Add;
