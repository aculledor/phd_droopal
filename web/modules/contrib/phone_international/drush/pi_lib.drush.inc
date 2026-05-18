<?php

/**
 * @file
 * Drush integration for phone_international.
 */

declare(strict_types = 1);

use Drush\Boot\DrupalBootLevels;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The Phone International(intl-tel-input) plugin URI.
 */
define('PI_DOWNLOAD_URI', 'https://github.com/jackocnr/intl-tel-input/archive/v17.0.19.zip');

/**
 * Implements hook_drush_command().
 */
function pi_lib_drush_command() {
  $items = [];

  // The key in the $items array is the name of the command.
  // No bootstrap.
  $items['pi-plugin'] = [
    'callback' => 'drush_pi_lib_plugin',
    'description' => dt('Download and install the Phone International(intl-tel-input) plugin.'),
    'bootstrap' => DrupalBootLevels::NONE,
    'arguments' => [
      'path' => dt('Optional. A path where to install the Phone International(intl-tel-input) plugin. If omitted Drush will use the default location.'),
    ],
    'aliases' => ['piplugin'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function pi_lib_drush_help($section) {
  switch ($section) {
    case 'drush:pi-plugin':
      return dt('Download and install the Phone International(intl-tel-input) plugin from https://github.com/jackocnr/intl-tel-input, default location is /libraries.');
  }
}

/**
 * Command to download the Phone International(intl-tel-input) plugin.
 */
function drush_pi_lib_plugin() {
  $args = func_get_args();
  if (!empty($args[0])) {
    $path = $args[0];
  }
  else {
    $path = 'libraries';
  }

  // Create the path if it does not exist.
  if (!is_dir($path)) {
    drush_op('mkdir', $path);
    \Drupal::logger(dt('Directory @path was created', ['@path' => $path]))->notice('notice');

  }

  // Set the directory to the download location.
  $olddir = getcwd();
  chdir($path);

  // Download the zip archive.
  if ($filepath = drush_download_file(PI_DOWNLOAD_URI)) {
    $filename = basename($filepath);
    $dirname = basename($filepath, '.zip');

    // Remove any existing Phone International(intl-tel-input) plugin directory.
    if (is_dir($dirname) || is_dir('intl-tel-input')) {
      Filesystem::remove($dirname, TRUE);
      Filesystem::remove('intl-tel-input', TRUE);
      \Drupal::logger(dt('A existing Phone International(intl-tel-input) plugin was deleted from @path', ['@path' => $path]))->notice('notice');
    }

    // Decompress the zip archive.
    drush_tarball_extract($filename, $dirname);

    // Change the directory name to "intl-tel-input" if needed.
    if ($dirname != 'intl-tel-input') {
      drush_move_dir($dirname, 'intl-tel-input', TRUE);
      $dirname = 'intl-tel-input';
    }
  }

  if (is_dir($dirname)) {
    \Drupal::logger(dt('Phone International(intl-tel-input) plugin has been installed in @path', ['@path' => $path]))->info('success');
  }
  else {
    \Drupal::logger(dt('Drush was unable to install the Phone International(intl-tel-input) plugin to @path', ['@path' => $path]))->error('error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
