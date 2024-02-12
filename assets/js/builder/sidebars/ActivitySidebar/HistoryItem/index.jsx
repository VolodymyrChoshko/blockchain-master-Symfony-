import React, { useState } from 'react';
import PropTypes from 'prop-types';
import router from 'lib/router';
import { useSelector } from 'react-redux';
import { formatTime } from 'utils/dates';
import Avatar from 'dashboard/components/Avatar';
import PersonModal from 'components/PersonModal';
import { Pane } from 'builder/sidebars/ActivitySidebar/styles';
import { Name, Status, Time, Body } from 'builder/sidebars/ActivitySidebar/CommentItem/Comment/styles';
import { Item } from './styles';

const HistoryItem = ({ history, highlighted, onStatusClick, setHighlighted, fProcessName }) => {
  const emailVersion = useSelector(state => state.builder.emailVersion);
  const templateVersion = useSelector(state => state.builder.templateVersion);
  const mode = useSelector(state => state.builder.mode);
  const tid = useSelector(state => state.builder.tid);
  const [modalUser, setModalUser] = useState(null);
  const isEmail = mode.indexOf('template') !== 0;

  const href = isEmail
    ? router.generate('build_email_version', { tid, id: history.eid, version: history.version })
    : router.generate('build_template_version', { id: history.tid, version: history.version });
  const isCurrent = (isEmail && (history.version === emailVersion))
    || (!isEmail && history.version === templateVersion);

  return (
    <Pane
      highlighted={highlighted === history.id}
      onClick={() => {
        if (highlighted) {
          setHighlighted(0);
        }
      }}
    >
      <Item className="activity-history" data-history-id={history.id}>
        <div className="d-flex align-items-start">
          <div className="d-flex align-items-center">
            <Avatar
              user={history.user}
              className="pointer mr-2"
              onClick={() => setModalUser(history.user)}
              md
            />
            <div className="d-flex flex-column align-items-start">
              <Name>{fProcessName(history.user.name)}</Name>
              <Status className="mt-1" onClick={() => onStatusClick(history)}>
                {isEmail ? 'Made Edits' : 'Made Updates'}
              </Status>
            </div>
          </div>
          <Time className="ml-auto">
            {formatTime(new Date(history.dateCreated * 1000))}
          </Time>
        </div>
        <Body className="pt-2 pb-1">
          {isCurrent ? (
            <span>Current Version</span>
          ) : (
            <a href={href}>
              View This Version
            </a>
          )}
        </Body>
        {modalUser && (
          <PersonModal user={modalUser} onClose={() => setModalUser(null)} />
        )}
      </Item>
    </Pane>
  );
};

HistoryItem.propTypes = {
  history: PropTypes.object.isRequired,
  highlighted: PropTypes.number.isRequired,
  onStatusClick: PropTypes.func.isRequired,
  setHighlighted: PropTypes.func.isRequired,
};

HistoryItem.defaultProps = {};

export default HistoryItem;
