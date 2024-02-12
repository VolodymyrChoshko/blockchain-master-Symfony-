import React from 'react';
import PropTypes from 'prop-types';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import Icon from 'components/Icon';
import { Container } from './styles';

const Notice = ({ notice, closeable, children }) => {
  const templateActions = useTemplateActions();

  /**
   *
   */
  const handleCloseClick = () => {
    if (notice) {
      templateActions.closeNotice(notice.id);
    } else {
      templateActions.closeNotice();
    }
  };

  return (
    <Container className="font-size mt-4 mb-4" white>
      {closeable && (
        <Icon name="be-symbol-delete" title="Close" onClick={handleCloseClick} />
      )}
      {notice ? (
        <div dangerouslySetInnerHTML={{ __html: notice.content }} />
      ) : children}
    </Container>
  );
};

Notice.propTypes = {
  notice:    PropTypes.object,
  closeable: PropTypes.bool,
  children:  PropTypes.node
};

Notice.defaultProps = {
  closeable: true,
};

export default Notice;
