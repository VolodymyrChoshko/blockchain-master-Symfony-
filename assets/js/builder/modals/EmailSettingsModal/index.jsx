import { DocMeta } from 'builder/engine';
import React, { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import { Modal, Button, ButtonGroup } from 'components';
import ModalTabs from 'components/ModalTabs';
import browser from 'utils/browser';
import Envelope from './Envelope';
import LinkParams from './LinkParams';
import Meta from './Meta';

const EmailSettingsModal = ({ id, button, saveTitle, source, onUpdate, closeModal, ...props }) => {
  const [selectedTab, setSelectedTab] = useState('envelope');
  const [hasMeta, setHasMeta] = useState(false);
  const iframe = useSelector(state => state.builder.iframe);
  const tpaEnabled = useSelector(state => state.builder.tpaEnabled);
  const tmpAliasEnabled = useSelector(state => state.builder.tmpAliasEnabled);
  const sources = useSelector(state => state.source.sources);

  const sourcesWithSettings = sources.filter((s) => {
    if (s.settings.rules.export_settings_show) {
      return s;
    }
    return null;
  });

  /**
   *
   */
  useEffect(() => {
    if (source) {
      setSelectedTab(source.id.toString());
    } else if (sourcesWithSettings.length > 0 && selectedTab === 'envelope') {
      setSelectedTab(sourcesWithSettings[0].id.toString());
    }
  }, [sourcesWithSettings, selectedTab, source]);

  /**
   *
   */
  useEffect(() => {
    if (iframe) {
      const doc = browser.iFrameDocument(iframe);
      const lang = DocMeta.getLang(doc);
      const meta = DocMeta.getMetaTags(doc);
      if (lang.domLang) {
        setHasMeta(true);
      }
      // eslint-disable-next-line max-len
      if (meta.domDescription || meta.domAppTitle || meta.domOGTitle || meta.domOGDescription || meta.domOGImage || meta.domOGUrl) {
        setHasMeta(true);
      }
    }
  }, [iframe]);

  /**
   * @param e
   */
  const handleSubmit = (e) => {
    e.preventDefault();
    document.dispatchEvent(new Event(`emailSettingsModalSubmit.${selectedTab}`));
  };

  const footer = (
    <>
      {button ? (
        <Button variant="main" onClick={handleSubmit}>
          {button}
        </Button>
      ) : (
        <ButtonGroup className="text-center">
          <Button variant="main" onClick={handleSubmit}>
            Update
          </Button>
          <Button variant="alt" onClick={() => closeModal()}>
            Cancel
          </Button>
        </ButtonGroup>
      )}
    </>
  );

  const tabItems = [];
  if (sourcesWithSettings.length > 0) {
    for (let i = 0; i < sourcesWithSettings.length; i++) {
      tabItems.push({
        value: sourcesWithSettings[i].id.toString(),
        label: sourcesWithSettings[i].name,
        icon:  sourcesWithSettings[i].thumb,
      });
    }
  } else {
    tabItems.push({ value: 'envelope', label: 'Envelope' });
  }
  if (hasMeta) {
    tabItems.push({ value: 'meta', label: 'Meta' });
  }
  if (tpaEnabled || tmpAliasEnabled) {
    tabItems.push({ value: 'parameters', label: 'Link Tracking' });
  }

  const tabs = (
    <ModalTabs
      items={tabItems}
      selected={selectedTab.toString()}
      onChange={(e, v) => {
        setSelectedTab(v);
      }}
    />
  );

  return (
    <Modal
      title="Email Settings"
      tabs={tabs}
      footer={footer}
      bodyStyle={{ minHeight: 240 }}
      {...props}
    >
      <>
        {selectedTab === 'envelope' && (
          <Envelope
            id={id}
            source={source}
            saveTitle={saveTitle}
            onUpdate={onUpdate}
            closeModal={closeModal}
          />
        )}
        {selectedTab.match(/^\d+$/) && (
          <Envelope
            key={selectedTab}
            id={id}
            source={source || sourcesWithSettings.find(s => s.id === Number(selectedTab))}
            saveTitle={saveTitle}
            onUpdate={onUpdate}
            closeModal={closeModal}
          />
        )}
        {selectedTab === 'parameters' && (
          <LinkParams
            id={id}
            onUpdate={onUpdate}
            closeModal={closeModal}
          />
        )}
        {selectedTab === 'meta' && (
          <Meta
            id={id}
            onUpdate={onUpdate}
            closeModal={closeModal}
          />
        )}
      </>
    </Modal>
  );
};

export default EmailSettingsModal;
