import { pLimit } from 'plimit-lit';
import api from 'lib/api';
import router from 'lib/router';
import browser from 'utils/browser';
import HTMLUtils from 'builder/engine/HTMLUtils';
import * as c from 'builder/engine/constants';

/**
 *
 */
class UpgradeEngine {
  /**
   * @param {number[]} ids
   * @param {number} tid
   * @param {string} origHtml
   * @param {function} cb
   * @param {function} pcb
   * @returns {Promise<void>}
   */
  upgradeLayouts = async (ids, tid, origHtml, cb, pcb) => {
    const { htmls } = await api.req('POST', router.generate('build_layout_get_htmls'), { ids });

    let percent = 0;
    const limit = pLimit(4);
    let promises = [];
    const keys = Object.keys(htmls);
    for (let i = 0; i < keys.length; i++) {
      const key = keys[i];
      // eslint-disable-next-line no-loop-func
      promises.push(limit(() => {
        const [layout, layoutDoc] = this.createDoc(htmls[key]);
        const [template, templateDoc] = this.createDoc(origHtml);
        this.replaceDataBlocks(layoutDoc, templateDoc);

        const html = browser.iFrameSrc(template);
        layout.remove();
        template.remove();

        percent += 5;
        pcb(percent);

        return html;
      }));
    }

    const results = await Promise.all(promises);
    if (results.length > 0) {
      const step = (100 - percent - 10) / results.length;
      promises = [];
      for (let i = 0; i < results.length; i++) {
        // eslint-disable-next-line no-loop-func
        promises.push(limit(async () => {
          const id = keys[i];
          const html = results[i];
          const uuid = await api.req('POST', router.generate('build_layout_save_html', { id }), {
            html,
          });

          const s = this.getStatus(uuid);
          percent += (step * i);
          pcb(percent);

          return s;
        }));
      }

      await Promise.all(promises);
      pcb(100);
      setTimeout(cb, 2000);
    } else {
      cb();
    }
  };

  /**
   * @param id
   * @param tid
   * @param origHtml
   * @param templateVersion
   * @param cb
   * @param {function} pcb
   * @returns {Promise<void>}
   */
  upgradeEmail = async (id, tid, origHtml, templateVersion, cb, pcb) => {
    const { html, title } = await api.req('GET', router.generate('build_email_get_html', { id }));
    const [email, emailDoc] = this.createDoc(html);
    const [template, templateDoc] = this.createDoc(origHtml);

    pcb(10);
    this.replaceDataBlocks(emailDoc, templateDoc);
    pcb(50);
    await api.req('POST', router.generate('build_save', { id }), {
      html: browser.iFrameSrc(template),
      templateVersion,
      title,
    });

    email.remove();
    template.remove();

    pcb(100);
    setTimeout(cb, 1000);
  };

