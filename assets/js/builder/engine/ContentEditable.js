import debounce from 'lodash.debounce';
import browser, { iFrameSrc } from 'utils/browser';
import EventDispatcher from 'lib/EventDispatcher';
import HTMLUtils from './HTMLUtils';
import * as constants from './constants';

/**
 *
 */
class ContentEditable {
  static onUndo = null;

  static onEmit = null;

  static _element = null;

  static _iframe = null;

  static eventDispatcher = new EventDispatcher();

  static instance = null;

  /**
   * @param html
   */
  static setHTML = (html) => {
    if (ContentEditable._element) {
      ContentEditable._element.innerHTML = html;
      if (ContentEditable._iframe) {
        return iFrameSrc(ContentEditable._iframe);
      }
    }

    return '';
  };

  /**
   * @returns {string|*}
   */
  static getHTML = () => {
    if (ContentEditable._element) {
      return ContentEditable._element.innerHTML;
    }

    return '';
  }

  /**
   * @param iframe
   * @param block
   * @returns {null}
   */
  static createInstance = (iframe, block) => {
    ContentEditable.instance = new ContentEditable(iframe, block);

    return ContentEditable.instance;
  };

  /**
   * @param {HTMLIFrameElement} iframe
   * @param {Block} block
   */
  constructor(iframe, block) {
    this.iframe        = iframe;
    this.window        = iframe.contentWindow;
    this.document      = browser.iFrameDocument(this.iframe);
    this.block         = block;
    this.origStyle     = '';
    this.element       = block.element;
    this.origHTML      = '';
    this.cloneCallback = null;
    this.firstChange   = true;
    this.debounced     = debounce(ContentEditable.emit, 1000, { 'maxWait': 2000 });
    ContentEditable._element = block.element;
    ContentEditable._iframe = iframe;
  }

  /**
   * @param {function} cloneCallback
   */
  startEditing = (cloneCallback) => {
    this.cloneCallback = cloneCallback;

    const body = browser.iFrameDocument(this.iframe).querySelector('body');
    if (body) {
      this.style = body.querySelector('#blocksedit-styles');
    }
    if (!this.style) {
      this.style           = HTMLUtils.createElement('<style id="blocksedit-styles" />');
      this.style.innerHTML = `
        [contenteditable=true] {
          outline: none !important;
        }
        [contenteditable=true]:focus {
          outline: none !important;
          box-shadow: none !important;
        }
      `;
      if (body) {
        // Is this why the body is losing its styles?
        body.appendChild(this.style);
      }
    }

    this.iframe.contentWindow.getSelection().removeAllRanges();
    this.origStyle = '';
    const styles   = window.getComputedStyle(this.element);
    if (styles.getPropertyValue('display') === 'inline') {
      this.origStyle             = this.element.getAttribute('style');
      this.element.style.display = 'inline-block';
    }

    // See https://stackoverflow.com/questions/51100750/contenteditable-adding-multiple-spaces-at-the-end-on-key-press
    this.element.innerHTML = this.element.innerHTML.trim();

    this.element.setAttribute('contenteditable', 'true');
    this.element.addEventListener('keydown', this.handleKeyDown, false);
    this.element.addEventListener('input', this.handleInput, false);
    this.element.addEventListener('paste', this.handlePaste, false);
    this.element.addEventListener('mouseup', this.handleMouseUp, false);
    this.element.focus();
  };

  /**
   *
   */
  finishEditing = () => {
    this.debounced.flush();
    this.element.removeEventListener('keydown', this.handleKeyDown, false);
    this.element.removeEventListener('mouseup', this.handleMouseUp, false);
    this.element.removeEventListener('input', this.handleInput, false);
    this.element.removeEventListener('paste', this.handlePaste, false);
    this.element.removeAttribute('contenteditable');
    ContentEditable._element = null;

    if (this.origStyle !== '') {
      this.element.setAttribute('style', this.origStyle);
      this.origStyle = '';
    }
    if (this.style) {
      this.style.remove();
      this.style = null;
    }
    ContentEditable.instance = null;
  };

