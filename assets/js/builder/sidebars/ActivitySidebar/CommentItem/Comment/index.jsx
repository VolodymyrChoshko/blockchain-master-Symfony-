import React, { useEffect, useMemo, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import useMe from 'dashboard/hooks/useMe';
import { useSelector } from 'react-redux';
import { useCommentActions } from 'builder/actions/commentActions';
import { formatTime } from 'utils/dates';
import Markdown from 'components/Markdown';
import WriteComment from 'builder/sidebars/ActivitySidebar/WriteComment';
import PersonModal from 'components/PersonModal';
import Avatar from 'dashboard/components/Avatar';
import Button from 'components/Button';
import Icon from 'components/Icon';
import Emoji from 'components/Emoji';
import PopupMenu from 'components/PopupMenu';
import AttachedBlockTag from '../../AttachedBlockTag';
import EmojiMenu from './EmojiMenu';
import EmojiUsers from './EmojiUsers';
import {
  Body,
  Emojis,
  EmojiWrap,
  Footer,
  Status,
  Time,
  ParentWrapper,
  Name
} from './styles';

const Comment = ({ comment, replying, onReply, onStatusClick,fProcessName }) => {
  const me = useMe();
  const commentActions = useCommentActions();
  const comments = useSelector(state => state.comment.comments);
  const blocks = useSelector(state => state.builder.blocks);
  const [modalUser, setModalUser] = useState(null);
  const [isEditing, setEditing] = useState(false);
  const [deleteElement, setDeleteElement] = useState(null);
  const [emojisList, setEmojisList] = useState(null);
  const [emojiMenuElement, setEmojiMenuElement] = useState(null);
  const [emojiUsersElement, setEmojiUsersElement] = useState(null);

  const isScrolling = useSelector(state => state.comment.isScrolling);
  const emojiListTimeoutRef = useRef(0);
  /** @type {{ current: HTMLElement }} */
  const bodyRef = useRef();
  /** @type {{ current: HTMLElement }} */
  const parentBodyRef = useRef();
  const dateCreated = new Date(comment.dateCreated * 1000);

  /**
   *
   */
  useEffect(() => {
    if (isScrolling) {
      if (emojiMenuElement) {
        setEmojiMenuElement(null);
      }
      if (emojiUsersElement) {
        setEmojiUsersElement(null);
      }
    }
  }, [emojiMenuElement, emojiUsersElement, isScrolling]);

  /**
   *
   */
  useEffect(() => {
    if (bodyRef.current) {
      const avatars = bodyRef.current.querySelectorAll('.activity-avatar-sm');
      avatars.forEach((el) => {
        el.classList.add('pointer');
        // eslint-disable-next-line no-use-before-define
        el.addEventListener('click', handleActivityAvatarClick, false);
      });
    }
    if (parentBodyRef.current) {
      const avatars = parentBodyRef.current.querySelectorAll('.activity-avatar-sm');
      avatars.forEach((el) => {
        el.classList.add('pointer');
        // eslint-disable-next-line no-use-before-define
        el.addEventListener('click', handleActivityAvatarClick, false);
      });
    }
  }, [comment]);

  /**
   * @type {{}}
   */
  const emojis = useMemo(() => {
    const emojis_ = {};
    for (let i = 0; i < comment.emojis.length; i++) {
      const e = comment.emojis[i];
      e.tone = e.user.skinTone;
      const key = `${e.code}-${e.tone}`;
      if (!emojis_[key]) {
        emojis_[key] = [];
      }
      emojis_[key].push(e);
    }

    return emojis_;
  }, [comment, comment.emojis.length]);

  /**
   * @type {unknown}
   */
  const parent = useMemo(() => {
    if (!comment.parent) {
      return null;
    }

    for (let i = 0; i < comments.length; i++) {
      if (comments[i].id === comment.parent) {
        return comments[i];
      }
    }

    return null;
  }, [comment, comments]);

  /**
   * @param {MouseEvent} e
   */
  const handleActivityAvatarClick = (e) => {
    let userInfoRaw = e.currentTarget.getAttribute('data-user-info');
    if (userInfoRaw) {
      userInfoRaw = JSON.parse(decodeURIComponent(userInfoRaw.replace(/\+/g, '%20')));
      if (userInfoRaw) {
        setModalUser(userInfoRaw);
      }
    }
  };

  /**
   *
   */
  const handleEmojiMenuClick = (e) => {
    setEmojiMenuElement(e.currentTarget);
  };

  /**
   * @param e
   * @param emojiMap
   */
  const handleEmojiUsersClick = (e, emojiMap) => {
    let found = false;
    for (let i = 0; i < emojiMap.length; i++) {
      if (emojiMap[i].user.id === me.id) {
        found = emojiMap[i];
      }
    }

    if (!found) {
      setEmojisList(emojiMap);
      setEmojiUsersElement(e.currentTarget);
    } else {
      clearTimeout(emojiListTimeoutRef.current);
      commentActions.removeEmoji(comment.id, found.uuid);
    }
  };

  /**
   * @param e
   * @param emojiMap
   */
  const handleEmojiUsersOver = (e, emojiMap) => {
    const element = e.currentTarget;
    emojiListTimeoutRef.current = setTimeout(() => {
      setEmojisList(emojiMap);
      setEmojiUsersElement(element);
    }, 750);
  };

  /**
   *
   */
  const handleEmojiUsersOut = () => {
    clearTimeout(emojiListTimeoutRef.current);
    setTimeout(() => {
      // setEmojiUsersElement(null);
    }, 5000);
  };

  /**
   *
   */
  const handleEditClick = () => {
    setEditing(!isEditing);
  };

  /**
   * @param {string} content
   */
  const handleEditSubmit = (content) => {
    commentActions.updateComment(comment.id, content);
    setEditing(false);
  };

  /**
   * @param e
   */
  const handleDeleteClick = (e) => {
    setDeleteElement(e.currentTarget);
  };

  let block = null;
  let parentBlock = null;
  if (comment.blockId) {
    block = blocks.getByID(comment.blockId);
  }
  if (parent && parent.blockId) {
    parentBlock = blocks.getByID(parent.blockId);
  }

  let status = 'Commented';
  if (comment.status === 'checked') {
    status = 'Checked off an item';
  } else if (comment.status === 'unchecked') {
    status = 'Unchecked an item';
  }

  return (
    <div className="activity-comment" data-comment-id={comment.id}>
      <div className="d-flex align-items-start">
        <div className="d-flex align-items-center">
          <Avatar
            user={comment.user}
            className="pointer mr-2"
            onClick={() => setModalUser(comment.user)}
            md
          />
          <div className="d-flex flex-column align-items-start">
          
            <Name>{fProcessName(comment.user.name)}</Name>

            <Status className="mt-1" onClick={() => onStatusClick(comment)}>
              {status}
            </Status>
          </div>
        </div>
        <Time className="ml-auto">
          {formatTime(dateCreated)}
        </Time>
      </div>

      {parent && (
        <ParentWrapper>
          <div className="d-flex align-items-center">
            <Avatar
              user={parent.user}
              className="pointer mr-2"
              onClick={() => setModalUser(parent.user)}
              sm
            />
            <div className="d-flex align-items-center">
            <Name>{fProcessName(parent.user.name)}</Name>
            
              
              <Status className="ml-1" onClick={() => onStatusClick(parent)}>
                Commented
              </Status>
            </div>
          </div>

          <Body ref={parentBodyRef} className="pt-2">
            <Markdown markdown={parent.content} />
          </Body>

          {parentBlock && (
            <AttachedBlockTag block={parentBlock} />
          )}
        </ParentWrapper>
      )}

      <Body ref={bodyRef} className={parent ? 'pt-1 pb-1' : 'pt-2 pb-1'}>
        {isEditing ? (
          <>
            <WriteComment
              isReplying={false}
              initialValue={comment.content}
              onComment={handleEditSubmit}
              onCancel={() => setEditing(false)}
              isEditing
            />
          </>
        ) : (
          <Markdown markdown={comment.content} />
        )}
      </Body>

      {block && (
        <AttachedBlockTag block={block} />
      )}

      {comment.emojis.length > 0 && (
        <Emojis className="emoji-wrapper mb-1">
          {Object.keys(emojis).map((key) => (
            <EmojiWrap
              key={key}
              onClick={(e) => handleEmojiUsersClick(e, emojis[key])}
              onMouseEnter={(e) => handleEmojiUsersOver(e, emojis[key])}
              onMouseLeave={handleEmojiUsersOut}
            >
              <Emoji code={key.split('-')[0]} tone={emojis[key][0].tone} />
              {emojis[key].length > 1 && (
                <i>{emojis[key].length}</i>
              )}
            </EmojiWrap>
          ))}
        </Emojis>
      )}

      <Footer>
        <button
          title="Emoji"
          onClick={handleEmojiMenuClick}
          className={emojiMenuElement !== null ? 'selected mr-auto' : 'mr-auto'}
        >
          <Icon name="be-symbol-smile" />
        </button>
        <button
          title="Reply"
          onClick={onReply}
          className={replying ? 'selected' : ''}
        >
          <Icon name="be-symbol-reply" />
        </button>
        {comment.user.id === me.id && (
          <button
            title="Edit"
            onClick={handleEditClick}
            className={isEditing ? 'selected' : ''}
          >
            <Icon name="be-symbol-edit" />
          </button>
        )}
        {comment.user.id === me.id && (
          <button
            title="Delete"
            onClick={handleDeleteClick}
            className={deleteElement !== null ? 'selected' : ''}
          >
            <Icon name="be-symbol-delete" />
          </button>
        )}
      </Footer>

      <EmojiMenu
        name={comment.id}
        element={emojiMenuElement}
        onClose={() => setEmojiMenuElement(null)}
        onSelect={(code) => {
          setEmojiMenuElement(null);
          commentActions.addEmoji(comment.id, code);
        }}
      />
      <EmojiUsers
        name={comment.id}
        emojis={emojisList}
        element={emojiUsersElement}
        onClose={() => setEmojiUsersElement(null)}
      />
      <PopupMenu
        element={deleteElement}
        onClose={() => setDeleteElement(null)}
        name={comment.id}
        position="top"
        className="p-3"
      >
        <p>Are you sure?</p>
        <Button
          sm
          variant="danger"
          className="mr-2"
          onClick={() => {
            commentActions.deleteComment(comment.id);
            setDeleteElement(null);
          }}
        >
          Delete
        </Button>
        <Button variant="alt" onClick={() => setDeleteElement(null)} sm>
          Cancel
        </Button>
      </PopupMenu>
      {modalUser && (
        <PersonModal user={modalUser} onClose={() => setModalUser(null)} />
      )}
    </div>
  );
};

Comment.propTypes = {
  comment:       PropTypes.object.isRequired,
  replying:      PropTypes.bool,
  onReply:       PropTypes.func.isRequired,
  onStatusClick: PropTypes.func.isRequired
};

export default Comment;
