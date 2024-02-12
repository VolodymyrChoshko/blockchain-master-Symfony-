import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import useMe from 'dashboard/hooks/useMe';
import useTemplate from 'dashboard/hooks/useTemplate';
import router from 'lib/router';
import { Container } from './styles';

const Thumbnail = ({ template, peopleCount }) => {
  const me = useMe();
  const [isVisible, setVisible] = useState(true);
  const { people } = useTemplate(template?.id);
  let found = null;
  if (people && people.length > 0) {
    found = people.find(p => p && p.id && (p.id === me.id));
  }
  const canEdit = found && (found.isOwner || found.isAdmin);

  /**
   *
   */
  useEffect(() => {
    // setVisible(false);
  }, [template]);

  if (!template) {
    return null;
  }

  return (
    <Container
      as={canEdit ? 'a' : 'div'}
      href={canEdit ? router.generate('build_template', { id: template.id }) : undefined}
      peopleCount={peopleCount}
      style={{ visibility: isVisible ? 'visible' : 'hidden' }}
    >
      <img
        src={template.thumbnail}
        onLoad={() => setVisible(true)}
        alt=""
      />
    </Container>
  );
};

Thumbnail.propTypes = {
  template:    PropTypes.object,
  peopleCount: PropTypes.number.isRequired
};

export default Thumbnail;
