import React from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import Icon from 'components/Icon';
import { useBuilderActions } from 'builder/actions/builderActions';
import { Container } from './styles';

const AttachedBlockTag = ({ block }) => {
  const builderActions = useBuilderActions();
  const scrollToBlock = useSelector(state => state.builder.scrollToBlock);

  return (
    <div>
      <Container
        role="button"
        className="activity-attached-block mb-2"
        data-block-id={block.id}
        activated={scrollToBlock === block.id}
        onClick={() => builderActions.scrollToBlock(block.id)}
        title="Scroll to block"
      >
        <Icon name="be-symbol-sections" /> {block.title || block.groupName || block.id}
      </Container>
    </div>
  );
};

AttachedBlockTag.propTypes = {
  block: PropTypes.object.isRequired,
};

export default AttachedBlockTag;
