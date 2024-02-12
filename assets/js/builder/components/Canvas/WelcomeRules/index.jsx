import Button from 'components/Button';
import React from 'react';
import { useSelector } from 'react-redux';
import { useBuilderActions } from 'builder/actions/builderActions';
import Box from 'dashboard/components/Box';
import { Container } from './styles';

const WelcomeRules = () => {
  const builderActions = useBuilderActions();
  const isFirstRulesEdit = useSelector(state => state.builder.isFirstRulesEdit);

  if (!isFirstRulesEdit) {
    return null;
  }

  return (
    <Container>
      <Box narrow shadow white>
        Welcome to the template editor. Here you can select and enable editing options for
        your team to build individual emails and add in content.
        <div className="mt-2 text-right">
          <Button variant="main" onClick={() => builderActions.setFirstRulesEdit(false)}>
            Close
          </Button>
        </div>
      </Box>
    </Container>
  );
};

export default WelcomeRules;
