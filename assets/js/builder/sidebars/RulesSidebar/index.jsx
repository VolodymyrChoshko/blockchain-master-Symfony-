import React from 'react';
import { useSelector } from 'react-redux';
import BlockEdit from './BlockEdit';
import BlockSection from './BlockSection';
import BlockComponent from './BlockComponent';
import BlockRegions from './BlockRegions';
import { Container, Inner } from './styles';

const RulesSidebar = () => {
  const rules = useSelector(state => state.rules);

  return (
    <Container open={!rules.isEditingHtml}>
      <Inner>
        {!rules.isEditingHtml && rules.mode === 'editable' && (
          <BlockEdit />
        )}
        {!rules.isEditingHtml && rules.mode === 'sections' && (
          <BlockSection />
        )}
        {!rules.isEditingHtml && rules.mode === 'regions' && (
          <BlockRegions />
        )}
        {!rules.isEditingHtml && rules.mode === 'components' && (
          <BlockComponent />
        )}
      </Inner>
    </Container>
  );
};

export default RulesSidebar;
