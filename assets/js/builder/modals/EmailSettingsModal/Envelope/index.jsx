import React, { useEffect, useState, useRef } from 'react';
import { DocMeta } from 'builder/engine';
import api from 'lib/api';
import router from 'lib/router';
import IntegrationHook from 'builder/components/IntegrationHook';
import { Input, Textarea, Widget } from 'components/forms';
import { hasIntegrationRule } from 'lib/integrations';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import { loading } from 'utils';
import browser from 'utils/browser';

const Envelope = ({ id, source, saveTitle, onUpdate, closeModal }) => {
  const titleRef = useRef(null);
  const formRef = useRef(null);
  const isChangedRef = useRef(false);
  const [title, setTitle] = useState('');
  const [preview, setPreview] = useState('');
  const iframe = useSelector(state => state.builder.iframe);
  const emailVersion = useSelector(state => state.builder.emailVersion);
  const sources = useSelector(state => state.source.sources);
  const widgets = useRef([]);

  /**
   *
   */
  useEffect(() => {
    if (!iframe) {
      const url = `${router.generate('build_email_settings', { id })}?version=${emailVersion}`;
      api.get(url)
        .then((settings) => {
          setTitle(settings.title);
          setPreview(settings.preview);

          setTimeout(() => {
            if (titleRef.current) {
              titleRef.current.focus();
            }
          }, 100);
        });
    } else {
      const doc      = browser.iFrameDocument(iframe);
      const settings = DocMeta.getTitleAndPrefix(doc);
      setTitle(settings.title);
      setPreview(settings.preview);

      setTimeout(() => {
        if (titleRef.current) {
          titleRef.current.focus();
        }
      }, 100);
    }
  }, [iframe, emailVersion, id]);

  /**
   * @param formValues
   * @param {boolean} close
   */
  const handleUpdate = (formValues = {}, close = true) => {
    const sanitized = {};
    const integrationSettings = {};
    Object.keys(formValues).forEach((key) => {
      if (key.match(/^\d+\.\d+\./)) {
        integrationSettings[key] = formValues[key];
      } else {
        sanitized[key] = formValues[key];
      }
    });

    loading(true, false);
    const url = `${router.generate('build_email_settings', { id })}?version=${emailVersion}`;
    if (!iframe) {
      sanitized.integrationSettings = integrationSettings;
      api.post(url, sanitized)
        .then(() => {
          if (onUpdate) {
            onUpdate(sanitized);
          }
          if (close) {
            closeModal();
          }
        })
        .finally(() => {
          loading(false);
        });
    } else {
      const doc      = browser.iFrameDocument(iframe);
      const settings = DocMeta.getTitleAndPrefix(doc);
      if (settings.domTitle && sanitized.title !== undefined) {
        settings.domTitle.innerHTML = sanitized.title;
      }
      if (settings.domPreview && sanitized.preview !== undefined) {
        settings.domPreview.innerHTML = sanitized.preview;
      }

      if (sanitized.title !== undefined) {
        const body = {
          integrationSettings,
        };
        if (saveTitle) {
          body.title = sanitized.title;
          body.preview = sanitized.preview || '';
        }

        api.post(url, body)
          .then(() => {
            if (onUpdate) {
              onUpdate(sanitized);
            }
            if (close) {
              closeModal();
            }
          })
          .finally(() => {
            loading(false);
          });
      } else {
        loading(false);
        if (onUpdate) {
          onUpdate(sanitized);
        }
        if (close) {
          closeModal();
        }
      }
    }
  };

  /**
   * @param e
   */
  const handleModalSubmit = (e) => {
    if (e) {
      e.preventDefault();
    }
    handleUpdate(browser.serializeForm(formRef.current), e !== null);
  };

  /**
   *
   */
  const handleIntegrationChange = () => {
    isChangedRef.current = true;
  };

  /**
   *
   */
  useEffect(() => {
    document.addEventListener('emailSettingsModalSubmit.envelope', handleModalSubmit, false);
    if (source) {
      document.addEventListener(`emailSettingsModalSubmit.${source.id}`, handleModalSubmit, false);
    }

    return () => {
      if (isChangedRef.current) {
        handleModalSubmit(null);
        for (let i = 0; i < widgets.current.length; i++) {
          widgets.current[i].removeEventListener('input', handleIntegrationChange);
        }
      }

      document.removeEventListener('emailSettingsModalSubmit.envelope', handleModalSubmit);
      if (source) {
        document.removeEventListener(`emailSettingsModalSubmit.${source.id}`, handleModalSubmit);
      }
    };
  }, [iframe, id, emailVersion, saveTitle, source]);

  /**
   *
   */
  const handleIntegrationComplete = (f) => {
    widgets.current = [];
    const inputs = f.querySelectorAll('input, textarea, select');
    for (let i = 0; i < inputs.length; i++) {
      inputs[i].addEventListener('input', handleIntegrationChange);
      widgets.current.push(inputs[i]);
    }
  };

  let titleRequired = false;
  if (source) {
    titleRequired = hasIntegrationRule(source, 'export_title_required');
  }

  return (
    <form ref={formRef}>
      <Widget label="Subject" htmlFor="email-settings-input-subject">
        <Input
          name="title"
          value={title || ''}
          innerRef={titleRef}
          id="email-settings-input-subject"
          onChange={e => {
            setTitle(e.target.value);
            isChangedRef.current = true;
          }}
          spellCheck="true"
          required={titleRequired}
        />
      </Widget>
      {preview !== undefined && (
        <Widget label="Preview Text" htmlFor="email-settings-input-preview">
          <Textarea
            value={preview || ''}
            name="preview"
            id="email-settings-input-preview"
            style={{ height: 100 }}
            onChange={e => {
              setPreview(e.target.value);
              isChangedRef.current = true;
            }}
          />
        </Widget>
      )}
      {source ? (
        <IntegrationHook hook="email_settings" source={source} onComplete={handleIntegrationComplete} />
      ) : (
        <IntegrationHook hook="email_settings" sources={sources} onComplete={handleIntegrationComplete} />
      )}
    </form>
  );
};

Envelope.propTypes = {
  id:         PropTypes.number,
  source:     PropTypes.object,
  saveTitle:  PropTypes.bool,
  onUpdate:   PropTypes.func,
  closeModal: PropTypes.func
};

export default Envelope;