  /**
   * @param {number[]} ids
   * @param {number} tid
   * @param {string} origHtml
   * @param {function} cb
   * @param {function} pcb
   * @returns {Promise<void>}
   */
  upgradePins = async (ids, tid, origHtml, cb, pcb) => {
    const { htmls } = await api.req('POST', router.generate('build_library_get_htmls'), { ids });

    let percent = 0;
    const limit = pLimit(4);
    let promises = [];
    const keys = Object.keys(htmls);
    for (let i = 0; i < keys.length; i++) {
      const key = keys[i];
      // eslint-disable-next-line no-loop-func
      promises.push(limit(() => {
        percent += 5;
        pcb(percent);

        const [layout, layoutDoc] = this.createDoc(origHtml);
        const [template, templateDoc] = this.createDoc(origHtml);

        let html = htmls[key];
        const element = HTMLUtils.createElement(htmls[key], layoutDoc);
        const groupName = element.getAttribute('data-group');
        if (groupName) {
          const lGroup = layoutDoc.querySelector(`[data-group="${groupName}"]`);
          if (lGroup) {
            HTMLUtils.insertBefore(lGroup, element);
            lGroup.remove();

            this.replaceDataBlocks(layoutDoc, templateDoc);
            html = element.outerHTML;
          }
        }

        layout.remove();
        template.remove();

        return html;
      }));
    }

    /**
     * @param {array} libraries
     * @returns {number}
     */
    const countDone = (libraries) => {
      let done = 0;
      libraries.forEach((lib) => {
        if (lib.screenshotDesktop !== '' && lib.screenshotMobile !== '') {
          done += 1;
        }
      });

      return done;
    };

    const results = await Promise.all(promises);
    if (results.length > 0) {
      const step = (100 - percent - 10) / results.length;
      promises = [];
      for (let i = 0; i < results.length; i++) {
        // eslint-disable-next-line no-loop-func
        promises.push(limit(async () => {
          const id = keys[i];
          const html = results[i];
          await api.req('POST', router.generate('build_library_update', { id }), {
            html,
          });

          percent += step;
          pcb(percent);
        }));
      }

      await Promise.all(promises);
      const checkInterval = setInterval(() => {
        api.get(`${router.generate('build_libraries')}?id=${tid}&mode=template`)
          .then((libraries) => {
            if (countDone(libraries) === libraries.length) {
              clearInterval(checkInterval);
              pcb(100);
              setTimeout(cb, 1000);
            }
          });
      }, 5000);
    } else {
      cb();
    }
  };

  /**
   * @param layoutDoc
   * @param templateDoc
   */
  replaceDataBlocks = (layoutDoc, templateDoc) => {
    layoutDoc.querySelectorAll(`*[${c.DATA_GROUP}]`)
      .forEach((element) => {
        this.replaceBlock(element, templateDoc, c.DATA_GROUP);
      });
    layoutDoc.querySelectorAll(`*[${c.DATA_BLOCK}]`)
      .forEach((element) => {
        this.replaceBlock(element, templateDoc, c.DATA_BLOCK);
      });

    const templateSections = templateDoc.querySelectorAll('.block-section');
    let firstSection;
    for (let i = 0; i < templateSections.length; i++) {
      if (!firstSection && (templateSections[i].getAttribute('class') || '').indexOf('be-code-edit') === -1) {
        firstSection = templateSections[i];
      } else {
        templateSections[i].remove();
      }
    }

    if (firstSection) {
      const layoutSections = layoutDoc.querySelectorAll('.block-section');
      const layoutSectionsA = Array.from(layoutSections);
      firstSection.after(...layoutSectionsA);
      firstSection.remove();
    }
  };

