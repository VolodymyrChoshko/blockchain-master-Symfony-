import React, { useState, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { SlideDown } from 'react-slidedown';
import Switch from 'components/Switch';
import { uiActions, sourceActions } from 'builder/actions';
import { mapDispatchToProps } from 'utils';
import api from 'lib/api';
import router from 'lib/router';
import { serializeFormFields } from 'utils/browser';

const IntegrationSettings = ({
  uiModal,
  settings,
  visible,
  dispatcher,
  sourceActiveSourceID,
  sourceSources
}) => {
  const [enabled, setEnabled] = useState({});
  const [homeDirs, setHomeDirs] = useState({});
  const [sources, setSources] = useState([]);
  const [isDisabled, setDisabled] = useState(false);
  const extraFields = useRef(null);

  useEffect(() => {
    if (settings) {
      const _enabled  = {};
      const _homeDirs = {};
      settings.sources.forEach((s) => {
        _enabled[s.id]  = s.isEnabled;
        _homeDirs[s.id] = s.homeDir;
      });
      setEnabled(_enabled);
      setHomeDirs(_homeDirs);
      setSources(settings.sources);
      setDisabled(settings.isIntegrationsDisabled);
    }
  }, [settings]);

  /**
   * @param {Event} e
   * @param {number} sid
   */
  const handleEnableChange = (e, sid) => {
    const newChecked = { ...enabled };
    newChecked[sid] = e.target.checked;
    setEnabled(newChecked);
  };

  /**
   * @param {Event} e
   * @param {number} sid
   */
  const handleHomeDirChange = (e, sid) => {
    const newHomeDirs = { ...homeDirs };
    newHomeDirs[sid] = e.target.value;
    setHomeDirs(newHomeDirs);
  };

  /**
   * @param {Event} e
   * @param {number} sid
   */
  const handleBrowseClick = (e, sid) => {
    sourceActiveSourceID(sid);

    api.get(router.generate('integrations_enabled'))
      .then((resp) => {
        sourceSources(resp);

        uiModal('sourceBrowse', true, {
          selectType: 'folder',
          onChoose:   (folder) => {
            uiModal('sourceBrowse', false);
            const newHomeDirs = { ...homeDirs };
            newHomeDirs[sid] = folder;
            setHomeDirs(newHomeDirs);
          }
        });
      });
  };

  useEffect(() => {
    return dispatcher.on('save', () => {
      const body = {
        extraFields: {},
        enabled,
        homeDirs,
      };

      if (extraFields.current) {
        const fields = extraFields.current.querySelectorAll('input, select, textarea');
        body.extraFields = serializeFormFields(fields);
      }

      dispatcher.trigger('saved-integrations', body);
    });
  }, [enabled, homeDirs, extraFields]);

  if (!visible) {
    return null;
  }

  return (
    <div>
      <div className="modal-lightbox" style={{ padding: '4px 0 0 0' }}>
        <ul className="integration-templates-list">
          {sources.map(source => (
            <li key={source.id} className="integration-templates-list-item" data-sid={source.id}>
              <img
                alt="Icon"
                className="integration-icon"
                src={source.thumb}
              />
              <h2 style={{ display: 'inline-block' }}>
                {source.name}
              </h2>
              <Switch
                id={`enabled-${source.id}`}
                name={`enabled-${source.id}`}
                checked={enabled[source.id]}
                disabled={isDisabled}
                onChange={e => handleEnableChange(e, source.id)}
              />
              {source.extraFields && (
                <SlideDown
                  closed={!enabled[source.id]}
                  className="integration-templates-options"
                  transitionOnAppear={false}
                >
                  <div
                    ref={extraFields}
                    style={{ width: '100%' }}
                    dangerouslySetInnerHTML={{ __html: source.extraFields }}
                  />
                </SlideDown>
              )}
              {source.homeDirPlaceholder && (
                <SlideDown
                  closed={!enabled[source.id]}
                  className="integration-templates-options"
                  transitionOnAppear={false}
                >
                  <input
                    type="text"
                    name="home"
                    value={homeDirs[source.id]}
                    className="integration-templates-options-home-dir"
                    id={`integration-home-${source.id}`}
                    placeholder={source.homeDirPlaceholder}
                    onChange={e => handleHomeDirChange(e, source.id)}
                    disabled={isDisabled}
                  />
                  <button
                    type="button"
                    className="btn-alt integration-templates-options-browse-btn mr-2"
                    onClick={e => handleBrowseClick(e, source.id)}
                    disabled={isDisabled}
                  >
                    Browse
                  </button>
                </SlideDown>
              )}
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

IntegrationSettings.propTypes = {
  settings:   PropTypes.object,
  visible:    PropTypes.bool.isRequired,
  dispatcher: PropTypes.object.isRequired
};

export default connect(
  null,
  mapDispatchToProps(uiActions, sourceActions)
)(IntegrationSettings);
