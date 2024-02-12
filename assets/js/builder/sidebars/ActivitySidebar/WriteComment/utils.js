import emojiCodes, { skinToned, tones } from 'emojiCodes';
import { v4 as uuidv4 } from 'uuid';

/**
 * @return {{x: number, y: number}}
 */
export const getCaretCoordinates = () => {
  let x = 0;
  let y = 0;

  const selection = window.getSelection();
  if (selection.rangeCount !== 0) {
    const range = selection.getRangeAt(0).cloneRange();
    range.collapse(true);
    const rect = range.getClientRects()[0];
    if (rect) {
      x = rect.left;
      y = rect.top;
    }
  }

  return { x, y };
};

/**
 * @param {Node|ChildNode} node
 * @param {Range} range
 */
export const insertAtRange = (node, range) => {
  range.deleteContents();
  range.insertNode(node);
  range.setStartAfter(node);
  range.setEndAfter(node);

  const text = document.createTextNode('\u00a0');
  range.insertNode(text);
  range.setStartAfter(text);
  range.setEndAfter(text);

  const sel = window.getSelection();
  sel.removeAllRanges();
  sel.addRange(range);
};

/**
 * @param user
 * @return {Node|ChildNode}
 */
export const createMentionNode = (user) => {
  const nameParts = user.name.split(' ', 2);
  const [firstName, lastName] = nameParts;
  const userInfo = encodeURIComponent(JSON.stringify(user));

  let avatar = user.avatar60;
  if (!avatar) {
    avatar = user.avatar;
  }

  let html = '';
  if (avatar) {
    // eslint-disable-next-line max-len
    html = `<span class="activity-avatar-sm" data-user-id="${user.id}" data-mention-uuid="${uuidv4()}" data-user-info="${userInfo}" title="${user.name}" contenteditable="false"><img src="${avatar}" alt="" /> <span class="activity-avatar-sm-name">${firstName}</span></span>`;
  } else {
    const initials = `${firstName[0]}${lastName ? lastName[0] : ''}`;
    // eslint-disable-next-line max-len
    html = `<span class="activity-avatar-sm" data-user-id="${user.id}" data-mention-uuid="${uuidv4()}" data-user-info="${userInfo}" title="${user.name}" contenteditable="false"><span class="activity-avatar-sm-initials">${initials}</span> <span class="activity-avatar-sm-name">${firstName}</span></span>`;
  }
  html = html.trim().replace(/\n/g, '').replace(/\s\s/g, ' ');

  const template = document.createElement('template');
  template.innerHTML = html;

  return template.content.firstChild;
};

/**
 * @param key
 * @param me
 * @return {Text}
 */
export const createEmojiNode = (key, me) => {
  let html = emojiCodes[key];
  if (skinToned.indexOf(key) !== -1 && me.skinTone !== -1) {
    html = `${html}&#x${tones[me.skinTone]};`;
  }

  return document.createTextNode(html);
};
