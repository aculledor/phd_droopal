<?php

declare(strict_types=1);

namespace Drupal\citius_analytics\PathProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor for /user/{uid}/results routes.
 */
class PathProcessorAnalytics implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * Constructs a PathProcessorAnalytics object.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AliasManagerInterface $aliasManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request): string {
    if (preg_match('#^/resultados/([^/]+)(/.*)?$#', $path, $matches)) {
      $slug = $matches[1];
      $prefix = is_numeric($slug) ? '/user/' : '/usuario/';
      $candidate_alias = $prefix . $slug;
      $internal_path = $this->aliasManager->getPathByAlias($candidate_alias);
      if (preg_match('#^/user/(\d+)$#', $internal_path, $uid_matches)) {
        $uid = $uid_matches[1];
        return '/user/' . $uid . '/results';
      }
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL): string {
    if (preg_match('#^/user/(\d+)/results(/.*)?$#', $path, $matches)) {
      $uid = (int) $matches[1];
      /** @var \Drupal\user\UserInterface|null $user */
      $user = $this->entityTypeManager->getStorage('user')->load($uid);

      if ($user instanceof UserInterface && !$user->isAnonymous()) {
        $user_alias = $this->aliasManager->getAliasByPath('/user/' . $uid);
        $slug = basename($user_alias);

        return '/resultados/' . $slug;
      }
    }
    return $path;
  }

}
