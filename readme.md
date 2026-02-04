This contains the source files for the "*Omnipedia - Block*" Drupal module,
which contains various block plug-ins and related code for
[Omnipedia](https://omnipedia.app/)

⚠️ ***[Why open source? / Spoiler warning](https://omnipedia.app/open-source)***

*Please note that [all development and issue tracking is done on <img src="https://gitlab.com/neurocracy/omnipedia/omnipedia/-/raw/main/docs/assets/gitlab/gitlab-logo.svg" alt="The GitLab logo" width="16" height="16"> GitLab](https://gitlab.com/neurocracy/omnipedia/modules/omnipedia-block).*

----

# Requirements

* [11.2](https://www.drupal.org/download)

* PHP 8.2

* [Composer](https://getcomposer.org/)

## Drupal dependencies

Before attempting to install this, you must add the Composer repositories as
described in the installation instructions for these dependencies:

* The [`ambientimpact_core` module](https://github.com/Ambient-Impact/drupal-ambientimpact-core).

* The following Omnipedia modules:

  * [`omnipedia_changes`](https://gitlab.com/neurocracy/omnipedia/modules/omnipedia-changes)

  * [`omnipedia_content`](https://gitlab.com/neurocracy/omnipedia/modules/omnipedia-content)

  * [`omnipedia_core`](https://gitlab.com/neurocracy/omnipedia/modules/omnipedia-core)

  * [`omnipedia_date`](https://gitlab.com/neurocracy/omnipedia/modules/omnipedia-date)

  * [`omnipedia_search`](https://gitlab.com/neurocracy/omnipedia/modules/omnipedia-search)

## Front-end dependencies

To build front-end assets for this project, [Node.js](https://nodejs.org/) and
[Yarn](https://yarnpkg.com/) are required.

----

# Installation

## Composer

### Set up

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the `drupal/recommended-project`
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

### Repository

In your root `composer.json`, add the following to the `"repositories"` section:

```json
{
  "type": "vcs",
  "url": "https://gitlab.com/neurocracy/omnipedia/modules/omnipedia-block.git",
  "only": ["drupal/omnipedia_block"]
}
```

### Installing

Once you've completed all of the above, run `composer require
"drupal/omnipedia_block:^7.0@dev"` in the root of your project to have
Composer install this and its required dependencies for you.

## Front-end assets

To build front-end assets for this project, you'll need to install
[Node.js](https://nodejs.org/) and [Yarn](https://yarnpkg.com/).

This package makes use of [Yarn
Workspaces](https://yarnpkg.com/features/workspaces) and references other local
workspace dependencies. In the `package.json` in the root of your Drupal
project, you'll need to add the following:

```json
"workspaces": [
  "<web directory>/modules/custom/*"
],
```

where `<web directory>` is your public Drupal directory name, `web` by default.
Once those are defined, add the following to the `"dependencies"` section of
your top-level `package.json`:

```json
"drupal-omnipedia-block": "workspace:^7"
```

Then run `yarn install` and let Yarn do the rest.

### Optional: install yarn.BUILD

While not required, [yarn.BUILD](https://yarn.build/) is recommended to make
building all of the front-end assets even easier.

----

# Building front-end assets

This uses [Webpack](https://webpack.js.org/) and [Symfony Webpack
Encore](https://symfony.com/doc/current/frontend.html) to automate most of the
build process. These will have been installed for you if you followed the Yarn
installation instructions above.

If you have [yarn.BUILD](https://yarn.build/) installed, you can run:

```
yarn build
```

from the root of your Drupal site. If you want to build just this package, run:

```
yarn workspace drupal-omnipedia-block run build
```

----

# Major breaking changes

The following major version bumps indicate breaking changes:

* 4.x - Front-end package manager is now [Yarn](https://yarnpkg.com/); front-end build process ported to [Webpack](https://webpack.js.org/).

* 5.x:

  * Requires Drupal 9.5 or [Drupal 10](https://www.drupal.org/project/drupal/releases/10.0.0).

  * Increases minimum version of [Hook Event Dispatcher](https://www.drupal.org/project/hook_event_dispatcher) to 3.1, removes deprecated code, and adds support for 4.0 which supports Drupal 10.

* 6.x:

  * Removed all use of the `omnipedia_commerce` module and removed it from dependencies.

  * Removed the [`\Drupal\omnipedia_block\Plugin\Block\Join` block](https://gitlab.com/neurocracy/omnipedia/modules/omnipedia-block/blob/5.x/src/Plugin/Block/Join.php); you can still find it in the 5.x and older branches.

  * Removed all use of the `omnipedia_access` module and removed it from dependencies.

  * Removed all use of the `permissions_by_term:access_result_cache` cache tag, removing reliance on the [Permissions by Term module](https://www.drupal.org/project/permissions_by_term).

* 7.x:

  * Removed Drupal 10 support; minimum Drupal core is now 11.2.

  * Removed [Hook Event Dispatcher](https://www.drupal.org/project/hook_event_dispatcher) as a dependency in favour of Drupal core OOP hooks.

  * Moved all main page code to the [`omnipedia_main_page`](https://gitlab.com/neurocracy/omnipedia/modules/omnipedia-main-page) module and removed it as a dependency.
