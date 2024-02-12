import React, { useMemo } from 'react';
import { useSelector } from 'react-redux';
import { iFrameDocument } from 'utils/browser';
import { useRuleActions } from 'builder/actions/ruleActions';
import { BLOCK_DATA_ELEMENT_ID } from 'builder/engine/constants';
import Data from 'builder/engine/Data';
import { Container } from './styles';

/**
 * @param element
 * @returns {string|*}
 */
function getPathTo(element) {
  if (element === document.body) {
    return element.tagName;
  }

  let ix = 0;
  const siblings = element.parentNode?.childNodes || [];
  for (let i = 0; i < siblings.length; i++) {
    const sibling = siblings[i];
    if (sibling === element) {
      return `${getPathTo(element.parentNode)}/${element.tagName.toLowerCase()}[${  ix + 1  }]`;
    }
    if (sibling.nodeType === 1 && sibling.tagName === element.tagName) {
      ix++;
    }
  }

  return '';
}

/**
 * @param path
 * @param doc
 * @returns {Node}
 */
function getElementByXpath(path, doc) {
  return doc.evaluate(path, doc, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
}

const ElementPath = () => {
  const ruleActions = useRuleActions();
  const activeEdits = useSelector(state => state.rules.activeEdits);
  const iframe = useSelector(state => state.builder.iframe);
  const doc = iFrameDocument(iframe);

  /**
   * @type {unknown}
   */
  const buttons = useMemo(() => {
    const b = [];
    if (activeEdits.length === 0) {
      b.push({
        label: 'body',
        path:  'html[1]/body[1]'
      });

      return b;
    }

    const el = activeEdits[0];
    const paths = [];
    const tags = getPathTo(el).split('/').filter(v => v).map((v) => {
      paths.push(v);
      const m = v.match(/([\w\d]+)\[[\d]+]/);
      return m ? m[1] : v;
    });

    for (let i = 1; i < tags.length; i++) {
      b.push({
        label: tags[i],
        path:  Array.from(paths).splice(0, i + 1).join('/'),
      });
    }

    if (b.length > 12) {
      const count = b.length - 12;
      const b2 = b.slice(count);
      b2.unshift(b[0]);

      return b2;
    }

    return b;
  }, [activeEdits]);

  const len = buttons.length - 1;

  return (
    <Container>
      {buttons.map((btn, i) => {
        const el = getElementByXpath(`//${btn.path}`, doc);
        if ((!el || !Data.get(el, BLOCK_DATA_ELEMENT_ID)) && el.tagName !== 'BODY') {
          return null;
        }

        return (
          <React.Fragment key={btn.path}>
            <button
              className={i === len ? 'active' : ''}
              onClick={() => {
                ruleActions.setActiveEdit([el]);
              }}
              onMouseEnter={() => {
                ruleActions.setHoverEdits([el]);
              }}
              onMouseLeave={() => {
                ruleActions.setHoverEdits([]);
              }}
            >
              {btn.label}
            </button>
            {i < len && (
              <span>&gt;</span>
            )}
          </React.Fragment>
        );
      })}
    </Container>
  );
};

export default ElementPath;
