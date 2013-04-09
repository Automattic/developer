=== Developer ===
Contributors: automattic, batmoo, Viper007Bond, nbachiyski, tott, danielbachhuber, betzster, nprasath002, nickdaugherty
Tags: developer, development, local
Requires at least: 3.4
Tested up to: 3.6
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin, which helps WordPress developers develop.

== Description ==

A plugin, which helps WordPress developers develop.

This plugin will help you optimize your development environment by making sure that you have all the essential tools and plugins installed.

If you'd like to check out the code and contribute, [join us on GitHub](https://github.com/Automattic/developer). Pull requests, issues, and plugin recommendations are more than welcome!

We would like to thank Ejner Galaz for letting us use the `developer` slug in the WordPress.org plugin repository.

== Installation ==

1. Upload the `developer` folder to your plugins directory (e.g. `/wp-content/plugins/`)
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Follow the instructions

== Frequently Asked Questions ==

= Why are there no FAQs besides this one? =

Because you haven't asked one yet.

== Screenshots ==

1. On activation, the plugin prompts you to specify what type of developer you are. This is used to configure the plugins checks.
2. On activation, the plugin does a quick check to see if you have essential developer plugins installed.
3. With one click you can install and activate the plugin.
4. The plugin's settings page (Tools > Developer) will check to make sure your environment is correctly configured, including plugins, constants, and other settings.

== Changelog ==

= 1.1.6 (2013-04-08) =
* Made purpose of activate/install links on Settings page more obvious
* Added link to full plugin details on Settings page (opens in Thickbox)

= 1.1.5 (2013-04-05) =
* Added ability to define multiple project types for plugins
* Added ability to define project types for constants
* Added Jetpack to recommended plugins and constants

= 1.1.4 (2013-04-03) =
* Added plugin descriptions to installation and settings pages

= 1.1.3 (2013-04-02) =
* Added improved error reporting
* Added [Log Viewer](http://wordpress.org/extend/plugins/log-viewer/) to recommended plugins. Props to @rockaut for the suggestion

= 1.1.2 (2013-01-29) =
* French localization. Props [fxbenard](https://github.com/fxbenard)
* Replaced Reveal IDs with Simply Show IDs. The former never installed.
* Bug fix: Show a few plugins as active when they're actually active.

= 1.1.1 (2012-08-30) =

* Fix piglatin slug, props bobbingwide

= 1.1 (2012-08-30) =
* New "WP.org Theme" project type for developers building themes for self-hosted installs with a number of sweet plugins recommended by the WordPress.com Theme Team.
* Simplify some of the wording across the plugin.
* Added John Blackbourn's [User Switching](http://wordpress.org/extend/plugins/user-switching/) is now a recommended plugin for all projects.
* Added [Pig Latin](http://wordpress.org/extend/plugins/pig-latin) plugin to help developer i18n their code.
* Added resources for all projects.
* Bug fix: don't show installation prompt in network admin.

= 1.0 =
* Initial Release
