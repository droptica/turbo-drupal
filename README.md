# Before everything


> **1. Clone/fork this repository**
>
> This is a project template. Fork/clone it to start your new project.

> **2. DDEV is required**
>
> This build requires ddev to be installed on your local machine. You can
> install it by following ddev installation guide located here
> https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/#linux.

# Build project for development
    $ mkdir [your project name/domain] && cd [your project name/domain]
    $ git clone git@github.com:droptica/CLONED-REPOSITORY-NAME.git .

# Project Config
In repository directory run command:
```shell
ddev config
```

1. Project name: [your project name]
2. Docroot: [enter] (default: docroot **web**)
3. Create docroot directory: [y]
4. Project type: select from list (default: **drupal10**)

```shell
ddev start
```

# Composer install
After project configuration run command to install composer dependencies:

```shell
ddev composer install
```

If you have problems with the authorization, add ssh key authentication to the
ddev-ssh-auth container:
```shell
ddev auth ssh
```

# DDEV install the clean Drupal website

```shell
ddev drush site:install --config-dir=../config/sync --account-name=admin --account-pass=123 -y
```

Set the GIN as a default theme:
```shell
ddev drush theme:install gin -y
ddev drush config-set system.theme admin gin -y
```

Also, you can enable the common modules:
```shell
ddev drush en admin_toolbar admin_toolbar_tools config_split security_review site_audit -y
```

Update the `config_sync_directory` in the `settings.php` file:
```php
$settings['config_sync_directory'] = '../config/sync';
```

# DDEV project build from the existing data

Define the `DATA_DEV_SERVER` variable in the `.ddev/commands/host/download-data`
 file to be able to download latest data from the server.

```shell
ddev download-data
ddev build
```

# Additional commands

### DDEV default commands:

```bash
ddev drush <params> - Normal drush command, what did you expected here? :D
ddev composer <params> - Same as above but with composer :D
```

### DDEV build commands:

```bash
ddev download-data - Download latest database and files from defined server
ddev build - Run normal default procedure
ddev platform - Run pull platform command to get fresh database and files from PLATFORM_ENVIRONMENT in the config
ddev db-restore - Restore db from local dump located at '.ddev/db-dumps'
ddev files-import - Import all files from local dump located at 'ddev/file-dumps'
```

### DDEV theme commands:

Define the `THEME_PATH` variable in the `.ddev/commands/web/theme` file to be
able to build the assets.

```bash
ddev theme - Compile all styles for defined theme
```

### DDEV tools commands:

```bash
ddev get ddev/ddev-phpmyadmin - Get PhpMyAdmin container
```

### Other DDEV commands
Any other useful command from ddev you can find by simply typing `ddev` into
your terminal.

# Quality tools

## Code Sniffer, Code Beautifier, Stan:
```bash
ddev phpcs
ddev phpcbf
ddev phpstan
```

## Compatibility check

```bash
ddev compatibility
ddev compatibility 8.1 web/modules/custom
ddev compatibility 8.2 web/modules/custom web/themes/custom
ddev compatibility 8.2 web/modules/custom web/themes/custom > report.txt
```

## Auto update

```shell
ddev auto-update
```

## Tests

Documentation: [README](./tests/README.md).

## Github Actions and Bitbucket Pipelines

This template contains the base configuration of the github actions and
bitbucket pipelines to check quality of your code with the PHP CodeSniffer and
PHP Stan tools.

Base usage doesn't need any configuration, just enable actions/pipelines in your
repository settings.

# Project documentation

The project documentation is located in the `docs` directory:
[Documentation](./docs/documentation/000-readme.md)

# Using DDEV connected to a database instance on an active Platform.sh environment
This is instructions for running the template locally, connected to a live
database instance on an active Platform.sh environment.

In all cases for developing with Platform.sh, it's important to develop on an
isolated environment - do not connect to data on your production environment
when developing locally.
Each of the options below assume that you have already deployed this template
to Platform.sh, as well as the following starting commands:

```bash
platform get PROJECT_ID
cd project-name
platform environment:branch updates
```

DDEV provides an integration with Platform.sh that makes it simple to develop
Drupal locally. Check the [providers documentation](https://ddev.readthedocs.io/en/latest/users/providers/platform/)
for the most up-to-date information.

In general, the steps are as follows:

1. [Install ddev](https://ddev.readthedocs.io/en/stable/#installation).
2. A configuration file has already been provided at
`.ddev/providers/platform.yaml`, so you should not need to run `ddev config`.
3. [Retrieve an API token](https://docs.platform.sh/development/cli/api-tokens.html#get-a-token)
for your organization via the management console.
4. Add the API token to the `web_environment` section in your global DDEV
configuration at `~/.ddev/global_config.yaml`:
    ```yaml
    web_environment:
    - PLATFORMSH_CLI_TOKEN=abcdeyourtoken
    ```
5. Run `ddev restart`.
6. Get your project ID with `platform project:info`. If you have not already
connected your local repo with the project (as is the case with a source
integration, by default), you can run `platform project:list` to locate the
project ID, and `platform project:set-remote PROJECT_ID` to configure
Platform.sh locally.
7. Update the `.ddev/providers/platform.yaml` file for your current setup:
    ```yaml
    environment_variables:
    project_id: PROJECT_ID
    environment: CURRENT_ENVIRONMENT
    application: drupal
    ```
8. Get the current environment's data with `ddev pull platform`.
9. When you have finished with your work, run `ddev stop` and `ddev poweroff`.

> **Note:**
>
> For many of the steps above, you may need to include the CLI flags
> `-p PROJECT_ID` and `-e ENVIRONMENT_ID` if you are not in the project
> directory or if the environment is associated with an existing pull request.
