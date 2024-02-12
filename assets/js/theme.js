import { createGlobalStyle, css } from 'styled-components';
import { lighten, darken } from 'polished';
import styles from '../css/_vars.scss';

/**
 * @param c
 * @returns {FlattenSimpleInterpolation}
 */
export const iconColor = (c) => {
  return css`
    color: ${c};
    fill: ${c};
  `;
};

export const lightTheme = {
  isDarkMode:                 false,
  gutter0:                    '0rem',
  gutter1:                    styles.gutter1,
  gutter2:                    styles.gutter2,
  gutter3:                    styles.gutter3,
  gutter4:                    styles.gutter4,
  gutter5:                    styles.gutter5,
  fontSizeSm:                 styles.fontSizeSm,
  fontSizeMd:                 styles.fontSizeMd,
  fontSizeLg:                 styles.fontSizeLg,
  fontWeightThin:             300,
  fontWeightRegular:          400,
  fontWeightMedium:           500,
  fontWeightBold:             700,
  borderRadiusSm:             styles.borderRadiusSm,
  borderRadiusMd:             styles.borderRadiusMd,
  borderRadiusLg:             styles.borderRadiusLg,
  boxShadow:                  styles.boxShadow,
  zIndexMask:                 10000,
  durationMenus:              styles.durationMenus,
  colorText:                  styles.colorText,
  colorBG:                    '#f6f6f6',
  colorPrimary:               styles.colorPrimary,
  colorSecondary:             styles.colorSecondary,
  colorSuccess:               styles.colorSuccess,
  colorDanger:                styles.colorDanger,
  colorMuted:                 styles.colorMuted,
  colorDark:                  styles.colorDark,
  colorLight:                 styles.colorLight,
  colorLink:                  styles.colorLink,
  colorSelectedBG:            '#e9e9e9',
  colorSubTitle:              '#555555',
  colorBorder:                styles.colorBorder,
  colorBox:                   styles.colorText,
  colorBoxBG:                 '#FFF',
  colorSection:               styles.colorText,
  colorSectionBG:             '#f9f9f9',
  colorSectionBorder:         '#e9e9e9',
  colorToolbarButtonActiveBG: '#ECECEC',
  colorToolbarButtonHoverBG:  '#dfdfdf',
  colorBuilderBG:             '#eeeeee',
  colorBuilderSidebar:        '#414141',
  colorBuilderSidebarMuted:   '#606060',
  colorBuilderSidebarBg:      '#fff',
  colorBtnMain:               '#009bcb',
  colorBtnAlt:                '#525252',
  colorBtnDanger:             '#ee2f29',
  colorBtnEdit:               '#dc4458',
  colorBtnSave:               '#43c6f6',
  colorBtnMainHover:          lighten(0.05, '#009bcb'),
  colorBtnMainActive:         lighten(0.08, '#009bcb'),
  colorBuilderSidebarBorder:  darken(0.1, styles.colorBorder),
  bColorGrid:                 styles.bColorGrid,
  bColorEditHover:            styles.bColorEditHover,
  bColorEditActive:           styles.bColorEditActive,
  bColorSectionHover:         styles.bColorSectionHover,
  bColorSectionActive:        styles.bColorSectionActive,
  bColorRegionHover:          styles.bColorRegionHover,
  bColorRegionActive:         styles.bColorRegionActive,
  bColorComponentHover:       styles.bColorComponentHover,
  bColorComponentActive:      styles.bColorComponentActive,
  widthToolbar:               50,
  widthBuilderSidebar:        300,
  heightBuilderHeader:        65,
};

export const darkTheme = Object.assign({}, lightTheme, {
  isDarkMode:                 true,
  colorText:                  '#cccccc',
  colorBG:                    '#222222',
  colorBox:                   '#cccccc',
  colorBoxBG:                 '#333333',
  colorBorder:                '#393939',
  colorDark:                  '#FFFFFF',
  colorSelectedBG:            '#393939',
  colorSection:               '#cccccc',
  colorSubTitle:              '#dddddd',
  colorSectionBG:             lighten(0.02, '#111111'),
  colorToolbarButtonActiveBG: '#444444',
  colorToolbarButtonHoverBG:  '#5c5c5c',
  colorBuilderBG:             '#222222',
  colorBuilderSidebar:        '#CCCCCC',
  colorBuilderSidebarMuted:   '#999999',
  colorBuilderSidebarBg:      '#3c3c3c',
  colorBuilderSidebarBorder:  lighten(0.1, '#393939'),
});