  /**
   *
   */
  rollbackEditing = () => {
    this.finishEditing();
    this.element.innerHTML = this.origHTML;
    this.origHTML = '';
  };

  /**
   * @param {KeyboardEvent} e
   */
  handleKeyDown = (e) => {
    const { keyCode, ctrlKey } = e;

    if (this.firstChange) {
      ContentEditable.emit();
      this.firstChange = false;
    }
    if (keyCode === 90 && ctrlKey && ContentEditable.onUndo) {
      ContentEditable.onUndo(e);
      return;
    }

    if (keyCode === 13) {
      this.handleKeyEnter(e);
    }

    // Prevent bold and italics on code blocks
    if (this.isScriptBlock()) {
      if (ctrlKey && (keyCode === 66 || keyCode === 73)) {
        e.preventDefault();
      }
    }

    if (this.element.innerHTML === '&nbsp;') {
      this.element.innerHTML = '';
    }

    if (
      keyCode === 8
      && this.element.tagName === 'LI'
      && (this.element.innerHTML === '' || this.element.innerHTML === '<br>')
    ) {
      this.cloneCallback(false);
    }

    this.debounced();
  };

  /**
   * @param {KeyboardEvent} e
   */
  handleKeyEnter = (e) => {
    // Replace <p> with <br />
    if (this.element.tagName === 'LI') {
      e.preventDefault();
      this.cloneCallback(true);
    } else if (this.window.getSelection) {
      e.preventDefault();
      const selection = this.window.getSelection();
      const range     = selection.getRangeAt(0);
      const br        = this.document.createElement('br');
      const textNode  = this.document.createTextNode('\u00a0');
      range.deleteContents(); // required or not?
      range.insertNode(br);
      range.setStartAfter(br);
      range.setEndAfter(br);
      range.collapse(false);

      range.insertNode(textNode);
      range.selectNodeContents(textNode);

      selection.removeAllRanges();
      selection.addRange(range);
      this.document.execCommand('delete');
    }
  };

  /**
   * @param {KeyboardEvent} e
   */
  handleInput = (e) => {
    const { inputType } = e;

    if (this.firstChange) {
     // ContentEditable.emit();
      this.firstChange = false;
    }
    if ((inputType === 'deleteByCut' || inputType === 'deleteContentBackward') && this.element.innerHTML.length === 0) {
      this.element.innerHTML = '&nbsp;'; // Placeholder to prevent block from disappearing
    }
    this.debounced();
  };

  /**
   * @param {Event} e
   */
  handlePaste = (e) => {
    e.preventDefault();

    if (this.firstChange) {
      ContentEditable.emit();
      this.firstChange = false;
    }

    const event         = e.originalEvent || e;
    const clipboardData = event.clipboardData || window.clipboardData;
    let text;
    if (this.isScriptBlock()) {
      text = clipboardData.getData('text/plain')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    } else {
      text = clipboardData.getData('text/plain')
        .replace(/(<([^>]+)>)/ig, '')
        .replace(/\n/g, '<br />');
      // .replace(/<p[^>]*>/g, '').replace(/<\/p>/g, '<br /><br />');
    }

    browser.iFrameDocument(this.iframe).execCommand('insertHTML', false, text);
    this.debounced();
  };

  /**
   * Catches selecting text. Which will be used to rollback the changes
   * made to the selected text.
   */
  handleMouseUp = () => {
    this.origHTML = this.element.innerHTML;
  };

  /**
   * @private
   * @returns {boolean}
   */
  isScriptBlock = () => {
    return !!(
      this.element.parentNode && this.element.parentElement.classList.contains(constants.CLASS_BLOCK_SCRIPT_SEC)
    );
  }

  /**
   *
   */
  static emit = () => {
    if (ContentEditable.instance && ContentEditable.instance.element && ContentEditable.instance.block) {
      const attribs = HTMLUtils.getAttributes(ContentEditable.instance.element);
      ContentEditable.eventDispatcher.trigger(
        'emit',
        ContentEditable.instance.element.innerHTML,
        ContentEditable.instance.block.id,
        attribs
      );
    }
  };
}

export default ContentEditable;
