import React, { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import router from 'lib/router';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import useMe from 'dashboard/hooks/useMe';
import UserMenu from 'components/UserMenu';
import { Icon } from 'components';
import { Input } from 'components/forms';
import { hasParentClass } from 'utils/browser';
import PopupMenuProvider from 'components/PopupMenuProvider';
import NotificationsMenu from 'components/NotificationsMenu';
import HelpButton from 'components/HelpButton';
import SelectOrgs from './SelectOrgs';
import SearchResults from './SearchResults';
import { Container, SettingsAnchor } from './styles';

const Header = () => {
  const me = useMe();
  const templateActions = useTemplateActions();
  const [search, setSearch] = useState('');
  const searchResults = useSelector(state => state.template.searchResults);

  /**
   * @param e
   */
  const handleDocClick = (e) => {
    if (search !== '') {
      if (!hasParentClass(e.target, 'db-search-results')) {
        templateActions.searchEmails(false);
      }
    }
  };

  /**
   *
   */
  useEffect(() => {
    document.body.addEventListener('mousedown', handleDocClick, false);

    return () => {
      document.body.removeEventListener('mousedown', handleDocClick);
    };
  }, [search]);

  /**
   * @param e
   */
  const handleSearchChange = (e) => {
    setSearch(e.target.value);
    templateActions.searchEmails(e.target.value);
  };

  return (
    <Container className="header-nav d-flex align-items-center justify-content-between p-2">
      {/* Left */}
      <div className="d-flex align-items-center w-33">
        <Link to="/" className="header-nav-logo d-flex align-items-center mr-3">
          <img src={router.asset('Blocks-Edit-Symbol.svg')} alt="Blocks Edit" />
        </Link>
        {(me && me.organizations) && (
          <>
            <SelectOrgs organizations={me.organizations} />
            {(me && (me.isOwner || me.isAdmin)) && (
              <SettingsAnchor to="/integrations" className="header-settings-cog pl-2 pr-1 ml-2" title="Integrations.">
                <Icon name="be-symbol-integration" />
              </SettingsAnchor>
            )}
            <SettingsAnchor to="/account" className="header-settings-cog pl-1 pr-2 ml-2" title="Organization settings.">
              <Icon name="be-symbol-settings" />
            </SettingsAnchor>
          </>
        )}
      </div>

      {/* Middle */}
      <div className="d-flex justify-content-center w-33">
        {me && (
          <Input
            id="header-search-emails"
            placeholder="Search Emails"
            icon="be-symbol-search"
            value={search}
            style={{ width: 250 }}
            onChange={handleSearchChange}
          />
        )}
      </div>

      {/* Right */}
      <div className="d-flex align-items-center justify-content-end w-33">
        {me && (
          <PopupMenuProvider>
            {/* <HelpButton /> */}
            <NotificationsMenu />
            <UserMenu />
          </PopupMenuProvider>
        )}
      </div>
      {searchResults.length > 0 && (
        <SearchResults searchResults={searchResults} />
      )}
      <HelpButton />
    </Container>
  );
};

export default Header;
