<?php

namespace Drupal\citius_device_api;

/**
 * Execution result available values.
 */
enum ExecutionResult: string {

  case Success = 'success';
  case Failure = 'failure';
  case Missed = 'missed';

}
