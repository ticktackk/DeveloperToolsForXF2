CHANGELOG
==========================

## 1.1.0 (`1010070`)

* **New:** Show style property group in breadcrumb
* **New:** Show option group when adding option
* **New:** Show template modification type in breadcrumb
* **New:** Ability to exclude files and directories using `exclude_files` and `exclude_directories` respectively via `build.json` (#25)
* **New:** Add `CHANGELOG.md` to release archive
* **New:** Class extensions will now have common classes already imported
* **Changed:** Entity class extensions created via CLI command will now have `XF\Mvc\Entity\Structure` class aliased to `EntityStructure`
* **Changed:** Provided scripts now have `.sh` extension
* **Changed:** Developer options group will now only be shown in debug mode
* **Fixed:** Template modification test failing
* **Fixed:** "View modifications" failing for templates
* **Fixed:** Path for `addon.json` is not shown when invalid add-on id is provided for class extension CLI command (#26)
* **Removed:** Dead class extension
* **Removed:** Removed PHPUnit integration which was borderline useless
* General code changes and improvements

## 1.0.0 (`1000070`)

* **New:** Enable hidden file-based email transport option
* **New:** Option to disable template watching (performance improvement)
* **New:** Option to disable file hash checking
* **New:** Add link to build add-on archive from add-on control menu
* **New:** Added CLI command to add phrase
* **New:** Added `_tests` to excluded directories
* **Changed:** Allow per-style analysis of how template modifications apply

**Contributions:** Some of the changes and bug-fixes were made by @Xon

## 1.0.0 Beta 5 (`1000035`)

* **New:** Added CLI commands `ticktackk-devtools:git-init`, `ticktackk-devtools:git-move`, `tdt:create-entity` and `tdt:schema-entity` (modified to round-trip better)
* **New:** Added method to get random entity based on an identifier for seeds
* **New:** Added post seed
* **New:** Option named "Custom Git repository location"
* **New:** Show template modifications which are applying (or failing) for a template
* **Changed:** Clear cache before adding files to the repository
* **Changed:** Update default `.gitignore` file contents to include `git.json`, `_metadata.json` and `.phpstorm.meta.php`
* **Changed:** More robust git-init
* **Changed:** Release build no longer removes `_data` after successfully creating a repository
* **Changed:** The CLI command `git-commit` will now make use of the new `ticktackk-devtools:git-move` command
* **Changed:** Move seeding process to Job to avoid timeouts
* **Changed:** Default branch is now `master` for `ticktackk-devtools:better-export` command
* **Changed:** Every seed will be now run as a random user
* **Fixed:** Git username and email not showing correctly
* **Fixed:** File not found error when FakeComposer attempts to load files
* **Fixed:** FakeComposer would fail on non-Windows OS
* **Fixed:** Setup not porting old settings correctly
* **Fixed:** `additional_files` directive saving incorrectly
* **Fixed:** When attempting to seed specific file, it would fail
* General bug fixes and improvements

**Contributions:** Some of the contributions were made by @Xon and @filliph 

## 1.0.0 Beta 4 (`1000034`)

* **New:** Faker integration
    * Check `_seeds` directory for sample
* **New:** Added CLI commands `ticktackk-devtools:create-class-extension` and `ticktackk-devtools:seed`
* **New:** Allows hosting the google minification closure compiler locally to avoid rate-limiting
* **New:** Some bash wrappers inside `scripts` directory

**Contributions:** Some of the contributions were made by @Xon

## 1.0.0 Beta 3 (`1000033`)

* **New:**: PHPUnit framework integration allows you to test add-on before releasing or pushing the new changes to VCS
* **New:**: Add-on specific git configuration (currently only name and email supported)
* **New:**: Ability to use packages made using composer without composer itself for your add-ons
* **New:**: Added new CLI commands `ticktackk-devtools:phpunit` and `ticktackk-devtools:rebuild-fake-composer`
* **Changed:**: Minimum PHP requirement has been bumped to `7.2`
* **Changed:**: Removed useless template from public side
* **Changed:**: The `_repo` directory now will be initialized if it hasn't already
* **Changed:**: Store developer options of add-on in `dev.json` instead of database
* **Changed:**: Store git configuration of add-on in `git.json` instead of database
* **Fixed:** Stop spamming `name` and `email` in `CONFIG` file for git

## 1.0.0 Beta 2 (`1000032`)

*Unreleased*

## 1.0.0 Beta 1 (PL 1) (`1000031`)

* **Changed:** Move `xf-addon:build-release` to the end in `ticktackk-devtools:better-export`
* **Fixed:** `[E_USER_WARNING] Accessed unknown getter 'gitignore'`

## 1.0.0 Beta 1 (`1000031`)

* **New:**: Added new CLI command `ticktackk-devtools:git-push <add-on id> <repo> <branch>` (thanks to @belazor)
* **New:**: New options for `ticktackk-devtools:better-export`
   - `--skip-export` Allows skipping exporting data before building release or moving files to `_repo` directory (thanks to @belazor)
   - `--commit` Allows committing changes (if any) to the local repository
   - `--push` Allows local repository to a branch (thanks to @belazor)
* **New:**: Added option to exclude directories when moving working files to `_repo` directory (thanks to @belazor)
* **New:**: Added option to copy additional files to `_repo` directory
   - Can be enabled/disabled per add-on (Default: disabled)
* **Changed:**: Developer options won't be shown in overlay
* General bug-fixes and code improvements

## 1.0.0 Alpha 6 (`1000016`)

* **New:** Copy additional files to repository directory
* **New:** Add "Save and exit" button for template modification editing process
* **New:** Both LICENSE.md and README.md are now copied to root directory
* **New:** Both LICENSE.md and README.md is now added to add-on archive as well
* **New:** New CLI commands `ticktackk-devtools:git-init` and `ticktackk-devtools:git-commit`
* **Changed:** LICENSE is now LICENSE.md
* **Changed:** Removed commit upon any developer output
* **Changed:** Moved all git related commands to separate CLI command

## 1.0.0 Alpha 5 (`1000015`)

* **Fixed:** Check if _repo directory exists before committing

## 1.0.0 Alpha 4 (`1000014`)

* **New**: Upon developer output for supported output types git commit command is called with supported commit type
    * Supported developer output types
        * Admin Navigation
        * Admin Permission
        * Advertising Position
        * BB Code
        * BB Code Media Site
        * Class Extension
        * Content type filed
        * Cron entry
        * Help page
        * Navigation
        * Option
        * Option Group
        * Permission
        * Permission Interface Group
        * Phrase
        * Route
        * Style Property
        * Style Property Group
        * Template
        * Template Modification
        * Widget Definition
        * Widget Position
    * Supported commit types
        * Export
        * Change
        * Delete

## 1.0.0 Alpha 3 (`1000013`)

* **New**: Added option --release for ticktackk-devtools:better-export which would release the add-on
* **Changed**: README.me location is now under add-on directory to avoid getting overwritten by other releases made from developer tools
* **Changed**: Removed template modification which wasn't required for anything
* **Changed**: xf-dev:export command will be called after xf-dev:entity-class-properties
* **Fixed**: Added a workaround for xf-dev:entity-class-properties command bug if no Entity directory was present

## 1.0.0 Alpha 2 (`1000012`)

* **New**: Added new command `ticktackk-devtools:better-export [addon_id]` which would run both `xf-addon:export` and `xf-dev:entity-class-properties` for the add-on id provided 
* **New**: Added support for testing template modifications against different style
* **Changed**: Updated description under `addon.json`
* **Fixed**: Fixed Setup file

## 1.0.0 Alpha 1 (`1000011`)

First alpha release.