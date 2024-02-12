import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { Pane } from 'builder/sidebars/ActivitySidebar/styles';
import { useCommentActions } from 'builder/actions/commentActions';
import WriteComment from 'builder/sidebars/ActivitySidebar/WriteComment';
import Comment from './Comment';

const CommentItem = ({ comment, highlighted, onStatusClick, onScrollTop, setHighlighted ,fProcessName }) => {
  const commentActions = useCommentActions();
  const [isReplying, setReplying] = useState(false);

  /**
   * @param content
   */
  const handleReplySend = (content) => {
    commentActions.addReply(comment.id, content);
    setReplying(false);
    onScrollTop();
  };

  return (
    <Pane
      highlighted={highlighted === comment.id}
      onClick={() => {
        if (highlighted) {
          setHighlighted(0);
        }
      }}
    >
      <Comment
        comment={comment}
        onReply={() => setReplying(!isReplying)}
        onStatusClick={onStatusClick}
        replying={isReplying}
        fProcessName={fProcessName}
      />
      {isReplying && (
        <div className="mt-2">
          <WriteComment
            onComment={handleReplySend}
            onCancel={() => setReplying(false)}
            isReplying
          />
        </div>
      )}
    </Pane>
  );
};

CommentItem.propTypes = {
  comment: PropTypes.object.isRequired,
  setHighlighted: PropTypes.func.isRequired,
  highlighted: PropTypes.number.isRequired,
  onStatusClick: PropTypes.func.isRequired,
  onScrollTop: PropTypes.func.isRequired,
};

CommentItem.defaultProps = {};

export default CommentItem;
