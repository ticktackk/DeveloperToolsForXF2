Developer Tools for XenForo 2.1.7+
==================================

Description
-----------

This add-on provides enhancements for add-on developers.

Features
--------

- Show display order... 
  - Option group
  - Options
  - Permission groups
  - Permission
- Test template modification against specific style
- Show breadcrumb for style property group
- Show template modification type in breadcrumb
- Show option group when adding option
- Show execution order for template modifications, class extensions and code event listeners (Since 1.2)
- Show warnings when attempted to check for permissions or permission groups that do not exist (Since 1.2)
- Allow creating permission via permission interface even if permissions already exist (Since 1.2)
- Automatically fill out code event listener callback class and method as well creating listener file or adding just the function
- Exclude files or directories via `exclude\_files` and `exclude\_directories` respectively via `build.json` from add-on archive
- Create multiple phrases at once
- Build add-on archive from ACP
- View template modifications applied on a specific template
- Minify JavaScript files locally
- Lookup what email HTML and plain text was sent (Since 1.3)
- Automatically generate `README.md`... 
  - The following information will be available in README with description whenever possible: 
      - Add-on title
      - Add-on description
      - Add-on requirements
      - Options
      - Permissions
      - Admin permissions
      - BB codes
      - BB code media sites
      - Style properties
      - Advertising positions
      - Widget positions
      - Widget definitions
      - Cron entries
      - REST API scopes
      - CLI Commands
  - Further more, you can add your own blocks by creating HTML files named after the hook positions: 
      - `BEFORE_TITLE`
      - `AFTER_TITLE`
      - `BEFORE_DESCRIPTION`
      - `AFTER_DESCRIPTION`
      - `BEFORE_REQUIREMENTS`
      - `AFTER_REQUIREMENTS`
      - `BEFORE_OPTIONS`
      - `AFTER_OPTIONS`
      - `BEFORE_PERMISSIONS`
      - `AFTER_PERMISSIONS`
      - `BEFORE_ADMIN_PERMISSIONS`
      - `AFTER_ADMIN_PERMISSIONS`
      - `BEFORE_BB_CODES`
      - `AFTER_BB_CODES`
      - `BEFORE_BB_CODE_MEDIA_SITES`
      - `AFTER_BB_CODE_MEDIA_SITES`
      - `BEFORE_STYLE_PROPERTIES`
      - `AFTER_STYLE_PROPERTIES`
      - `BEFORE_ADVERTISING_POSITIONS`
      - `AFTER_ADVERTISING_POSITIONS`
      - `BEFORE_WIDGET_POSITIONS`
      - `AFTER_WIDGET_POSITIONS`
      - `BEFORE_WIDGET_DEFINITIONS`
      - `AFTER_WIDGET_DEFINITIONS`
      - `BEFORE_CRON_ENTRIES`
      - `AFTER_CRON_ENTRIES`
      - `BEFORE_REST_API_SCOPES`
      - `AFTER_REST_API_SCOPES`
      - `BEFORE_CLI_COMMANDS`
      - `AFTER_CLI_COMMANDS`
  - When an add-on is built, following `README` variants will be created: 
      - BB code version at `_dev/resource_description.txt` for resource descriptions
      - Markdown version at `README.md` for any VCS repository

Requirements
------------

- PHP 7.3.0+

Options
-------

| Group                        | Name                         | Description                                                                                                                                                               |
| ---------------------------- | ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Developer Tools (Debug only) | Disable hash checking        | For development purposes disabling XF's hash check is required to hotpatch code                                                                                           |
| Developer Tools (Debug only) | Disable XF Template watching | XF's template watching causes a large amount of IO per page, and doesn't touch phrases to template modification. Disable for a boost in performance if it isn't required. |

CLI Commands
------------

| Command                                 | Description                                                                                                  |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `tck-devtools:add-phrase`               | Creates a phrase for an add-on.                                                                              |
| `tck-devtools:create-class-extension`   | Creates an XF class-extension for an add-on and writes out a basic template file.                            |
| `tck-devtools:better-export`            | Exports the XML files for an add-on and applies class properties to type hint columns, getters and relations |
| `tck-devtools:create-entity-from-table` | Creates an XF entity for an add-on from a table.                                                             |
| `tck-devtools:generate-schema-entity`   | Generates schema code from an entity                                                                         |
| `tck-devtools:build-readme`             | Builds README files for provided add-on.                                                                     |
| `tck-devtools:clamp-versions`           | Ensures an add-on does not have phrases or templates with version id's above the addon.json file.            |

Scripts
-------

There are some wrapper scripts under `scripts` directory provided by [Xon](https://xenforo.com/community/members/xon.71874/) which can be helpful.

License
-------

This project is licensed under the MIT License - see the [LICENSE.md](https://github.com/ticktackk/DeveloperToolsForXF2/blob/master/LICENSE.md) file for details.