  /**
   * @param {HTMLElement} element
   * @param {Document} templateDoc
   * @param {string} dataAttrib
   */
  replaceBlock = (element, templateDoc, dataAttrib) => {
    if (element.classList.contains(c.CLASS_BLOCK_COMPONENT)) {
      element.setAttribute(c.DATA_BE_KEEP, 'true');
    }
    element.querySelectorAll(`.${c.CLASS_BLOCK_COMPONENT}`).forEach((component) => {
      component.setAttribute(c.DATA_BE_KEEP, 'true');
    });

    const clone       = element.cloneNode(true);
    const blockName   = element.getAttribute(dataAttrib);
    let templateBlock = templateDoc.querySelector(`[${dataAttrib}="${blockName}"]`);

    const groupName = clone.getAttribute(c.DATA_GROUP);
    const variation = clone.getAttribute(c.DATA_VARIATION_INDEX);
    if (groupName && variation !== null) {
      const blocks = templateDoc.querySelectorAll(`[${c.DATA_GROUP}="${groupName}"]`);
      templateBlock = blocks[variation];
      if (!templateBlock) {
        console.error(`Section variable index ${variation} not found in template`);
        return;
      }
    }

    if (templateBlock) {
      if (templateBlock.classList.contains(c.CLASS_BLOCK_SECTION)) {
        // Save the block-edit values.
        const edits = [];
        const editIndexes = {};
        element.querySelectorAll(`.${c.CLASS_BLOCK_EDIT}`).forEach((subElement) => {
          const subBlockName = subElement.getAttribute(c.DATA_BLOCK);
          if (subBlockName) {
            if (editIndexes[subBlockName] === undefined) {
              editIndexes[subBlockName] = 0;
            }

            edits.push({
              index:          editIndexes[subBlockName]++,
              name:           subBlockName,
              element:        subElement.cloneNode(true),
              prevComponents: this.findPrevComponents(subElement),
              nextComponents: this.findNextComponents(subElement),
            });
          }
        });

        // Overwrite all the html in the element with the template html.
        // standard-text
        element.innerHTML = templateBlock.innerHTML;
        element.querySelectorAll(`.${c.CLASS_BLOCK_COMPONENT}`).forEach((component) => {
          component.remove();
        });

        // Restore the block-edit values.
        for (let i = 0; i < edits.length; i++) {
          const name = edits[i].name;
          const index = edits[i].index;
          const elements = element.querySelectorAll(`.${c.CLASS_BLOCK_EDIT}[${c.DATA_BLOCK}="${name}"]`);
          const el = elements[index];

          if (el) {
            const sub = edits[i];
            el.innerHTML = sub.element.innerHTML;
            this.replaceAttributes(el, sub.element, el.tagName);
            sub.prevComponents.forEach((component) => {
              this.replaceComponentAttributes(component, templateDoc);
              HTMLUtils.insertBefore(el, component);
            });
            sub.nextComponents.forEach((component) => {
              this.replaceComponentAttributes(component, templateDoc);
              HTMLUtils.insertAfter(el, component);
            });
          }
        }
      }

      if (templateBlock.classList.contains(c.CLASS_BLOCK_EDIT)) {
        templateBlock.innerHTML = clone.innerHTML;
      }

      let attribs = HTMLUtils.getAttributes(templateBlock);
      Object.keys(attribs).forEach((key) => {
        if (attribs[key]) {
          element.setAttribute(key, attribs[key]);
        }
      });

      this.replaceAttributes(element, clone, templateBlock.tagName);

      element.querySelectorAll(`.${c.CLASS_BLOCK_COMPONENT}`).forEach((component) => {
        let attr = c.DATA_BLOCK;
        const variationIndex = component.getAttribute(c.DATA_VARIATION_INDEX);
        let componentName = component.getAttribute(c.DATA_BLOCK);
        if (!componentName) {
          attr = c.DATA_GROUP;
          componentName = component.getAttribute(c.DATA_GROUP);
        }

        if (componentName) {
          // eslint-disable-next-line max-len
          const templateComponents = templateDoc.querySelectorAll(`.${c.CLASS_BLOCK_COMPONENT}[${attr}="${componentName}"]`);
          if (templateComponents.length > 0) {
            const templateComponent = variationIndex === null
              ? templateComponents[0]
              : templateComponents[variationIndex];
            attribs = HTMLUtils.getAttributes(templateComponent);

            Object.keys(attribs).forEach((key) => {
              if (attribs[key] && key !== c.DATA_COMPONENT_HIDDEN) {
                if (key === 'style') {
                  attribs[key] = attribs[key].replace('display: none;', '');
                  attribs[key] = attribs[key].replace(/;;/g, ';');
                  if (attribs[key] === ';') {
                    return;
                  }
                }
                component.setAttribute(key, attribs[key]);
              }
            });
            if (variationIndex !== null) {
              component.setAttribute(c.DATA_VARIATION_INDEX, variationIndex);
            }
            attribs = HTMLUtils.getAttributes(component);
          }
        }
      });
    } else if (templateBlock && templateBlock.classList.contains(c.CLASS_BLOCK_EDIT)) {
      this.replaceAttributes(element, clone, templateBlock.tagName);
    }
  }

