import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import EventDispatcher from 'lib/EventDispatcher';
import api from 'lib/api';
import router from 'lib/router';
import { Scrollbars } from 'react-custom-scrollbars';
import renderScrollbars from 'utils/scrollbars';
import { useUIActions } from 'builder/actions/uiActions';
import { Modal, ModalTabs } from 'components';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import Loading from 'components/Loading';
import IntegrationSettings from './IntegrationSettings';
import ParameterSettings from './ParameterSettings';
import ChecklistSettings from './ChecklistSettings';

const dispatcher = new EventDispatcher();
const tabItems = [
  { value: 'integrations', label: 'Integrations' },
  { value: 'parameters', label: 'Link Tracking' },
  { value: 'checklist', label: 'Checklist' },
];

const TemplateSettingsModal = ({ id, closeModal, ...props }) => {
  const uiActions = useUIActions();
  const templateActions = useTemplateActions();
  const [settings, setSettings] = useState(null);
  const [isLoading, setLoading] = useState(true);
  const [tpaEnabled, setTpaEnabled] = useState(false);
  const [savedIntegrations, setSaveIntegrations] = useState(null);
  const [savedParams, setSavedParams] = useState(null);
  const [savedChecklist, setSavedChecklist] = useState(null);
  const [modalStyle, setModalStyle] = useState({ minHeight: 200 });
  const [selectedTab, setSelectedTab] = useState('integrations');
  const modalRef = useRef(null);

  /**
   *
   */
  useEffect(() => {
    api.get(router.generate('template_settings', { id }))
      .then((resp) => {
        setSettings(resp);
        setTpaEnabled(resp.tpaEnabled);

        setTimeout(() => {
          if (modalRef.current && modalRef.current.querySelector('.modal-body')) {
            const height = modalRef.current.querySelector('.modal-body').offsetHeight;
            setModalStyle({
              minHeight: height
            });
          }
        }, 1000);
      })
      .finally(() => {
        setLoading(false);
      });

    const off1 = dispatcher.on('saved-integrations', (integrations) => {
      setSaveIntegrations(integrations);
    });
    const off2 = dispatcher.on('saved-params', (params) => {
      setSavedParams(params);
    });
    const off3 = dispatcher.on('saved-checklist', (checklist) => {
      setSavedChecklist(checklist);
    });

    return () => {
      off1();
      off2();
      off3();
    };
  }, []);

  /**
   *
   */
  useEffect(() => {
    if (savedParams && savedIntegrations && savedChecklist) {
      const body = {
        ...savedIntegrations,
        ...savedParams,
        ...savedChecklist
      };

      api.post(router.generate('template_settings_save', { id }), body)
        .finally(() => {
          uiActions.notice('success', 'Settings saved!');
          setLoading(false);
          closeModal();
        });
    }
  }, [savedIntegrations, savedParams, savedChecklist]);

  /**
   *
   */
  const handleDeleteClick = () => {
    // eslint-disable-next-line max-len
    const content = 'Are you sure you want to delete this template? The template will be removed for all editors. All emails created for it will also be deleted.';
    uiActions.confirm('', content, [
      {
        text:    'Yes',
        variant: 'danger',
        action:  () => {
          closeModal();
          templateActions.deleteTemplate(id);
        }
      },
      {
        text:    'No',
        variant: 'alt',
        action:  () => {}
      }
    ]);
  };

  /**
   *
   */
  const handleSaveClick = () => {
    setLoading(true);
    setSavedParams(null);
    setSaveIntegrations(null);
    setSavedChecklist(null);
    setTimeout(() => {
      dispatcher.trigger('save');
    }, 250);
  };

  const tabs = (
    <ModalTabs
      selected={selectedTab}
      items={tabItems}
      onChange={(e, v) => {
        if (!isLoading) {
          setSelectedTab(v);
        }
      }}
    />
  );

  const footer = (
    <div className="d-flex justify-content-between align-items-center">
      <button
        type="submit"
        onClick={handleSaveClick}
        className="btn-main btn-template-save"
        disabled={false}
      >
        Save changes
      </button>
      <button
        type="button"
        className="btn btn-danger trash btn-template-delete"
        onClick={handleDeleteClick}
      >
        Delete template
      </button>
    </div>
  );

  return (
    <Modal
      title="Template Settings"
      innerRef={modalRef}
      bodyStyle={modalStyle}
      footer={footer}
      tabs={tabs}
      {...props}
    >
      {isLoading && (
        <Loading fixed={false} />
      )}
      <Scrollbars
        renderTrackHorizontal={renderScrollbars.renderTrackHorizontal}
        renderThumbHorizontal={renderScrollbars.renderThumbHorizontal}
      >
        <div style={{ maxHeight: '60vh' }}>
          <IntegrationSettings
            id={id}
            settings={settings}
            onLoading={setLoading}
            dispatcher={dispatcher}
            visible={selectedTab === 'integrations'}
          />
          <ParameterSettings
            id={id}
            settings={settings}
            dispatcher={dispatcher}
            visible={selectedTab === 'parameters'}
            onEnabled={e => setTpaEnabled(e)}
          />
          <ChecklistSettings
            settings={settings}
            dispatcher={dispatcher}
            visible={selectedTab === 'checklist'}
            tpaEnabled={tpaEnabled}
          />
        </div>
      </Scrollbars>
    </Modal>
  );
};

TemplateSettingsModal.propTypes = {
  id:         PropTypes.number.isRequired,
  closeModal: PropTypes.func.isRequired,
};

export default TemplateSettingsModal;
