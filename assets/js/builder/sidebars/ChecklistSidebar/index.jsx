import React, { useRef } from 'react';
import { useSelector } from 'react-redux';
import { Scrollbars } from 'react-custom-scrollbars';
import SlideoutSidebar from 'builder/components/SlideoutSidebar';
import renderScrollbars from 'utils/scrollbars';
import useMe from 'dashboard/hooks/useMe';
import ErrorBoundary from 'components/ErrorBoundary';
import SidebarTitle from 'builder/components/SidebarTitle';
import { Container, Inner, Panel } from '../ActivitySidebar/styles';
import ChecklistItem from './ChecklistItem';

const ChecklistSidebar = () => {
  const me = useMe();
  const isChecklistOpen = useSelector(state => state.ui.isChecklistOpen);
  const zIndexActivity = useSelector(state => state.ui.zIndexActivity);
  const items = useSelector(state => state.checklist.items);
  const mode = useSelector(state => state.builder.mode);
  const builderState = useSelector(state => state.builder);
  const itemEditedFlag = me ? me.hasEditedTmplSettings : false;
  const scrollbars = useRef(null);

  if (mode.indexOf('preview') !== -1) {
    return null;
  }

  const list = [];
  if (items) {
    for (let i = 0; i < items.length; i++) {
      list.push(items[i]);
    }
  }

  return (
    <SlideoutSidebar zIndex={zIndexActivity} open={isChecklistOpen}>
      <ErrorBoundary>
        <Container>
          {mode.indexOf('email') === 0 && (
            <Panel>
              <SidebarTitle style={{ borderTop: 0, textTransform: 'none' }}>
                Checklist of items to review for this email.
              </SidebarTitle>
            </Panel>
          )}
          <Inner>
            <Scrollbars
              autoHide
              ref={scrollbars}
              renderTrackHorizontal={renderScrollbars.renderTrackHorizontal}
              renderThumbHorizontal={renderScrollbars.renderThumbHorizontal}
            >
              {list.map(item => (
                <ChecklistItem key={item.id} item={item} />
              ))}
              {!itemEditedFlag && builderState.isOwner || !itemEditedFlag && builderState.isAdmin ? (
                <Panel style={{ margin: '.75rem' }}>
                  <SidebarTitle
                    style={{ borderRadius: '10px', fontSize: '12px', padding: '.75rem', lineHeight: '1.3em' }}
                  >
                    You can change the items in the checklist from the template settings on your dashboard.
                  </SidebarTitle>
                </Panel>
              ) : null}
            </Scrollbars>
          </Inner>
        </Container>
      </ErrorBoundary>
    </SlideoutSidebar>
  );
};

export default ChecklistSidebar;
