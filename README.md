# DeveloperToolsForXF2
Developer tools for XenForo 2
 
## Features
- Display order for
  - Option group and Option
  - Permission group and Permission
- Test template modification against specific style
- PHPUnit framework integration allows you to test add-on before releasing or pushing the new changes to VCS
- Ability to use packages made using composer without composer itself
- Available global configuration
  - Git name
  - Git email
  - .gitignore file contents
  - Directories to exclude for VCS purposes 
- Available configuration per add-on
  - Git name
  - Git email
  - .gitignore file contents (appended after global .gitignore contents)
  - License.md
  - Readme.md

## CLI Commands

| Option | Description |
| ------ | ----------- |
| `ticktackk-devtools:better-export` | Exports the XML files for an add-on and applies class properties to type hint columns, getters and relations |
| `ticktackk-devtools:git-init` | Initialize an add-on for VCS |
| `ticktackk-devtools:git-commit` | Copies changes made to the add-on to repository and then finally commits the changes |
| `ticktackk-devtools:phpunit` | Runs PHPUnit tests for an add-on |
| `ticktackk-devtools:create-class-extension` | Creates an class-extension for an add-on and writes out a basic template file. |
| `ticktackk-devtools:add-phrase` | Allows creating phrase via CLI. |
| `ticktackk-devtools:create-entity-from-table` | Creates an XF entity for an add-on from a table. |
| `ticktackk-devtools:generate-schema-entity` | Identifier for the Entity (Prefix:Type format) |

## Scripts
There are some wrapper scripts under `scripts` directory provided by @Xon which can be helpful.
 
## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details