export const BLOCK_EDIT                   = 'edit';
export const BLOCK_REGION                 = 'region';
export const BLOCK_SECTION                = 'section';
export const BLOCK_ANCHOR                 = 'anchor';
export const BLOCK_COMPONENT              = 'component';
export const BLOCK_BACKGROUND             = 'background';
export const BLOCK_BG_COLOR               = 'bgcolor';
export const BLOCK_CODE_EDIT              = 'code-edit';
export const BLOCK_RESIZE                 = 'resize';
export const BLOCK_SUPERSCRIPT            = 'superscript';
export const BLOCK_SUBSCRIPT              = 'subscript';
export const CLASS_BLOCK_EDIT             = `block-${BLOCK_EDIT}`;
export const CLASS_BLOCK_SECTION          = `block-${BLOCK_SECTION}`;
export const CLASS_BLOCK_BACKGROUND       = `block-${BLOCK_BACKGROUND}`;
export const CLASS_BLOCK_REGION           = `block-${BLOCK_REGION}`;
export const CLASS_BLOCK_COMPONENT        = `block-${BLOCK_COMPONENT}`;
export const CLASS_BLOCK_CODE_EDIT        = `be-${BLOCK_CODE_EDIT}`;
export const CLASS_BLOCK_BG_COLOR         = `block-${BLOCK_BG_COLOR}`;
export const CLASS_BLOCK_SECTION_EMPTY    = `block-${BLOCK_SECTION}-empty`;
export const CLASS_BLOCK_EDIT_EMPTY       = `block-${BLOCK_EDIT}-empty`;
export const CLASS_BLOCK_ANCHOR           = `block-${BLOCK_ANCHOR}`;
export const CLASS_BLOCK_RESIZE           = `block-${BLOCK_RESIZE}`;
export const CLASS_BLOCK_NO_SUPERSCRIPT   = `block-no-${BLOCK_SUPERSCRIPT}`;
export const CLASS_BLOCK_NO_SUBSCRIPT     = `block-no-${BLOCK_SUBSCRIPT}`;
export const CLASS_BLOCK_REPEAT           = 'block-repeat';
export const CLASS_BLOCK_NO_TEXT          = 'block-no-text';
export const CLASS_BLOCK_NO_LINK          = 'block-no-link';
export const CLASS_BLOCK_NO_BOLD          = 'block-no-bold';
export const CLASS_BLOCK_NO_ITALIC        = 'block-no-italic';
export const CLASS_BLOCK_NO_IMAGE         = 'block-no-image';
export const CLASS_BLOCK_REMOVE           = 'block-remove';
export const CLASS_BLOCK_SCRIPT_SEC       = 'block-script-sec';
export const CLASS_BLOCK_SCRIPT           = 'block-script';
export const DATA_GROUP                   = 'data-group';
export const DATA_STYLE                   = 'data-style';
export const DATA_TITLE                   = 'data-title';
export const DATA_BE_ID                   = 'data-be-id';
export const DATA_BE_DATA                 = 'data-be-data';
export const DATA_BE_KEEP                 = 'data-be-keep';
export const DATA_BE_STYLE_ORIG           = 'data-be-style-orig';
export const DATA_BE_STYLE_INDEX          = 'data-be-style-index';
export const DATA_BE_STYLE_DEFAULT        = 'data-be-style-default';
export const DATA_BLOCK                   = 'data-block';
export const DATA_IMG_ID                  = 'data-be-img-id';
export const DATA_HOSTED                  = 'data-be-hosted';
export const DATA_DROPPABLE               = 'data-be-droppable';
export const DATA_VARIATION_INDEX         = 'data-be-variation-index';
export const DATA_COMPONENT_HIDDEN        = 'data-be-component-hidden';
export const BLOCK_DATA_CAN_REMOVE        = 'rule-can-remove';
export const BLOCK_DATA_HAS_CLONES        = 'has-clones';
export const BLOCK_DATA_IS_CLONE          = 'is-clone';
export const BLOCK_DATA_VARIANT_ID        = 'variant-id';
export const BLOCK_DATA_SUB_VARIANT_INDEX = 'sub-variant-index';
export const BLOCK_DATA_ELEMENT_ID        = 'element-id';
export const BLOCK_DATA_ELEMENT_IGNORE    = 'element-ignore';
export const BLOCK_DATA_IMG_ID            = 'element-img-id';

// The inline styles the truthy class name and falsy class name.
// When the inline style evaluates to truthy add the truthy class, otherwise add
// the falsy class.
// Key is suffix, i.e. -block-edit, -block-region, -block-section, etc.
export const inlineStyles = {
  [BLOCK_EDIT]:       ['edit', ''],
  [BLOCK_REGION]:     ['region', ''],
  [BLOCK_SECTION]:    ['section', ''],
  [BLOCK_COMPONENT]:  ['component', ''],
  [BLOCK_BG_COLOR]:   ['bgcolor', ''],
  [BLOCK_BACKGROUND]: ['background', ''],
  'repeat':           ['repeat', ''],
  'remove':           ['remove', ''],
  'bold':             ['no-bold', 'bold'],
  'italic':           ['no-italic', 'italic'],
  'link':             ['no-link', 'link'],
  'text':             ['no-text', 'text'],
  'preview':          ['preview', ''],
  'minchar':          ['minchar', ''],
  'maxchar':          ['maxchar', '']
};

export const defaultRules = {
  canBold:      true,
  canItalic:    true,
  canLink:      true,
  canText:      true,
  canRepeat:    false,
  canRemove:    false,
  canChangeImg: true,
  isAutoHeight: false,
  isAutoWidth:  false,
  movesUp:      false,
  movesDown:    false,
  isLinkable:   false,
  isEditable:   false,
  minChars:     0,
  maxChars:     0
};

export const DROP_ZONE_BOTTOM_ID_MAGIC_NUM = 5000;
