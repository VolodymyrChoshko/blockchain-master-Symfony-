import React, { useEffect, createContext, useState } from 'react';
import PropTypes from 'prop-types';

export const PopupMenuContext = createContext({
  opened:    '',
  setOpened: (s) => console.log(s),
});

const PopupMenuProvider = ({ children }) => {
  const [openedMenu, setOpenedMenu] = useState('');

  /**
   *
   */
  useEffect(() => {
    let mount = document.getElementById('builder-popup-menu-mount');
    if (!mount) {
      mount = document.createElement('div');
      mount.id = 'builder-popup-menu-mount';
      document.body.appendChild(mount);
    }
  }, []);

  const menuContext = {
    opened:    openedMenu,
    setOpened: setOpenedMenu
  };

  return (
    <PopupMenuContext.Provider value={menuContext}>
      {children}
    </PopupMenuContext.Provider>
  );
};

PopupMenuProvider.propTypes = {
  children: PropTypes.node.isRequired,
};

export default PopupMenuProvider;
