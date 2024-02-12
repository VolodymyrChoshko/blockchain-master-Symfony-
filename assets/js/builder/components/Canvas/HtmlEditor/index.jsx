import Button from 'components/Button';
import React, { useEffect, useState, useRef } from 'react';
import ace from 'brace';
import 'brace/mode/html';
import 'brace/theme/monokai';
import 'brace/theme/chrome';
import 'brace/ext/searchbox';
import Icon from 'components/Icon';
import { iFrameSrc } from 'utils/browser';
import { useTheme } from 'styled-components';
import { useSelector } from 'react-redux';
import { useBuilderActions } from 'builder/actions/builderActions';
import { useRuleActions } from 'builder/actions/ruleActions';
import { Container, Inner, Toolbar } from './styles';

const HtmlEditor = () => {
  const inner = useRef();
  const editor = useRef(null);
  const builderActions = useBuilderActions();
  const ruleActions = useRuleActions();
  const iframe = useSelector(state => state.builder.iframe);
  const isExpandedHtml = useSelector(state => state.rules.isExpandedHtml);
  const theme = useTheme();
  const [value, setValue] = useState('');

  /**
   *
   */
  useEffect(() => {
    editor.current = ace.edit(inner.current);
    editor.current.getSession().setMode('ace/mode/html');
    editor.current.setTheme('ace/theme/monokai');
    editor.current.setOption('wrap', true);
    editor.current.setOption('showPrintMargin', false);
    editor.current.setFontSize('12px');
    editor.current.on('change', (v) => setValue(v));

    const html = iFrameSrc(iframe);
    editor.current.setValue(html, -1);

    return () => {
      editor.current.destroy();
    };
  }, [theme, iframe]);

  /**
   *
   */
  useEffect(() => {
    editor.current.resize(true);
  }, [isExpandedHtml]);

  /**
   *
   */
  const handleUpdateClick = () => {
    builderActions.setHTML(editor.current.getValue(), -1);
  };

  return (
    <Container isExpandedHtml={isExpandedHtml}>
      <Toolbar>
        <Button variant="main" onClick={handleUpdateClick}>
          Update
        </Button>

        <Button title={isExpandedHtml ? 'Shrink' : 'Expand'} onClick={() => ruleActions.setExpandedHtml()}>
          <Icon name={isExpandedHtml ? 'be-symbol-shrink' : 'be-symbol-expand'} />
        </Button>
      </Toolbar>
      <Inner ref={inner} />
    </Container>
  );
};

export default HtmlEditor;
