<?php

namespace Drupal\citius_content;

/**
 * Session state available values.
 */
enum SessionState: string {

  case Scheduled = 'scheduled';
  case Execution = 'execution';
  case Pause = 'pause';
  case Finished = 'finished';

}
