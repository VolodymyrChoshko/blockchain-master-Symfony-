import React, { useEffect, useMemo, useRef } from 'react';
import { useSelector } from 'react-redux';
import eventDispatcher from 'builder/store/eventDispatcher';
import { Scrollbars } from 'react-custom-scrollbars';
import { useBuilderActions } from 'builder/actions/builderActions';
import Draggables from './Draggables';
import Pins from './Pins';
import Layouts from './Layouts';
import DebugBlocks from './DebugBlocks';
import { Container, Inner } from './styles';

const LeftSidebar = () => {
  const builderActions = useBuilderActions();
  const editing = useSelector(state => state.builder.editing);
  const sections = useSelector(state => state.builder.sections);
  const components = useSelector(state => state.builder.components);
  const layouts = useSelector(state => state.builder.layouts);
  const version = useSelector(state => state.builder.version);
  const templateVersion = useSelector(state => state.builder.templateVersion);
  const mode = useSelector(state => state.builder.mode);
  const tmhEnabled = useSelector(state => state.builder.tmhEnabled);
  const dropZoneID = useSelector(state => state.builder.dropZoneID);
  const previewDevice = useSelector(state => state.ui.previewDevice);
  const sidebarSection = useSelector(state => state.ui.sidebarSection);
  const sources = useSelector(state => state.source.sources);
  const sidebar = useRef(null);
  const scrollbars = useRef(null);

  useEffect(() => {
    sidebar.current.addEventListener('mousemove', () => {
      if (dropZoneID !== -1) {
        builderActions.dropZoneID(-1);
      }
    }, false);
    sidebar.current.addEventListener('click', () => {
      builderActions.dropZoneID(-1);
    });
  }, [dropZoneID]);

  useEffect(() => {
    eventDispatcher.on('sectionLibraryAdded', () => {
      setTimeout(() => {
        scrollbars.current.scrollToBottom();
      }, 1000);
    });
  }, []);

  /**
   * @type {unknown}
   */
  const filteredComponents = useMemo(() => {
    if (tmhEnabled) {
      return components.filter((com) => {
        return com.tmp_version === templateVersion;
      });
    }

    return components.filter((com) => {
      return com.tmp_version === version;
    });
  }, [components, tmhEnabled, templateVersion]);

  /**
   * @type {unknown}
   */
  const filteredSections = useMemo(() => {
    if (tmhEnabled) {
      return sections.filter((sec) => {
        return sec.tmp_version === templateVersion;
      });
    }

    return sections.filter((sec) => {
      return sec.tmp_version === version;
    });
  }, [sections, tmhEnabled, templateVersion]);

  return (
    <Container ref={sidebar} open={editing}>
      <Inner>
        <Scrollbars ref={scrollbars}>
          {sidebarSection === 'components' && (
            <Draggables
              components={filteredComponents}
              previewDevice={previewDevice}
            />
          )}
          {sidebarSection === 'sections' && (
            <Draggables
              sections={filteredSections}
              previewDevice={previewDevice}
              sources={sources}
            />
          )}
          {sidebarSection === 'libraries' && (
            <Pins />
          )}
          {sidebarSection === 'layouts' && (
            <Layouts
              layouts={layouts}
              mode={mode}
              previewDevice={previewDevice}
            />
          )}
          {(sidebarSection === 'blocks' && __ENV__ === 'development') && (
            <DebugBlocks />
          )}
        </Scrollbars>
      </Inner>
    </Container>
  );
};

export default LeftSidebar;
