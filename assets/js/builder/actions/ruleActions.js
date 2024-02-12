import { RuleEngine } from 'builder/engine';
import api from 'lib/api';
import router from 'lib/router';
import { useMemo } from 'react';
import { bindActionCreators } from 'redux';
import { useDispatch } from 'react-redux';
import { actions as builderActions } from 'builder/actions/builderActions';

export const actions = {};
export const types = {
  CANCEL_EDITING:      'RULES_CANCEL_EDITING',
  SET_EDITING:         'RULES_SET_EDITING',
  SET_SAVING:          'RULES_SET_SAVING',
  SET_EDITING_HTML:    'RULES_SET_EDITING_HTML',
  SET_FILTERING_HTML:  'RULES_SET_FILTERING_HTML',
  SET_ACTIVE_EDIT:     'RULES_SET_ACTIVE_EDIT',
  SET_ACTIVE_SECTIONS: 'RULES_SET_ACTIVE_SECTIONS',
  SET_HOVER_EDITS:     'RULES_SET_HOVER_EDITS',
  SET_ZONES:           'RULES_SET_ZONES',
  SET_MODE:            'RULES_SET_MODE',
  SET_EXPANDED_HTML:   'RULES_SET_EXPANDED_HTML',
  UPDATE_BRACE_HTML:   'RULES_UPDATE_BRACE_HTML',
  SET_CHANGED:         'RULES_SET_CHANGED',
};

/**
 * @returns {{isEditing: boolean, type: string, zones: {}}}
 */
actions.cancelEditing = () => {
  return (dispatch) => {
    dispatch({
      type:         types.CANCEL_EDITING,
      isCancelling: true,
    });
    dispatch(actions.setEditingHtml(false));
    dispatch(builderActions.cancelEditing());
    dispatch(actions.setChanged(false));
    setTimeout(() => {
      dispatch({
        type:         types.CANCEL_EDITING,
        isCancelling: false,
      });
    }, 2000);
  };
};

/**
 * @param isEditing
 * @returns {{isEditing, type: string}}
 */
actions.setEditing = (isEditing) => {
  return async (dispatch, getState) => {
    const { builder } = getState();

    if (!isEditing) {
      RuleEngine.restoreElements();
      dispatch(actions.setEditingHtml(false));
      dispatch({
        type:     types.SET_SAVING,
        isSaving: true,
      });
      await dispatch(builderActions.saveTemplate(() => {
        dispatch({
          type:  types.SET_EDITING,
          zones: {},
          isEditing,
        });
        dispatch({
          type:     types.SET_SAVING,
          isSaving: false,
        });
      }));
    } else {
      dispatch(builderActions.cancelEditing());
      dispatch(builderActions.setHTML(builder.origHtml));

      RuleEngine.setIframe(builder.iframe);
      const zones = RuleEngine.findBlocks();
      RuleEngine.restoreElements();
      dispatch({
        type: types.SET_EDITING,
        isEditing,
        zones,
      });

      if (builder.isFirstRulesEdit) {
        await api.post(router.generate('build_template_rules_edit_start'), {});
      }

      setTimeout(() => {
        dispatch({
          type:  types.SET_ZONES,
          zones: RuleEngine.findBlocks(),
        });
      }, 500);
      setTimeout(() => {
        dispatch({
          type:  types.SET_ZONES,
          zones: RuleEngine.findBlocks(),
        });
      }, 1500);
      setTimeout(() => {
        dispatch({
          type:  types.SET_ZONES,
          zones: RuleEngine.findBlocks(),
        });
      }, 2500);
    }
  };
};

/**
 * @returns {(function(*))|*}
 */
actions.frameResize = () => {
  return (dispatch, getState) => {
    if (getState().rules.isEditingHtml) {
      return;
    }
    const zones = RuleEngine.findBlocks();
    dispatch({
      type: types.SET_ZONES,
      zones,
    });
  };
};

/**
 * @param mode
 * @returns {{mode, type: string}}
 */
actions.setMode = (mode) => {
  return (dispatch) => {
    dispatch({
      type: types.SET_MODE,
      mode,
    });
    RuleEngine.setMode(mode);
    const zones = RuleEngine.findBlocks();
    dispatch({
      type: types.SET_ZONES,
      zones,
    });
  };
};

/**
 * @param zones
 * @returns {{type: string, zones}}
 */
actions.setZones = (zones) => ({
  type: types.SET_ZONES,
  zones,
});

/**
 * @returns {(function(*): void)|*}
 */
actions.deselectAll = () => {
  return (dispatch) => {
    dispatch(actions.setActiveEdit([]));
    dispatch(actions.setActiveSections([]));
  };
};

/**
 * @param activeEdits
 * @returns {(function(*): void)|*}
 */
actions.setActiveEdit = (activeEdits) => {
  return (dispatch) => {
    dispatch({
      type: types.SET_ACTIVE_EDIT,
      activeEdits,
    });
  };
};

/**
 * @param activeSections
 * @returns {(function(*): void)|*}
 */
actions.setActiveSections = (activeSections) => {
  return (dispatch) => {
    dispatch({
      type: types.SET_ACTIVE_SECTIONS,
      activeSections,
    });
  };
};

/**
 * @param hoverEdits
 * @returns {{hoverEdits, type: string}}
 */
actions.setHoverEdits = (hoverEdits) => ({
  type: types.SET_HOVER_EDITS,
  hoverEdits,
});

/**
 * @param isEditingHtml
 * @returns {(function(*): void)|*}
 */
actions.setEditingHtml = (isEditingHtml) => {
  return (dispatch) => {
    if (isEditingHtml) {
      RuleEngine.restoreElements();
    }
    dispatch({
      type: types.SET_EDITING_HTML,
      isEditingHtml,
    });
    if (!isEditingHtml) {
      setTimeout(() => {
        dispatch(actions.setZones(RuleEngine.findBlocks()));
      }, 100);
    }
  };
};

/**
 * @param isExpandedHtml
 * @returns {{isExpandedHtml, type: string}}
 */
actions.setExpandedHtml = (isExpandedHtml = -1) => {
  return (dispatch) => {
    dispatch({
      type: types.SET_EXPANDED_HTML,
      isExpandedHtml,
    });
  };
};

/**
 * @param braceHtml
 * @returns {{braceHtml, type: string}}
 */
actions.updateBraceHtml = (braceHtml) => ({
  type: types.UPDATE_BRACE_HTML,
  braceHtml,
});

/**
 * @param isFilteringHtml
 * @returns {(function(*): void)|*}
 */
actions.setFilteringHtml = (isFilteringHtml) => {
  return (dispatch) => {
    dispatch({
      type: types.SET_FILTERING_HTML,
      isFilteringHtml,
    });
  };
};

/**
 * @param isChanged
 * @returns {{isChanged, type: string}}
 */
actions.setChanged = (isChanged) => {
  return {
    type: types.SET_CHANGED,
    isChanged,
  };
};

/**
 * @returns {{}}
 */
export const useRuleActions = () => {
  const dispatch = useDispatch();

  return useMemo(() => bindActionCreators(actions, dispatch), [dispatch]);
};