  /**
   * @param element
   * @param clone
   * @param tagName
   */
  replaceAttributes = (element, clone, tagName) => {
    switch (tagName) {
      case 'IMG':
        element.setAttribute('src', clone.getAttribute('src'));
        if (clone.getAttribute(c.DATA_HOSTED)) {
          element.setAttribute(c.DATA_HOSTED, '1');
        }
        if (clone.getAttribute(c.DATA_IMG_ID)) {
          element.setAttribute(c.DATA_IMG_ID, clone.getAttribute(c.DATA_IMG_ID));
        }
        if (clone.classList.contains(c.CLASS_BLOCK_RESIZE) && clone.getAttribute(c.DATA_HOSTED)) {
          element.setAttribute('height', clone.getAttribute('height') || element.getAttribute('height'));
          element.setAttribute('width', clone.getAttribute('width') || element.getAttribute('width'));
          if (clone.getAttribute('original')) {
            element.setAttribute('original', clone.getAttribute('original'));
          }

          const cloneStyles = HTMLUtils.getStyleObject(clone.getAttribute('style') || '');
          const elementStyles = HTMLUtils.getStyleObject(element.getAttribute('style') || '');
          if (cloneStyles.height) {
            elementStyles.height = cloneStyles.height;
          }
          if (cloneStyles.width) {
            elementStyles.width = cloneStyles.width;
          }
          element.setAttribute('style', HTMLUtils.serializeStyleObject(elementStyles));
        }
        break;
      case 'A':
        element.setAttribute('href', clone.getAttribute('href'));
        element.innerText = clone.innerText;
        break;
      default:
        if (clone.classList.contains(c.CLASS_BLOCK_EDIT_EMPTY)) {
          element.classList.add(c.CLASS_BLOCK_EDIT_EMPTY);
          element.setAttribute('style', '');
        }
        break;
    }
  }

  /**
   * @param {HTMLElement} component
   * @param {Document} templateDoc
   */
  replaceComponentAttributes = (component, templateDoc) => {
    component.querySelectorAll(`.${c.CLASS_BLOCK_EDIT}`).forEach((edit) => {
      const editName = edit.getAttribute(c.DATA_BLOCK);
      if (editName) {
        const templateEdit = templateDoc.querySelector(`.${c.CLASS_BLOCK_EDIT}[${c.DATA_BLOCK}="${editName}"]`);
        if (templateEdit) {
          const editAttribs = HTMLUtils.getAttributes(templateEdit);
          Object.keys(editAttribs).forEach((k) => {
            if (editAttribs[k]) {
              edit.setAttribute(k, editAttribs[k]);
            }
          });
        }
      }
    });
  }

  /**
   * @param {HTMLElement} element
   * @returns {HTMLElement[]}
   */
  findNextComponents = (element) => {
    const components = [];
    let next = element.nextElementSibling;
    while (next && next.classList.contains(c.CLASS_BLOCK_COMPONENT)) {
      // console.log('next', next.cloneNode(true));
      components.push(next.cloneNode(true));
      next = next.nextElementSibling;
    }

    return components.reverse();
  };

  /**
   * @param {HTMLElement} element
   * @returns {HTMLElement[]}
   */
  findPrevComponents = (element) => {
    const components = [];
    let prev = element.previousElementSibling;
    while (prev && prev.classList.contains(c.CLASS_BLOCK_COMPONENT)) {
      // console.log('prev', prev.cloneNode(true));
      components.push(prev.cloneNode(true));
      prev = prev.previousElementSibling;
    }

    return components.reverse();
  }

  /**
   * @param uuid
   */
  getStatus = (uuid) => {
    return new Promise((resolve) => {
      let isBusy = false;
      const url = router.generate('build_templates_uploading_status', { uuid });
      const it = setInterval(() => {
        try {
          if (isBusy) {
            return;
          }
          isBusy = true;

          api.get(url)
            .then((status) => {
              if (status.percent === 100) {
                clearInterval(it);
                resolve();
              }
            });
        } catch (error) {
          clearInterval(it);
        } finally {
          isBusy = false;
        }
      }, 5000);
    });
  };

  /**
   * @param html
   */
  createDoc = (html) => {
    const layout = document.createElement('IFRAME');
    layout.src = 'about:blank';
    layout.style.display = 'none';
    document.body.appendChild(layout);
    browser.iFrameSrc(layout, html);
    const layoutDoc = browser.iFrameDocument(layout);

    return [layout, layoutDoc];
  }
}

export default new UpgradeEngine();
