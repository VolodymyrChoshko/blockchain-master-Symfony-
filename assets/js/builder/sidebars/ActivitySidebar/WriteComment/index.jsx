import React, { useEffect, useRef, useState } from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import emojiCodes, { replaceClassicEmojis } from 'emojiCodes';
import useMe from 'dashboard/hooks/useMe';
import Button from 'components/Button';
import AttachedBlockTag from '../AttachedBlockTag';
import Mentions from './Mentions';
import CharCount from './CharCount';
import Textarea from './Textarea';
import {
  getCaretCoordinates,
  insertAtRange,
  createMentionNode,
  createEmojiNode
} from './utils';
import { Container, TextareaWrap } from './styles';

const maxChars = 1000;

const WriteComment = ({ isReplying, isEditing, initialValue, onComment, onCancel }) => {
  const me = useMe();
  const [comment, setComment] = useState(initialValue);
  const [showMentions, setShowMentions] = useState(false);
  const [mentionHint, setMentionHint] = useState('');
  const [mentionsOffset, setMentionsOffset] = useState({ x: 0, y: 0 });
  const [lastKey, setLastKey] = useState('');
  const [isExpanded, setExpanded] = useState(false);
  const [isShrinking, setShrinking] = useState(false);
  const [isExpandingComplete, setExpandedComplete] = useState(false);
  const attachedBlock = useSelector(state => state.comment.attachedBlock);
  const showingMentionsRef = useRef(false);
  const mentionsCandidateRef = useRef(null);
  /** @type {{ current: HTMLElement }} */
  const inputRef = useRef();
  /** @type {{ current: Range }} */
  const caretRange = useRef(null);
  const startOffset = useRef(0);
  const endOffset = useRef(0);

  /**
   *
   */
  useEffect(() => {
    if (isReplying || attachedBlock) {
      setExpanded(true);
      setExpandedComplete(true);
      setShrinking(false);
      inputRef.current.focus();
    }
  }, [attachedBlock, isReplying]);

  /**
   *
   */
  const closeMentions = () => {
    showingMentionsRef.current = false;
    mentionsCandidateRef.current = null;
    startOffset.current = 0;
    endOffset.current = 0;
    setShowMentions(false);
    setMentionHint('');
  };

  /**
   * @param {{ x: number, y: number }} offset
   */
  const openMentions = (offset) => {
    showingMentionsRef.current = true;
    setMentionsOffset(offset);
    setShowMentions(true);
  };

  /**
   *
   */
  const handleSubmit = () => {
    inputRef.current.querySelectorAll('*[contenteditable]').forEach((el) => {
      el.removeAttribute('contenteditable');
    });

    const html = replaceClassicEmojis(inputRef.current.innerHTML).replace(/<br>/g, '\n');
    onComment(html);
    setComment('');
    inputRef.current.focus();
  };

  /**
   * @param user
   */
  const handleMentionSelect = (user) => {
    const range = caretRange.current;
    range.setStart(range.endContainer, startOffset.current - 1);
    range.setEnd(range.endContainer, endOffset.current);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);

    const node = createMentionNode(user);
    insertAtRange(node, caretRange.current);
    setComment(inputRef.current.innerHTML);

    closeMentions();
  };

  /**
   * @param {Range} range
   */
  const checkForEmoji = (range) => {
    const matches = range.endContainer.textContent.match(/:[\w_]+:/);
    if (matches && emojiCodes[matches[0]] !== undefined) {
      range.setStart(range.endContainer, matches.index);
      range.setEnd(range.endContainer, matches.index + matches[0].length);
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);

      const node = createEmojiNode(matches[0], me);
      insertAtRange(node, caretRange.current);
      setComment(inputRef.current.innerHTML);
    }
  };

  /**
   * @param {Range} range
   */
  const checkForMention = (range) => {
    caretRange.current = range;
    endOffset.current = range.endOffset;
    const char = range.endContainer.textContent[range.endOffset - 1];

    if (showMentions) {
      if (char === '\u00a0') {
        closeMentions();
        return;
      }

      const chars = range.endContainer.textContent.substring(startOffset.current);
      if (chars) {
        const hint = chars.split(' ', 2)[0];
        setMentionHint(hint);
      }
    } else if (char === '@') {
      startOffset.current = range.endOffset;
      const offset = getCaretCoordinates();
      openMentions(offset);
    }
  };

  /**
   * @param e
   */
  const handleChange = (e) => {
    setComment(e.target.value);

    const range = window.getSelection().getRangeAt(0);
    checkForMention(range);
    checkForEmoji(range);
  };

  /**
   * @param {KeyboardEvent} e
   */
  const handleKeyDown = (e) => {
    if ((e.key === 'Enter' || e.key === 'Tab') && showingMentionsRef.current) {
      e.preventDefault();
      if (mentionsCandidateRef.current) {
        handleMentionSelect(mentionsCandidateRef.current);
      }
    } else if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSubmit();
    } else {
      setLastKey(`${e.key}.${Date.now()}`);
    }
  };

  /**
   *
   */
  const handleClick = () => {
    const range = window.getSelection().getRangeAt(0);
    checkForMention(range);

    if (!isExpanded) {
      setExpanded(true);
      setShrinking(false);
      setTimeout(() => {
        setExpandedComplete(true);
      }, 500);
    }
  };

  /**
   *
   */
  const handleBlur = () => {
    if (isExpanded && inputRef.current.innerHTML === '') {
      setExpandedComplete(false);
      setTimeout(() => {
        setShrinking(true);
      }, 10);
      setTimeout(() => {
        setExpanded(false);
      }, 500);
    }
  };

  /**
   * @param user
   */
  const handleMentionClick = (user) => {
    endOffset.current = caretRange.current.endOffset;
    handleMentionSelect(user);
  };

  return (
    <Container className="activity-write-comment">
      <TextareaWrap className="mb-3">
        <Textarea
          value={comment}
          innerRef={inputRef}
          expanded={isExpanded}
          shrinking={isShrinking}
          expandedComplete={isExpandingComplete}
          onChange={handleChange}
          onClick={handleClick}
          onBlur={handleBlur}
          onKeyDown={handleKeyDown}
        />
        <CharCount maxChars={maxChars} inputRef={inputRef} />
      </TextareaWrap>
      {attachedBlock && !isReplying && !isEditing && (
        <AttachedBlockTag block={attachedBlock} />
      )}
      <Button
        variant="main"
        onClick={() => {
          inputRef.current.dispatchEvent(new KeyboardEvent('keydown', { 'key': 'Enter' }));
          handleSubmit();
        }}
      >
        {isEditing ? 'Update' : 'Post'}
      </Button>
      {(isReplying || isEditing) && (
        <Button
          variant="alt"
          className="ml-2"
          onClick={() => {
            setComment('');
            onCancel();
          }}
        >
          Cancel
        </Button>
      )}
      {showMentions && (
        <Mentions
          element={inputRef.current}
          offset={mentionsOffset}
          hint={mentionHint}
          lastKey={lastKey}
          candidate={mentionsCandidateRef}
          onClose={() => setShowMentions(false)}
          onSelect={handleMentionClick}
        />
      )}
    </Container>
  );
};

WriteComment.propTypes = {
  initialValue: PropTypes.string,
  isReplying:   PropTypes.bool.isRequired,
  isEditing:    PropTypes.bool,
  onComment:    PropTypes.func.isRequired,
  onCancel:     PropTypes.func,
};

WriteComment.defaultProps = {
  initialValue: '',
};

export default WriteComment;
