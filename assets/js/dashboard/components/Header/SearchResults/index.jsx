import React from 'react';
import PropTypes from 'prop-types';
import Email from 'dashboard/pages/Templates/Email';
import { Container } from './styles';

const SearchResults = ({ searchResults }) => {
  return (
    <Container className="db-search-results p-0" shadow>
      {searchResults.map(email => (
        <Email
          key={email.id}
          email={email}
          depth={0}
          template={email.template}
          onDuplicate={() => {}}
          showControls={false}
          draggable={false}
          showTemplate
          canEdit
        />
      ))}
    </Container>
  );
};

SearchResults.propTypes = {
  searchResults: PropTypes.array.isRequired
};

export default SearchResults;
