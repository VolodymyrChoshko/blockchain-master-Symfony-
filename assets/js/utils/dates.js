const fullDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const months = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December'
];

export const nth = (d) => {
  if (d > 3 && d < 21) return 'th';
  switch (d % 10) {
    case 1:  return 'st';
    case 2:  return 'nd';
    case 3:  return 'rd';
    default: return 'th';
  }
};

/**
 * @param someDateTimeStamp
 * @return {string}
 */
export const formatDate = (someDateTimeStamp) => {
  const dt = new Date(someDateTimeStamp);
  const date = dt.getDate();
  const month = months[dt.getMonth()];
  const diffDays = new Date().getDate() - date;
  const diffMonths = new Date().getMonth() - dt.getMonth();
  const diffYears = new Date().getFullYear() - dt.getFullYear();

  if (diffYears === 0 && diffDays === 0 && diffMonths === 0) {
    return 'Today';
  }
  if (diffYears === 0 && diffDays === 1) {
    return 'Yesterday';
  }
  if (diffYears === 0 && diffDays === -1) {
    return 'Tomorrow';
  }
  if (diffYears === 0 && (diffDays < -1 && diffDays > -7)) {
    return fullDays[dt.getDay()];
  }
  if (diffYears >= 1) {
    return `${month} ${date}, ${new Date(someDateTimeStamp).getFullYear()}`;
  }

  return `${month} ${date}${nth(date)}`;
};

/**
 * @param {Date} date
 */
export const formatTime = (date) => {
  let ampm = 'am';
  let hours = date.getHours();
  if (hours > 12) {
    hours -= 12;
    ampm = 'pm';
  }

  let minutes = date.getMinutes().toString();
  if (minutes.length === 1) {
    minutes = `0${minutes}`;
  }

  return `${hours}:${minutes}${ampm}`;
};