export const GlobalStyle = createGlobalStyle`
  body {
    color: ${p => p.theme.colorText};
    background-color: ${p => p.theme.colorBG};
    position: relative;
    line-height: 1.2em;
    font-family: Helvetica Neue, Helvetica, Arial, sans-serif;
    text-align: center;
    font-weight: 300;
    letter-spacing: -.2px;
    cursor: default;
    -webkit-font-smoothing: antialiased;
    -webkit-text-size-adjust: 100%;
    text-rendering: optimizeLegibility;
  }

  a {
    color: ${p => p.theme.colorLink};
    text-decoration: none;
    transition: all 200ms ease;
    font-weight: 400;
  }

  h1 {
    padding-bottom: ${p => p.theme.gutter2};
    font-size: 1.5rem;
    color: ${p => p.theme.colorPrimary};
    font-weight: 400;
    line-height: 20px;
  }

  h2 {
    font-size: 1.2rem;
    color: ${p => p.theme.colorSubTitle};
    font-weight: 400;
    line-height: 30px;
  }

  p {
    margin-bottom: ${p => p.theme.gutter2};
  }

  .form-fancy-select-list {
    color: ${p => p.theme.colorBox};
    background-color: ${p => p.theme.colorBoxBG};

    li {
      a {
        color: ${p => p.theme.colorBox};
      }
    }

    &.open {
      background-color: ${p => p.theme.colorBoxBG} !important;
    }
  }

  .form-widget label {
    color: ${p => p.theme.colorText};
  }

  .form-widget.underlined {
    border-bottom: 1px solid ${p => p.theme.colorBorder};
  }

  .modal-tabs {
    background-color: ${p => lighten(0.05, p.theme.colorBoxBG)};
    border-bottom: ${p => p.theme.colorBorder} 1px solid;

    li {
      border-top: ${p => p.theme.colorBorder} 1px solid;
      border-right: ${p => p.theme.colorBorder} 1px solid;
      border-left: ${p => p.theme.colorBorder} 1px solid;

      &.active {
        background-color: ${p => lighten(0.1, p.theme.colorBoxBG)};
      }
    }
  }

  .modal-lightbox h2 {
    color: ${p => p.theme.colorText};
  }

  .modal,
  .modal-source-browser-sources-item-selected,
  .modal-source-browser-sources-item-selected:hover,
  .modal-source-browser-sources-item:hover {
    background-color: ${p => p.theme.isDarkMode ? lighten(0.05, p.theme.colorBoxBG) : darken(0.08, p.theme.colorBoxBG)} !important;
  }

  .file-browser,
  .file-browser-column {
    background-color: ${p => lighten(0.05, p.theme.colorBoxBG)};
  }

  .file-browser-column-list-item:hover {
    background-color: ${p => p.theme.isDarkMode ? lighten(0.1, p.theme.colorBoxBG) : darken(0.1, p.theme.colorBoxBG)} !important;
  }

  .file-browser-column {
    border-right: ${p => p.theme.colorBorder} 1px solid;
  }

  .integration-templates-list li {
    border-bottom: ${p => p.theme.colorBorder} 1px solid;
  }

  .border-bottom {
    border-bottom: ${p => p.theme.colorBorder} 1px solid !important;
  }

  .table-bordered {
    box-shadow: 0 0 0 1px ${p => p.theme.colorBorder};
  }

  .table-striped tbody tr:nth-child(odd) {
    background-color: ${p => lighten(0.1, p.theme.colorBoxBG)};
  }

  .text {
    color: ${p => p.theme.colorText} !important;
  }

  .text-muted {
    color: ${p => p.theme.colorMuted} !important;
  }

  .builder-header {
    border-bottom: 1px solid ${p => p.theme.colorBorder};
  }

  .builder-header-icon {
    ${p => iconColor(p.theme.colorText)};
  }

  .builder-header-help-button a, .builder-header-help-button .btn-icon, .builder-header-help-button .icon {
    ${p => iconColor(p.theme.colorText)};
  }

  .builder-toolbar {
    color: ${p => p.theme.colorBox};
    background-color: ${p => p.theme.colorBoxBG};
    border-right: 1px solid ${p => p.theme.colorBorder};
  }

  .builder-toolbar .builder-toolbar-button {
    &:hover {
      background-color: ${p => p.theme.isDarkMode ? '#5c5c5c' : '#dfdfdf'};
    }

    .icon {
      ${p => iconColor(p.theme.colorText)};
    }
  }

  .builder-toolbar-break {
    border-bottom: 1px solid ${p => p.theme.colorBorder};
  }

  .builder-sidebar {
    color: ${p => p.theme.colorBox};
    background-color: ${p => lighten(0.05, p.theme.colorBoxBG)};
  }

  .builder-body {
    background-color: ${p => p.theme.isDarkMode ? '#222222' : '#eeeeee'};
  }

  .builder-sidebar-draggable-layout-edit {
    ${p => iconColor(p.theme.colorText)};
  }

  .activity-avatar-sm {
    font-weight: 500;
    display: inline-block;
    transition: color 100ms;

    &:hover {
      color: ${p => p.theme.colorLink};
    }

    img {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      vertical-align: middle;
      margin-top: -3px;
    }
  }

  .activity-avatar-sm-initials {
    font-size: 12px;
    line-height: 1;
    width: 20px;
    height: 20px;
    overflow: hidden;
    border-radius: 100%;
    font-weight: 500;
    color: #fff;
    vertical-align: middle;
    text-align: center;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(#67CF96, #91CF5C);
  }
`;
