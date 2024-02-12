import Icon from 'components/Icon';
import Mask from 'components/Mask';
import Avatar from 'dashboard/components/Avatar';
import React, { useMemo } from 'react';
import { Container, Name, Role, Time } from './styles';

/**
 * @param date
 * @returns {string}
 */
const formatAMPM = (date) => {
  let hours   = date.getHours();
  let minutes = date.getMinutes();
  const ampm  = hours >= 12 ? 'pm' : 'am';
  hours %= 12;
  hours = hours || 12; // the hour '0' should be '12'
  minutes = minutes < 10 ? `0${minutes}` : minutes;

  return `${hours}:${minutes} ${ampm}`;
};

/**
 * @param date
 * @param timezone
 * @returns {Date}
 */
const convertDateToAnotherTimeZone = (date, timezone) => {
  const dateString = date.toLocaleString('en-US', {
    timeZone: timezone
  });

  return new Date(dateString);
};

/**
 * @param timezone
 * @returns {string}
 */
const getTimezoneAbbreviation = (timezone) => {
  return (new Date()).toLocaleTimeString('en-us', {
    timeZone:     timezone,
    timeZoneName: 'short'
  }).split(' ')[2];
};

/**
 * @param {HTMLElement|Node} element
 * @param {string} className
 * @param {boolean} returnParent
 * @returns {boolean|HTMLElement|Node}
 */
const hasParentClass = (element, className, returnParent = false) => {
  do {
    if (element.classList && element.classList.contains(className)) {
      if (returnParent) {
        return element;
      }
      return true;
    }
    element = element.parentNode;
  } while (element);

  return false;
};

const PersonModal = ({ user, onClose }) => {
  /**
   * @param e
   */
  const handleMaskClick = (e) => {
    if (!hasParentClass(e.target, 'modal-person')) {
      onClose();
    }
  };

  /**
   * @type {string}
   */
  const [userTime, userTimezone] = useMemo(() => {
    const userDate = convertDateToAnotherTimeZone(new Date(), user.timezone || 'America/New_York');

    return [
      formatAMPM(userDate),
      getTimezoneAbbreviation(user.timezone || 'America/New_York'),
    ];
  }, [user]);

  let roleLabel = 'Editor';
  if (user.isOwner) {
    roleLabel = 'Owner';
  } else if (user.isAdmin) {
    roleLabel = 'Admin';
  }

  let job = '';
  if (user.job) {
    // eslint-disable-next-line prefer-destructuring
    job = user.job;
    if (user.organization) {
      job = `${job}, `;
    }
  }
  if (user.organization) {
    job = `${job} ${user.organization}`.trim();
  }

  let avatar = '';
  if (user.avatar) {
    avatar = user.avatar.replace('-60x60', '');
  }

  return (
    <Mask onClick={handleMaskClick} open>
      <Container className="modal-person" white>
        <div className="text-right pb-2">
          <Icon
            name="be-symbol-delete"
            title="Close"
            className="pointer"
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              onClose(e);
            }}
          />
        </div>
        <div className="d-flex align-items-center justify-content-center flex-column">
          <Avatar user={{ ...user, avatar }} lg />
          <Role>
            {roleLabel}
          </Role>
          <Name>
            {user.name}
          </Name>
          <p className="mb-4">
            {job}
          </p>
          <Time>
            {userTime} {userTimezone} local time
          </Time>
        </div>
      </Container>
    </Mask>
  );
};

export default PersonModal;
