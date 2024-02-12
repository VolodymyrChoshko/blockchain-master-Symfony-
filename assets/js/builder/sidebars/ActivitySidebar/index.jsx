import React, { useEffect, useState, useMemo, useRef } from 'react';
import useMe from 'dashboard/hooks/useMe';
import { useSelector } from 'react-redux';
import { useUIActions } from 'builder/actions/uiActions';
import { useCommentActions } from 'builder/actions/commentActions';
import { Scrollbars } from 'react-custom-scrollbars';
import { formatDate } from 'utils/dates';
import { onLocationChange } from 'lib/history';
import renderScrollbars from 'utils/scrollbars';
import SlideoutSidebar from 'builder/components/SlideoutSidebar';
import ErrorBoundary from 'components/ErrorBoundary';
import PopupMenuProvider from 'components/PopupMenuProvider';
import Avatar from 'dashboard/components/Avatar';
import SidebarTitle from 'builder/components/SidebarTitle';
import CommentItem from './CommentItem';
import HistoryItem from './HistoryItem';
import WriteComment from './WriteComment';
import { Container, Inner, Panel, Pane } from './styles';

// https://unicode.org/emoji/charts/full-emoji-list.html
const ActivitySidebar = () => {
  const me = useMe();
  const commentActions = useCommentActions();
  const uiActions = useUIActions();
  const isActivityOpen = useSelector(state => state.ui.isActivityOpen);
  const comments = useSelector(state => state.comment.comments);
  const history = useSelector(state => state.history.history);
  const zIndexActivity = useSelector(state => state.ui.zIndexActivity);
  const iframeReady = useSelector(state => state.builder.iframeReady);
  const mode = useSelector(state => state.builder.mode);
  const [highlighted, setHighlighted] = useState(0);
  const [highlightedType, setHighlightedType] = useState('');
  const prevCommentCount = useRef(comments.length);
  const highlightLock = useRef(false);
  const scrollbars = useRef();

  function processName(userName){
    let wordArray = userName.trim().split(' ');
    if(wordArray.length > 2 && wordArray[1] != undefined) {
      return `${wordArray[0]} ${wordArray[1][0]}.`
    }
    else if (wordArray.length == 2 && wordArray[1] != undefined) {
      return `${wordArray[0]} ${wordArray[1][0]}.`
    }
    else {
      return wordArray[0];
    }
  }

  /**
   *
   */
  const handleLocationChange = () => {
    if (iframeReady && document.location.hash.indexOf('#activity-') === 0) {
      uiActions.toggleActivity(true);
      const parts = document.location.hash.split('-', 3);
      if (parts.length === 3) {
        let el;
        if (parts[1] === 'h') {
          el = document.querySelector(`.activity-history[data-history-id="${parts[2]}"]`);
        } else {
          el = document.querySelector(`.activity-comment[data-comment-id="${parts[2]}"]`);
        }
        if (el) {
          setHighlighted(Number(parts[2]));
          setHighlightedType(parts[1] === 'h' ? 'history' : 'comment');
          scrollbars.current.view.scroll({
            top:      el.offsetTop - 8,
            left:     0,
            behavior: 'smooth'
          });
        }
      }
    }
  };

  /**
   *
   */
  useEffect(() => {
    handleLocationChange();
  }, [iframeReady]);

  /**
   * Wires up event listening for location changes.
   */
  useEffect(() => {
    return onLocationChange(handleLocationChange);
  }, [iframeReady]);

  /**
   *
   */
  useEffect(() => {
    if (scrollbars.current && comments.length > prevCommentCount.current) {
      scrollbars.current.view.scroll({
        top:      0,
        left:     0,
        behavior: 'smooth'
      });
    }
    prevCommentCount.current = comments.length;
  }, [comments]);

  /**
   *
   */
  const commentDayMap = useMemo(() => {
    const map = {};
    const dates = [];

    for (let i = 0; i < comments.length; i++) {
      comments[i].date = new Date(comments[i].dateCreated * 1000);
      dates.push(comments[i].date);
    }
    for (let i = 0; i < history.length; i++) {
      history[i].date = new Date(history[i].dateCreated * 1000);
      dates.push(history[i].date);
    }

    const sortedDates = dates.sort((a, b) => {
      return a > b ? -1 : 1;
    });
    for (let i = 0; i < sortedDates.length; i++) {
      const day = formatDate(sortedDates[i]);
      if (!map[day]) {
        map[day] = [];
      }
    }

    for (let i = 0; i < comments.length; i++) {
      const day = formatDate(comments[i].date);
      map[day].push(comments[i]);
    }
    for (let i = 0; i < history.length; i++) {
      const day = formatDate(history[i].date);
      map[day].push(history[i]);
    }

    Object.keys(map).forEach((key) => {
      map[key] = map[key].sort((a, b) => {
        return a.dateCreated > b.dateCreated ? -1 : 1;
      });
    });

    return map;
  }, [comments, history]);

  /**
   *
   */
  const handlePostComment = (comment) => {
    const c = comment.trim();
    if (c) {
      commentActions.addComment(c);
    }
  };

  /**
   * @param comment
   */
  const handleStatusClick = (comment) => {
    let el;
    if (comment.version !== undefined) {
      el = document.querySelector(`.activity-history[data-history-id="${comment.id}"]`);
    } else {
      el = document.querySelector(`.activity-comment[data-comment-id="${comment.id}"]`);
    }
    if (el) {
      highlightLock.current = true;
      setTimeout(() => highlightLock.current = false, 1000);
      setHighlighted(comment.id);
      setHighlightedType(comment.version !== undefined ? 'history' : 'comment');
      scrollbars.current.view.scroll({
        top:      el.offsetTop - 8,
        left:     0,
        behavior: 'smooth'
      });

      document.location.hash = `#activity-${comment.version !== undefined ? 'h' : 'c'}-${comment.id}`;
    }
  };

  /**
   *
   */
  const handleSetHighlighted = () => {
    if (highlighted && !highlightLock.current) {
      setHighlighted(0);
      window.history.replaceState('', '', document.location.pathname);
    }
  };

  /**
   *
   */
  const handleScrollTop = () => {
    scrollbars.current.view.scroll({
      top:      0,
      left:     0,
      behavior: 'smooth'
    });
  };

  if (mode.indexOf('preview') !== -1) {
    return null;
  }

  return (
    <SlideoutSidebar zIndex={zIndexActivity} open={isActivityOpen}>
      <PopupMenuProvider>
        <ErrorBoundary>
          <Container>
            {mode.indexOf('email') === 0 && (
              <Panel>
                <SidebarTitle style={{ borderTop: 0, textTransform: 'none' }}>
                  Activity of updates made to this email.
                </SidebarTitle>
                <Pane className="pb-3">
                  <div className="d-flex align-items-center mb-2">
                    <Avatar user={me} className="mr-2" md />
                    <div>
                      Comment
                    </div>
                  </div>

                  <WriteComment
                    onComment={handlePostComment}
                    isReplying={false}
                  />
                </Pane>
              </Panel>
            )}

            <Inner>
              <Scrollbars
                autoHide
                ref={scrollbars}
                renderTrackHorizontal={renderScrollbars.renderTrackHorizontal}
                renderThumbHorizontal={renderScrollbars.renderThumbHorizontal}
                onScrollStart={() => commentActions.scroll(true)}
                onScrollStop={() => commentActions.scroll(false)}
              >
                {Object.keys(commentDayMap).map((key) => (
                  <Panel key={key}>
                    <SidebarTitle>
                      {key.toUpperCase()}
                    </SidebarTitle>
                    {commentDayMap[key].map((c) => {
                      if (c.version !== undefined) {
                        return (
                          <HistoryItem
                            key={c.id}
                            history={c}
                            highlighted={highlightedType === 'history' ? highlighted : 0}
                            onStatusClick={handleStatusClick}
                            setHighlighted={handleSetHighlighted}
                            fProcessName={processName}
                          />
                        );
                      }

                      return (
                        <CommentItem
                          key={c.id}
                          comment={c}
                          highlighted={highlightedType === 'comment' ? highlighted : 0}
                          onStatusClick={handleStatusClick}
                          onScrollTop={handleScrollTop}
                          setHighlighted={handleSetHighlighted}
                          fProcessName={processName}
                        />
                      );
                    })}
                  </Panel>
                ))}
              </Scrollbars>
            </Inner>
          </Container>
        </ErrorBoundary>
      </PopupMenuProvider>
    </SlideoutSidebar>
  );
};

export default ActivitySidebar;
