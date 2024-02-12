import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import api from 'lib/api';
import router from 'lib/router';
import { useUIActions } from 'builder/actions/uiActions';
import { loading } from 'utils';
import Modal from 'components/Modal';
import Button from 'components/Button';
import { Form, Icon } from './styles';

const evaluateScripts = (html) => {
  const div = document.createElement('DIV');
  div.innerHTML = html;
  Array.from(div.querySelectorAll('script')).forEach((oldScript) => {
    if (oldScript.getAttribute('data-adq-execute-in-builder') === 'false') {
      return;
    }
    const newScript = document.createElement('script');
    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
    document.body.appendChild(newScript);
  });

  return html;
};

const IntegrationSettings = ({ source, closeModal, ...props }) => {
  const uiActions = useUIActions();
  const [formBody, setFormBody] = useState('');
  const formRef = useRef();

  /**
   *
   */
  useEffect(() => {
    api.get(router.generate('integrations_settings', { sid: source.id }))
      .then((resp) => {
        if (resp.html) {
          setFormBody(resp.html);
        }
        setTimeout(() => {
          if (resp.scripts) {
            // eslint-disable-next-line no-eval
            eval(resp.scripts);
          }
        }, 500);
      });
  }, []);

  /**
   * @param e
   */
  const handleSubmit = (e) => {
    e.preventDefault();
    loading(true);
    const formData = new FormData(e.target);
    api.post(router.generate('integrations_settings', { sid: source.id }), formData)
      .then(() => {
        uiActions.notice('success', 'Settings updated!');
      })
      .finally(() => {
        loading(false);
      });
  };

  /**
   *
   */
  const handleSaveClick = () => {
    const btn = formRef.current.querySelector('button[type="submit"]');
    if (btn) {
      btn.click();
    }
  };

  const title = (
    <div className="d-flex align-items-center">
      <Icon src={source.integration.iconURL} className="mr-2" alt="" />
      <span>{source.name}</span>
    </div>
  );

  const footer = (
    <>
      <Button variant="main" onClick={handleSaveClick}>
        Save
      </Button>
    </>
  );

  return (
    <Modal title={title} footer={footer} bodyStyle={{ paddingTop: '0', paddingBottom: '0' }} {...props}>
      <Form
        ref={formRef}
        onSubmit={handleSubmit}
        className={`form-integration-${source.integration.slug} p-3`}
        dangerouslySetInnerHTML={{ __html: evaluateScripts(formBody) }}
      />
    </Modal>
  );
};

IntegrationSettings.propTypes = {
  source:     PropTypes.object.isRequired,
  closeModal: PropTypes.func.isRequired
};

export default IntegrationSettings;
