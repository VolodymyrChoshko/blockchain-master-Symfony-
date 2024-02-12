import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { connect } from 'react-redux';
import { mapDispatchToProps } from 'utils';
import { Icon, Button, Loading } from 'components';
import { builderActions, uiActions } from 'builder/actions';
import SidebarTitle from 'builder/components/SidebarTitle';
import { Container } from '../Draggables/Draggable/styles';

const mapStateToProps = state => ({
  upgrading:       state.builder.upgrading,
  templateVersion: state.builder.templateVersion,
});

@connect(
  mapStateToProps,
  mapDispatchToProps(builderActions, uiActions)
)
export default class Layouts extends React.PureComponent {
  static propTypes = {
    layouts:                 PropTypes.array,
    upgrading:               PropTypes.array.isRequired,
    mode:                    PropTypes.string.isRequired,
    previewDevice:           PropTypes.string.isRequired,
    templateVersion:         PropTypes.number.isRequired,
    builderLayoutLoad:       PropTypes.func.isRequired,
    builderLayoutSave:       PropTypes.func.isRequired,
    builderLayoutDelete:     PropTypes.func.isRequired,
    builderLayoutUpgradeAll: PropTypes.func.isRequired,
    uiPrompt:                PropTypes.func.isRequired,
    uiAlert:                 PropTypes.func.isRequired,
    uiConfirm:               PropTypes.func.isRequired
  };

  static defaultProps = {
    layouts: []
  };

  /**
   * @param {Event} e
   * @param {*} layout
   */
  handleClick = (e, layout) => {
    const { mode, builderLayoutLoad, uiAlert } = this.props;

    if (layout.upgrading) {
      uiAlert('', 'Layout is unavailable while upgrading.');
      return;
    }
    if (mode !== 'template') {
      builderLayoutLoad(layout.id);
    }
  };

  /**
   *
   */
  handleSaveClick = () => {
    const { builderLayoutSave, uiPrompt } = this.props;

    uiPrompt('Layout Title', '', '', (title) => {
      if (title) {
        builderLayoutSave(title);
      }
    });
  };

  /**
   * @param {Event} e
   * @param {*} layout
   */
  handleEditClick = (e, layout) => {
    const { builderLayoutSettings, builderLayoutDelete, uiPrompt, uiConfirm } = this.props;

    e.stopPropagation();

    uiPrompt('Layout Settings', layout.title, '', null, [
      {
        text:      'Delete',
        variant:   'danger',
        className: 'mr-auto',
        action:    () => {
          uiConfirm('', 'Are you sure you want to delete this layout?', () => {
            builderLayoutDelete(layout.id);
          }, { variant: 'danger' });
        }
      },
      {
        text:      'Save',
        variant:   'main',
        className: 'ml-auto',
        action:    (ee, title) => {
          builderLayoutSettings(layout.id, title);
        }
      },
      {
        text:    'Cancel',
        variant: 'alt'
      }
    ]);
  };

  /**
   * @param {Event} e
   * @param {*} layout
   */
  handleUpdateClick = (e, layout) => {
    const { uiConfirm, builderLayoutUpgrade } = this.props;

    e.stopPropagation();
    uiConfirm('', 'There has been an update to the template. Do you want to update your layout to take into account the latest template changes?', [
      {
        text:    'Okay',
        variant: 'main',
        action:  () => {
          builderLayoutUpgrade(layout.id);
        }
      },
      {
        text:    'Later',
        variant: 'alt'
      }
    ]);
  };

  /**
   * @param {Event} e
   */
  handleUpdateAllClick = (e) => {
    const { uiConfirm, builderLayoutUpgradeAll } = this.props;

    e.stopPropagation();
    uiConfirm('', 'There has been an update to the template. Do you want to update your layouts to take into account the latest template changes?', [
      {
        text:    'Okay',
        variant: 'main',
        action:  () => {
          builderLayoutUpgradeAll();
        }
      },
      {
        text:    'Later',
        variant: 'alt'
      }
    ]);
  };

  /**
   * @param {*} layout
   * @returns {*}
   */
  renderLayout = (layout) => {
    const { mode, upgrading, previewDevice } = this.props;

    const classes = classNames('builder-sidebar-draggable-layout position-relative mb-3', {
      'builder-sidebar-draggable-layout':           mode === 'template',
      'builder-sidebar-draggable-layout-upgrading': layout.upgrading
    });
    const title = mode === 'template' ? '' : 'Click to load layout';
    const showUpgradeButton = mode === 'template' || (mode === 'email' && upgrading.length > 0);

    return (
      <Container
        key={layout.id}
        title={title}
        draggable="false"
        className={classes}
        onDragStart={e => e.preventDefault()}
        onClick={e => this.handleClick(e, layout)}
        data-layout-id={layout.id}
      >
        {previewDevice === 'desktop' ? (
          <img src={layout.screenshotDesktop} alt="Desktop screenshot" />
        ) : (
          <img src={layout.screenshotMobile} alt="Mobile screenshot" />
        )}
        {layout.upgrading && (
          <Loading fixed={false} />
        )}
        <div className="d-flex align-items-center">
          <div className="builder-sidebar-draggable-layout-edit">
            {(showUpgradeButton && !layout.isUpgradable) && (
              <span title="This layout can not be updated and needs to be resaved." className="mr-2">
                <Icon name="be-symbol-caution" />
              </span>
            )}
            <Icon
              name="be-symbol-edit"
              title="Edit"
              className="pointer"
              onClick={e => this.handleEditClick(e, layout)}
            />
          </div>

          <p className="layout-title mb-0">
            {layout.title}
          </p>
        </div>
      </Container>
    );
  };

  /**
   * @returns {*}
   */
  render() {
    const { layouts, templateVersion } = this.props;

    let hasUpgrade = false;
    for (let i = 0; i < layouts.length; i++) {
      if (templateVersion > layouts[i].version && layouts[i].isUpgradable) {
        hasUpgrade = true;
        break;
      }
    }

    return (
      <div className="builder-sidebar-draggables-layouts">
        {layouts.length > 0 && (
          <div className="text-center">
            <SidebarTitle>
              Choose a layout as a starting point.
            </SidebarTitle>
            {hasUpgrade && (
              <Button className="text-muted" onClick={this.handleUpdateAllClick}>
                <Icon
                  name="be-symbol-update"
                  title="Update"
                  className="mr-2 pointer"
                />
                Update layouts
              </Button>
            )}
          </div>
        )}
        {layouts.length > 0 && (
          <div className="p-2">
            {layouts.map(this.renderLayout)}
          </div>
        )}
        <div className="text-center">
          {layouts.length > 0 ? (
            <div className="p-2 font-size-sm">
              Save current layout to use for new emails.
            </div>
          ) : (
            <SidebarTitle>
              Save current layout to use for new emails.
            </SidebarTitle>
          )}
          <div className="p-2">
            <Button
              variant="main"
              className="mb-2"
              onClick={this.handleSaveClick}
            >
              Save current layout
            </Button>
          </div>
        </div>
      </div>
    );
  }
}
