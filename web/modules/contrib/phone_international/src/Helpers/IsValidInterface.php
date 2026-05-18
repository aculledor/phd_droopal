<?php

declare(strict_types = 1);

namespace Drupal\phone_international\Helpers;

/**
 * Interface to validate phone number.
 */
interface IsValidInterface {

  /**
   * Full validation of a phone number.
   *
   * @param string $number
   *   Validate whether the number is valid.
   */
  public function isValidNumber(string $number);

  /**
   * Format phone number.
   *
   * @param string $number
   *   Format number.
   */
  public function formatNumber(string $number);

}
