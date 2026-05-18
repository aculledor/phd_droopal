<?php

declare(strict_types = 1);

namespace Drupal\phone_international\Helpers;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Class Validating Service.
 */
class ValidatingService implements IsValidInterface {

  /**
   * Utility for international phone numbers.
   *
   * @param string $number
   *   Phone number verification and validation.
   *
   * @return bool
   *   Return number valid (TRUE) or invalid (FALSE).
   */
  public function isValidNumber(string $number): bool {
    $phoneUtil = PhoneNumberUtil::getInstance();

    try {
      $parseNumber = $phoneUtil->parse($number);
      return $phoneUtil->isValidNumber($parseNumber);
    }
    catch (NumberParseException $e) {
      \Drupal::logger('phone_international')->debug($e->getMessage());

      return FALSE;
    }
  }

  /**
   * Utility for international phone numbers.
   *
   * @param string $number
   *   Phone number format.
   *
   * @return mixed
   *   Return number.
   */
  public function formatNumber(string $number) {
    $phoneUtil = PhoneNumberUtil::getInstance();

    try {
      $numberProto = $phoneUtil->parse($number);
      return $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
    }
    catch (NumberParseException $e) {
      \Drupal::logger('phone_international')->error('Problem formatting number: @number. The error given was @error', [
        '@number' => $number,
        '@error'  => $e->getMessage(),
      ]);

      return $number;
    }
  }

}
