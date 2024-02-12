import React, { useRef } from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import { useUIActions } from 'builder/actions/uiActions';
import { useSourceActions } from 'builder/actions/sourceActions';
import { Modal, Icon, Button } from 'components';
import api from 'lib/api';
import router from 'lib/router';
import { FileInput } from './styles';

const NewTemplateModal = (props) => {
  const uiActions = useUIActions();
  const templateActions = useTemplateActions();
  const sourceActions = useSourceActions();
  const formRef = useRef(null);
  const fileInputRef = useRef(null);
  const hasSources = useSelector(state => state.template.hasSources);
  const activeSourceID = useSelector(state => state.source.activeSourceID);

  /**
   *
   */
  const handleNewTemplateSubmit = () => {
    props.closeModal();
    const formData = new FormData(formRef.current);
    formData.append('name', 'template');
    templateActions.uploadTemplate(formData, (id) => {
      history.pushState('', '', `/t/${id}`);
      const popStateEvent = new PopStateEvent('popstate', { state: {} });
      dispatchEvent(popStateEvent);
    });
  };

  /**
   *
   */
  const handleNewTemplateClick = () => {
    fileInputRef.current.click();
  };

  /**
   *
   */
  const handleSourceClick = () => {
    props.closeModal();

    api.get(router.generate('integrations_enabled'))
      .then((resp) => {
        sourceActions.sources(resp);
        if (!activeSourceID && resp.length > 0) {
          sourceActions.activeSourceID(parseInt(resp[0].id, 10));
        }

        uiActions.modal('sourceBrowse', true, {
          selectType: 'html',
          onChoose:   () => {
            uiActions.modal('sourceBrowse', false);
          },
          onSelectFile: (file) => {
            const body = {
              cmd:  'import',
              args: [file.path],
              iid:  activeSourceID
            };

            uiActions.modal('sourceBrowse', false);

            api.post(router.generate('integrations_sources'), body)
              .then((resp2) => {
                if (resp2.redirect) {
                  if (!resp2.oauth) {
                    document.location = resp2.redirect;
                  }

                  uiActions.confirm('Redirecting', `Please re-authenticate with ${resp2.name} to continue.`, [
                    {
                      text:    'Ok',
                      variant: 'main',
                      action:  () => {
                        document.location = resp2.redirect;
                      }
                    }
                  ]);
                }

                const formData = new FormData();
                formData.append('name', 'template');
                formData.append('html', resp2.html);
                formData.append('filename', resp2.file);
                templateActions.uploadTemplate(formData, (id) => {
                  history.pushState('', '', `/t/${id}`);
                  const popStateEvent = new PopStateEvent('popstate', { state: {} });
                  dispatchEvent(popStateEvent);
                });
              });
          }
        });
      });
  };

  return (
    <Modal title="New Template" {...props} animation="zoomIn" auto sm>
      <div className="mb-2 mt-2">
        <form ref={formRef} method="POST" encType="multipart/form-data">
          <Button
            variant="transparent"
            className="font-size-lg d-flex align-items-center"
            onClick={handleNewTemplateClick}
          >
            <Icon name="be-symbol-export" className="mr-2" />
            Upload New Template
          </Button>
          <FileInput
            ref={fileInputRef}
            type="file"
            name="template"
            className="db-template-upload-input"
            value=""
            accept=".html,.zip"
            onChange={handleNewTemplateSubmit}
          />
        </form>
      </div>
      {hasSources && (
        <div className="mb-2">
          <Button
            variant="transparent"
            className="font-size-lg d-flex align-items-center"
            onClick={handleSourceClick}
          >
            <Icon name="be-symbol-host" className="mr-2" />
            Import from Source
          </Button>
        </div>
      )}
      <p className="font-size-lg mt-3" style={{ lineHeight: '1.5rem' }}>
        After uploading your HTML, you will be taken to the template editor where you can select and enable
        editing options for your team to build individual emails and add in content.&nbsp;
        <a href="https://blocksedit.com/developer/" target="_blank" rel="noopener noreferrer">See how it works →</a>
      </p>
    </Modal>
  );
};

NewTemplateModal.propTypes = {
  closeModal: PropTypes.func
};

export default NewTemplateModal;
