<?php

namespace Drupal\citius_gdpr\Hook;

use Drupal\citius_gdpr\GdprService;
use Drupal\citius_gdpr\GdprUserFields;
use Drupal\citius_user\UserRoles;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Cron jobs to anonymize users.
 */
class Cron {

  public function __construct(
    protected Connection $connection,
    protected TimeInterface $time,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GdprService $gdprService,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $this->scheduleAnonymization();
    $this->finalizeAnonymization();
  }

  /**
   * Schedule anonymization of outdated users.
   */
  protected function scheduleAnonymization(): void {
    $now = $this->time->getRequestTime();
    $config = $this->configFactory->get('citius_gdpr.settings');
    $apply = $config->get('apply_gdpr') ?? 5;
    $start_date = $now - ($apply * 365 * 24 * 60 * 60);
    $query = $this->connection->select('users_field_data', 'u')
      ->condition('u.status', 1)
      ->condition('u.created', $start_date, '<=')
      ->fields('u', ['uid']);
    $query->innerJoin('user__roles', 'r', 'u.uid = r.entity_id');
    $query->condition('r.roles_target_id', UserRoles::PATIENT);
    $query->leftJoin('user__field_gdpr_date', 'gdpr', 'u.uid = gdpr.entity_id');
    $query->isNull('gdpr.entity_id');
    $query->leftJoin('node__field_patient', 'p', 'u.uid = p.field_patient_target_id');
    $query->leftJoin('node_field_data', 'n', 'p.entity_id = n.nid');
    $query->addExpression('MAX(n.created)', 'newest_date');
    $query->groupBy('u.uid');
    $query->having('newest_date <= :date OR newest_date IS NULL', [':date' => $start_date]);
    $query->orderBy('u.uid');
    $query->range(0, 50);
    $ids = $query->execute()?->fetchCol();
    if (empty($ids)) {
      return;
    }
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($ids);
    foreach ($users as $user) {
      $this->gdprService->scheduleAnonymization($user);
      $this->log()->notice('Scheduled anonymization of user with ID %id.', ['%id' => $user->id()]);
    }
  }

  /**
   * Finalize anonymization of outdated users.
   */
  protected function finalizeAnonymization(): void {
    $now = $this->time->getRequestTime();
    $config = $this->configFactory->get('citius_gdpr.settings');
    $cancellation_period = $config->get('cancellation_period') ?? 30;
    $user_storage = $this->entityTypeManager->getStorage('user');
    $query = $user_storage->getQuery();
    $ids = $query->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', UserRoles::PATIENT)
      ->exists(GdprUserFields::NAME)
      ->condition(GdprUserFields::DATE, $now - ($cancellation_period * 24 * 60 * 60), '<=')
      ->execute();
    if (empty($ids)) {
      return;
    }
    $users = $user_storage->loadMultiple($ids);
    foreach ($users as $user) {
      $this->gdprService->finalizeAnonymization($user);
      $this->log()->notice('Finalized anonymization of user with ID %id.', ['%id' => $user->id()]);
    }
  }

  /**
   * Get logger channel.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   Logger channel.
   */
  protected function log(): LoggerChannelInterface {
    return $this->loggerFactory->get('citius_gdpr');
  }

}
