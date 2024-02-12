import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import { Scrollbars } from 'react-custom-scrollbars';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import { useUIActions } from 'builder/actions/uiActions';
import router from 'lib/router';
import useMe from 'dashboard/hooks/useMe';
import useTemplate from 'dashboard/hooks/useTemplate';
import { scrollIntoView, hasParentClass } from 'utils/browser';
import Input from 'components/forms/Input';
import Icon from 'components/Icon';
import Button from 'components/Button';
import { getFormattedDate, formatAMPM } from 'dashboard/utils/dates';
import { Container, Selected, Dropdown, Item, Thumbnail, UpdatedAt, OptionButton } from './styles';

const TemplateDropdown = ({ template, templates, initialRenaming, onChange }) => {
  const me = useMe();
  const uiActions = useUIActions();
  const templateActions = useTemplateActions();
  const [isOpen, setOpen] = useState(false);
  const [isRenaming, setRenaming] = useState(false);
  const [realTemplates, setRealTemplates] = useState([]);
  const [renameValue, setRenameValue] = useState('');
  const { people } = useTemplate(template?.id);
  const titleRef = useRef(null);
  const prevOpenRef = useRef(false);
  let found = null;
  if (people && people.length > 0) {
    found = people.find(p => p && p.id && (p.id === me.id));
  }

  /**
   *
   */
  useEffect(() => {
    if (templates) {
      const combined = templates.filter((t) => {
        if (!t.updatedAt) {
          t.updatedAt = t.createdAt;
        }

        return t;
      });
      const rt = combined.sort((a, b) => {
        return b.updatedAt - a.updatedAt;
      });
      setRealTemplates(rt);
    }
  }, [templates, template]);

  /**
   *
   */
  useEffect(() => {
    /**
     * @param e
     */
    const handleDocClick = (e) => {
      if (isOpen && !hasParentClass(e.target, 'db-no-deactivate') && !e.defaultPrevented && !isRenaming) {
        setOpen(false);
        setRenaming(false);
      }
    };

    document.addEventListener('click', handleDocClick, false);

    return () => {
      document.removeEventListener('click', handleDocClick);
    };
  }, [isOpen]);

  /**
   *
   */
  useEffect(() => {
    setRenaming(initialRenaming);
    if (initialRenaming) {
      setOpen(true);
      setTimeout(() => {
        titleRef.current.focus();
      }, 1000);
    }
  }, [initialRenaming]);

  /**
   *
   */
  useEffect(() => {
    if (isOpen && !prevOpenRef.current && template) {
      const el = document.getElementById(`template-item-${template.id}`);
      scrollIntoView(el);
    }
    prevOpenRef.current = isOpen;
  }, [isOpen, template]);

  /**
   * @param e
   */
  const handleRenameClick = (e) => {
    e.stopPropagation();
    setRenameValue(template ? template.title : '');
    setRenaming(true);
  };

  /**
   * @param e
   */
  const handleSaveClick = (e) => {
    e.stopPropagation();
    const title = renameValue.trim();
    if (!title) {
      uiActions.alert('Error', 'The template needs a title.');
      return;
    }

    if (template) {
      templateActions.updateTemplate(template.id, title);
      setTimeout(() => {
        setRenaming(false);
        if (initialRenaming) {
          setOpen(false);
        }
      }, 500);
    }
  };

  /**
   * @param e
   */
  const handleCancelClick = (e) => {
    e.stopPropagation();
    if (template) {
      setRenaming(false);
      if (initialRenaming) {
        setOpen(false);
        templateActions.updateTemplate(template.id, 'Template');
      }
    }
  };

  /**
   *
   */
  const handleSettingsClick = () => {
    if (template) {
      uiActions.uiModal('templateSettings', true, {
        id: template.id
      });
    }
  };

  let id = 0;
  let title = 'Template';
  if (template) {
    ({ id, title } = template);
  }

  let height = 66 * templates.length;
  if (height > 300) {
    height = 300;
  }
  const scrollbarStyles = {
    width: '100%',
    height
  };

  return (
    <div className="d-flex align-items-center">
      <Container className="mr-4">
        <Selected style={{ visibility: isOpen ? 'hidden' : 'visible' }} onClick={() => setOpen(true)}>
          <span>{title}</span>
          <Icon
            key="caret"
            className="icon-selector pointer ml-2"
            name={isOpen ? 'be-symbol-arrow-up' : 'be-symbol-arrow-down'}
            onClick={() => setOpen(true)}
          />
        </Selected>
        {isOpen && (
          <Dropdown>
            <Selected className="opened" onClick={() => setOpen(true)}>
              {isRenaming ? (
                <div className="d-flex align-items-center w-100 db-no-deactivate">
                  <Input
                    innerRef={titleRef}
                    id="input-template-rename"
                    className="mr-1"
                    value={renameValue}
                    onChange={e => setRenameValue(e.target.value)}
                    onKeyUp={(e) => {
                      if (e.key === 'Enter') {
                        handleSaveClick(e);
                      }
                    }}
                  />
                  <Button variant="main" className="mr-1" onClick={handleSaveClick}>Save</Button>
                  <Button variant="alt" onClick={handleCancelClick}>Cancel</Button>
                </div>
              ) : (
                <>
                  <span>{title}</span>
                  <div className="db-no-deactivate" onClick={handleRenameClick}>RENAME</div>
                </>
              )}
            </Selected>
            <Scrollbars style={scrollbarStyles}>
              {realTemplates.map((t) => {
                if (!t.title) {
                  return null;
                }
                return (
                  <Item
                    key={t.id}
                    id={`template-item-${t.id}`}
                    selected={t.id === id}
                    onClick={e => onChange(e, t.id)}
                  >
                    <div className="d-flex flex-column justify-content-center">
                      {t.title}
                      <UpdatedAt>
                        Latest {getFormattedDate(new Date((t.updatedAt) * 1000))}
                        &nbsp;at {formatAMPM(new Date((t.updatedAt) * 1000))}
                      </UpdatedAt>
                    </div>
                    <Thumbnail>
                      <img src={t.thumbnailSm} alt="" />
                    </Thumbnail>
                  </Item>
                );
              })}
            </Scrollbars>
          </Dropdown>
        )}
      </Container>
      {template && (
        <>
          {found && (found.isOwner || found.isAdmin) && (
            <OptionButton
              as={Link}
              className="btn btn-transparent pl-3"
              to={router.generate('build_template', { id })}
            >
              <Icon name="be-symbol-edit" />
            </OptionButton>
          )}
          {found && (found.isOwner || found.isAdmin) && (
            <OptionButton variant="transparent" className="pl-2" onClick={handleSettingsClick}>
              <Icon name="be-symbol-preferences" />
            </OptionButton>
          )}
        </>
      )}
    </div>
  );
};

TemplateDropdown.propTypes = {
  template:        PropTypes.object,
  templates:       PropTypes.array.isRequired,
  initialRenaming: PropTypes.bool,
  onChange:        PropTypes.func.isRequired
};

TemplateDropdown.defaultProps = {};

export default TemplateDropdown;
