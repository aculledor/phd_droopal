<?php

declare(strict_types = 1);

namespace Drupal\phone_international\Commands;

use Drush\Commands\DrushCommands;
use Drush\Drush;
use Psr\Log\LogLevel;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The Phone International plugin URI.
 */
define('PI_DOWNLOAD_URI', 'https://github.com/jackocnr/intl-tel-input/archive/v17.0.19.zip');

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class PhoneInternationalCommands extends DrushCommands {

  /**
   * Download and install the Phone International plugin.
   *
   * @param string $path
   *   Optional. A path where to install the Phone International plugin.
   *   If omitted Drush will use the default location.
   *
   * @command phone_international:plugin
   * @aliases piplugin,pi-plugin
   *
   * @throws \Exception
   */
  public function plugin($path = '') {
    if (empty($path)) {
      $path = 'libraries';
    }

    // Create the path if it does not exist.
    if (!is_dir($path)) {
      drush_op('mkdir', $path);
      $this->drushLog(dt('Directory @path was created', ['@path' => $path]), 'notice');
    }

    // Set the directory to the download location.
    $olddir = getcwd();
    chdir($path);

    // Download the zip archive.
    if ($filepath = $this->drushDownloadFile(PI_DOWNLOAD_URI)) {
      $filename = basename($filepath);
      $dirname = basename($filepath, '.zip');

      // Remove any existing Phone International(intl-tel-input)
      // plugin directory.
      if (is_dir($dirname) || is_dir('chosen')) {
        Filesystem::remove($dirname, TRUE);
        Filesystem::remove('intl-tel-input', TRUE);
        $this->drushLog(dt('A existing Phone International(intl-tel-input) plugin was deleted from @path', ['@path' => $path]), 'notice');
      }

      // Decompress the zip archive.
      $this->drushTarballExtract($filename, $dirname);

      // Change the directory name to "intl-tel-input" if needed.
      if ('intl-tel-input' !== $dirname) {
        $this->drushMoveDir($dirname, 'intl-tel-input');
        $dirname = 'intl-tel-input';
      }

      unlink($filename);
    }

    if (is_dir($dirname)) {
      $this->drushLog(dt('Phone International(intl-tel-input) plugin has been installed in @path', ['@path' => $path]), 'success');
    }
    else {
      $this->drushLog(dt('Drush was unable to install the Phone International(intl-tel-input) plugin to @path', ['@path' => $path]), 'error');
    }

    // Set working directory back to the previous working directory.
    chdir($olddir);
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param string $message
   *   The log message.
   * @param mixed $type
   *   The log type.
   */
  public function drushLog($message, $type = LogLevel::INFO) {
    $this->logger()->log($type, $message);
  }

  /**
   * Download file with Drush.
   *
   * @param string $url
   *   The download url.
   * @param mixed $destination
   *   The destination path.
   *
   * @return bool|string
   *   The destination file.
   *
   * @throws \Exception
   */
  public function drushDownloadFile($url, $destination = FALSE) {
    // Generate destination if omitted.
    if (!$destination) {
      $file = basename(current(explode('?', $url, 2)));
      $destination = getcwd() . '/' . basename($file);
    }

    // Copied from: \Drush\Commands\SyncViaHttpCommands::downloadFile.
    static $use_wget;
    if ($use_wget === NULL) {
      $process = Drush::process(['which', 'wget']);
      $process->run();
      $use_wget = $process->isSuccessful();
    }

    $destination_tmp = drush_tempnam('download_file');
    if ($use_wget) {
      $args = ['wget', '-q', '--timeout=30', '-O', $destination_tmp, $url];
    }
    else {
      $args = [
        'curl', '-s', '-L', '--connect-timeout', '30', '-o', $destination_tmp, $url,
      ];
    }
    $process = Drush::process($args);
    $process->mustRun();

    if (!drush_file_not_empty($destination_tmp) && $file = @file_get_contents($url)) {
      @file_put_contents($destination_tmp, $file);
    }
    if (!drush_file_not_empty($destination_tmp)) {
      // Download failed.
      throw new \Exception(dt("The URL !url could not be downloaded.", ['!url' => $url]));
    }
    if ($destination) {
      $fs = new Filesystem();
      $fs->rename($destination_tmp, $destination, TRUE);
      return $destination;
    }
    return $destination_tmp;
  }

  /**
   * Change directory name.
   *
   * @param string $src
   *   The origin filename or directory.
   * @param string $dest
   *   The new filename or directory.
   */
  public function drushMoveDir($src, $dest) {
    $fs = new Filesystem();
    $fs->rename($src, $dest, TRUE);
  }

  /**
   * Create directory.
   *
   * @param string $path
   *   The make directory path.
   */
  public function drushMkdir($path) {
    $fs = new Filesystem();
    $fs->mkdir($path);
  }

  /**
   * Drush Tarball Extract.
   *
   * @param string $path
   *   The filename or directory.
   * @param bool $destination
   *   The destination path.
   *
   * @throws \Exception
   */
  public function drushTarballExtract($path, $destination = FALSE) {
    $this->drushMkdir($destination);
    $cwd = getcwd();
    if (preg_match('/\.tgz$/', $path)) {
      drush_op('chdir', dirname($path));
      $process = Drush::process(['tar', '-xvzf', $path, '-C', $destination]);
      $process->run();
      drush_op('chdir', $cwd);

      if (!$process->isSuccessful()) {
        throw new \Exception(dt('Unable to extract !filename.' . PHP_EOL . implode(PHP_EOL, $process->getOutput()), ['!filename' => $path]));
      }
    }
    else {
      drush_op('chdir', dirname($path));
      $process = Drush::process(['unzip', $path, '-d', $destination]);
      $process->run();
      drush_op('chdir', $cwd);

      if (!$process->isSuccessful()) {
        throw new \Exception(dt('Unable to extract !filename.' . PHP_EOL . implode(PHP_EOL, $process->getOutput()), ['!filename' => $path]));
      }
    }
  }

}
