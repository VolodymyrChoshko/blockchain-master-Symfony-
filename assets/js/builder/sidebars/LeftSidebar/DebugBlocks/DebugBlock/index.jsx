import React, { useState, useRef } from 'react';
import PropTypes from 'prop-types';
import ContentEditable from 'react-contenteditable';
import classNames from 'classnames';
import { Icon } from 'components';
import { useUIActions } from 'builder/actions/uiActions';
import { useBuilderActions } from 'builder/actions/builderActions';
import { Container, Body } from './styles';

const DebugBlock = ({ block, active, hover, onClick, onVariationClick }) => {
  const uiActions = useUIActions();
  const builderActions = useBuilderActions();
  const [expanded, setExpanded]   = useState(false);
  const [classList, setClassList] = useState(block.element.classList.toString());
  const [styleText, setStyleText] = useState(block.element.style.cssText);
  const contentEditableClassList  = useRef();
  const contentEditableStyle      = useRef();

  const classes = classNames({
    expanded,
    active,
    hover
  });

  const handleExpandClick = () => {
    setExpanded(!expanded);
  };

  const handleClassListBlur = () => {
    block.element.setAttribute('class', contentEditableClassList.current.innerHTML);
    builderActions.updateBlock(block.id, 'element', block.element);
  };

  const handleStyleBlur = () => {
    block.element.setAttribute('style', contentEditableStyle.current.innerHTML);
    builderActions.updateBlock(block.id, 'element', block.element);
  };

  return (
    <Container
      key={block.id}
      className={classes}
      id={`draggable-block-debug-${block.id}`}
      onClick={e => onClick(e, block)}
    >
      <strong onClick={handleExpandClick}>
        #{block.id} &middot; {block.type}
        <Icon name={expanded ? 'fa-caret-up' : 'fa-caret-down'} fas />
      </strong>
      <Body>
        {block.hasVariations() && (
          <ul className="mb-2 ml-2">
            {block.variations.map(v => (
              <li key={v.id} onClick={e => onVariationClick(e, block, v)}>
                #{v.id} {v.element.style.display !== 'none' || '(Active)'}
              </li>
            ))}
          </ul>
        )}
        <small className="mb-2">
          class=&quot;
          <ContentEditable
            html={classList}
            className="d-inline"
            onBlur={handleClassListBlur}
            onChange={e => setClassList(e.target.value)}
            innerRef={contentEditableClassList}
          />&quot;
          <br /><br />
          style=&quot;
          <ContentEditable
            html={styleText}
            className="d-inline"
            onBlur={handleStyleBlur}
            onChange={e => setStyleText(e.target.value)}
            innerRef={contentEditableStyle}
          />&quot;
        </small>
        <pre className="pt-2">
          {JSON.stringify({
            tag:   block.tag,
            rect:  block.rect,
            rules: block.rules,
            data:  block.data()
          }, null, 2)}
        </pre>
        <div className="pt-4">
          <button onClick={() => uiActions.modal('html', true, { block })}>
            HTML
          </button>
        </div>
      </Body>
    </Container>
  );
};

DebugBlock.propTypes = {
  block:            PropTypes.object.isRequired,
  active:           PropTypes.bool.isRequired,
  hover:            PropTypes.bool.isRequired,
  onClick:          PropTypes.func.isRequired,
  onVariationClick: PropTypes.func.isRequired
};

export default DebugBlock;
