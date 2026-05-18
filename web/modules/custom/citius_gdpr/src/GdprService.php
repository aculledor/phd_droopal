<?php

declare(strict_types=1);

namespace Drupal\citius_gdpr;

use Drupal\citius_content\TaxonomyFields;
use Drupal\citius_user\UserFields;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\UserInterface;

/**
 * Service that performs GDPR actions.
 */
class GdprService {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected TimeInterface $time,
  ) {}

  /**
   * Move user data to temporary fields.
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   */
  public function scheduleAnonymization(UserInterface $user): void {
    $config = $this->configFactory->get('citius_gdpr.settings');
    $user->set(GdprUserFields::NAME, $user->get(UserFields::NAME)->getValue());
    $user->set(GdprUserFields::SURNAME, $user->get(UserFields::SURNAME)->getValue());
    $user->set(GdprUserFields::DATE, $this->time->getRequestTime());
    $user->set(GdprUserFields::IMAGE, $user->get(UserFields::PHOTO)->getValue());
    /** @var \Drupal\taxonomy\TermInterface|null $gender_term */
    $gender_term = $user->get(UserFields::SEX)->entity;
    $gender_code = $gender_term?->get(TaxonomyFields::CODE)->value;
    $name_key = $gender_code === 'female' ? 'female_name' : 'male_name';
    $user->set(UserFields::NAME, $config->get($name_key));
    $user->set(UserFields::SURNAME, $config->get('surname'));
    $user->set(UserFields::PHOTO, NULL);
    $user->save();
  }

  /**
   * Delete user data from temporary fields.
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   */
  public function finalizeAnonymization(UserInterface $user): void {
    $user->set(GdprUserFields::NAME, NULL);
    $user->set(GdprUserFields::SURNAME, NULL);
    $user->set(GdprUserFields::DATE, $this->time->getRequestTime());
    $user->set(GdprUserFields::IMAGE, NULL);
    $user->save();
  }

  /**
   * Deanonymizes user data.
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   */
  public function deanonymize(UserInterface $user): void {
    $gdpr_name = $user->get(GdprUserFields::NAME);
    $gdpr_surname = $user->get(GdprUserFields::SURNAME);
    $gdpr_image = $user->get(GdprUserFields::IMAGE);
    if (!$gdpr_name->isEmpty()) {
      $user->set(UserFields::NAME, $gdpr_name->getValue());
      $user->set(UserFields::SURNAME, $gdpr_surname->getValue());
      $user->set(UserFields::PHOTO, $gdpr_image->getValue());
      $user->set(GdprUserFields::NAME, NULL);
      $user->set(GdprUserFields::SURNAME, NULL);
      $user->set(GdprUserFields::DATE, NULL);
      $user->set(GdprUserFields::IMAGE, NULL);
      $user->save();
    }
  }

}
