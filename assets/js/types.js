/**
 * @typedef {Object} IntegrationSourceSettingsRules
 * @property {boolean} can_list_folders
 * @property {boolean} export_settings_show
 */

/**
 * @typedef {Object} IntegrationSourceSettings
 * @property {array} hooks
 * @property {IntegrationSourceSettingsRules} rules
 */

/**
 * @typedef {Object} IntegrationSource
 * @property {number} id
 * @property {string} name
 * @property {string} thumb
 * @property {IntegrationSourceSettings} settings
 */

/**
 * @typedef Organization
 * @property {number} org_id
 * @property {string} org_name
 * @property {string} domain
 * @property {boolean} is_owner
 */

/**
 * @typeof Template
 * @property {number} id
 * @property {array} emails
 * @property {array} layouts
 * @property {string} thumbnail
 * @property {string} title
 * @property {array} users
 * @property {number} version
 */

/**
 * @typedef Email
 * @property {number} id
 * @property {number} tid
 * @property {string} title
 */

/**
 * @typedef Emoji
 * @property {string} id
 * @property {string} code
 * @property {number} tone
 * @property {number} user
 * @property {number} timeAdded
 */

/**
 * @typedef Comment
 * @property {number} id
 * @property {Email} email
 * @property {string} content
 * @property {User} user
 * @property {Comment|null} parent
 * @property {string} status
 * @property {Emoji[]} emojis
 * @property {number} blockId
 * @property {number} dateCreated
 */

/**
 * @typedef Mention
 * @property {number} id
 * @property {string} uuid
 * @property {User} user
 * @property {Comment} comment
 * @property {number} dateCreated
 */

/**
 * @typedef Notification
 * @property {number} id
 * @property {User} to
 * @property {User|null} from
 * @property {Mention|null} mention
 * @property {Comment|null} comment
 * @property {string} message
 * @property {string} status
 * @property {string} action
 * @property {number} dateCreated
 */

/**
 * @typedef User
 * @property {number} id
 * @property {string} name
 * @property {string} email
 * @property {string} avatar
 * @property {string} avatar60
 * @property {string} avatar120
 * @property {string} avatar240
 * @property {string} job
 * @property {string} organization
 * @property {string} timezone
 * @property {boolean} isSiteAdmin
 * @property {boolean} hasPass
 * @property {string} dashboardUrl
 * @property {Organization[]} organizations
 * @property {boolean} isOwner
 * @property {boolean} isAdmin
 * @property {boolean|null} isDarkMode
 * @property {number} parentID
 * @property {number} skinTone
 * @property {Notification[]} notifications
 * @property {boolean} isNotificationsEnabled
 * @property {boolean} isShowingCount
 */
