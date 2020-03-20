# DeveloperToolsForXF2

This add-on provides enhancements for add-on developers.
 
## Features

- Display order for
  - Option group and Option
  - Permission group and Permission
- Test template modification against specific style
- Show breadcrumb for style property group
- Show template modification type in breadcrumb
- Show option group when adding option
- Show warnings when attempted to check for permissions or permission groups that do not exist (Since 1.2)
- Allow creating permission via permission interface even if permissions already exist (Since 1.2)
- Automatically fill out code event listener callback class and method as well creating listener file or adding just the function
- Exclude files or directories via `exclude_files` and `exclude_directories` respectively via `build.json` from add-on archive
- Create multiple phrases at once
- Build add-on archive from ACP
- View template modifications applied on a specific template
- Minify JavaScript files locally

## CLI Commands

| Option | Description |
| ------ | ----------- |
| `tck-devtools:better-export` | Exports the XML files for an add-on and applies class properties to type hint columns, getters and relations |
| `tck-devtools:create-class-extension` | Creates an class-extension for an add-on and writes out a basic template file. |
| `tck-devtools:add-phrase` | Allows creating phrase via CLI. |
| `tck-devtools:create-entity-from-table` | Creates an XF entity for an add-on from a table. |
| `tck-devtools:generate-schema-entity` | Identifier for the Entity (Prefix:Type format) |

## Scripts

There are some wrapper scripts under `scripts` directory provided by @Xon which can be helpful.
 
## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details