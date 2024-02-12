import styles from '../../css/_vars.scss';

export const breakpoints = {
  mobile:   parseInt(styles.breakpointsMobile, 10),
  handheld: parseInt(styles.breakpointsHandheld, 10),
  tablet:   parseInt(styles.breakpointsTablet, 10),
  laptop:   parseInt(styles.breakpointsLaptop, 10),
  desktop:  parseInt(styles.breakpointsDesktop, 10)
};

export const deviceHeights = {
  mobile:   480,
  handheld: 540,
  tablet:   960,
  laptop:   720,
  desktop:  1200
};

export const builder = {
  colorSection:    styles.colorSection,
  headerHeight:    parseInt(styles.headerHeight, 10),
  menuBarHeight:   parseInt(styles.menuBarHeight, 10),
  menuBarWidth:    parseInt(styles.menuBarHeight, 10),
  menuWidth:       parseInt(styles.menuWidth, 10),
  menuOffset:      parseInt(styles.menuOffset, 10),
  toolbarWidth:    parseInt(styles.widthToolbar, 10),
  durationMenus:   parseInt(styles.durationMenus, 10),
  durationSidebar: parseInt(styles.durationSidebar, 10),
  widthSidebar:    parseInt(styles.widthSidebar, 10)
};
