CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Maintainers
 * Attention

INTRODUCTION
------------

International Phone for Drupal.

 * For a full description of the module visit:
   https://www.drupal.org/project/phone_international

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/phone_international

INSTALLATION
------------
* Install the International Phone module as you would normally
  install a contributed Drupal module. Visit
  https://www.drupal.org/node/1897420 for further information.

### Version <= 8.x-1.3
  This module requires no modules outside of Drupal core.

### Version >= 8.x-1.4
   Since the module requires an external library, Composer must be used.
  ```
   composer require "drupal/phone_international"
  ```

### Version >= 3.x
  #### Manual Version
  Download it from the [release page](https://github.com/jackocnr/intl-tel-input/releases) and place it in Drupal's library folder.
  #### Composer Version
  It's recommended to use asset-packagist to install JavaScript libraries. Check next steps:
  1. Add the Composer Installers Extender PHP package by oomphinc to your project's root composer.json file, by running the following command:
  ```
  composer require oomphinc/composer-installers-extender
  ```
  2. Add Asset Packagist to the "repositories" section of your project's root composer.json.
  ```
  {
        "type": "composer",
        "url": "https://asset-packagist.org"
    }
  ```
  3. Ensure that NPM and Bower assets are registered as new "installer-types" and, in addition to type:drupal-library, they are registered in "installer-paths" to be installed into Drupal's /libraries folder, within the "extra" section of your project's root composer.json file.
  ```
  "extra": {
      "installer-types": [
          "npm-asset",
          "bower-asset"
      ],
      "installer-paths": {
          "web/libraries/{$name}": [
              "type:drupal-library",
              "type:npm-asset",
              "type:bower-asset"
          ]
      }
  }
  ```
  5. You may now require libraries from NPM or Bower via Composer on the command line:
  ```
   composer require "npm-asset/intl-tel-input:^17.0"
  ```

CONFIGURATION
-------------

  -   To use it you simply add the "International phone" to the entity on which
  you wish to use it

MAINTAINERS
-----------

Current maintainers:
 * Alexandre Dias (Saidatom) - https://www.drupal.org/u/saidatom
 * Miguel Leal (miguel.leal) - https://www.drupal.org/u/miguelleal

ATTENTION
---------

Version based on:

 * [https://github.com/jackocnr/intl-tel-input](https://github.com/jackocnr/intl-tel-input)
 * [https://github.com/jackocnr/intl-tel-input/blob/master/README.md](https://github.com/jackocnr/intl-tel-input/blob/master/README.md)

Most bugs have been ironed out, holes covered, features added. This module
is a work in progress. Please report bugs and suggestions, ok?
