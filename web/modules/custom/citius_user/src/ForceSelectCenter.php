<?php

declare(strict_types=1);

namespace Drupal\citius_user;

use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Force user to select center.
 */
final class ForceSelectCenter implements HttpKernelInterface {

  public function __construct(
    private readonly HttpKernelInterface $httpKernel,
    private readonly RouteMatchInterface $routeMatch,
    #[Autowire(service: 'current_user')]
    private readonly AccountProxy $currentUser,
    private readonly CurrentCenterResolver $currentCenterResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    $response = $this->httpKernel->handle($request, $type, $catch);
    if ($this->shouldProcess($request, $response, $type)) {
      return new RedirectResponse(Url::fromRoute('citius_user.select_center')->toString());
    }
    return $response;
  }

  /**
   * Checks if the response should be processed.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   Response object.
   * @param int $type
   *   Request type.
   *
   * @return bool
   *   TRUE if the response should be processed.
   */
  private function shouldProcess(Request $request, Response $response, int $type): bool {
    return !($type !== self::MAIN_REQUEST
      || !$response instanceof HtmlResponse
      || !$request->isMethod('GET')
      || !$response->isSuccessful()
      || !$this->currentUser->hasRole(UserRoles::PHYSIOTHERAPIST)
      || $this->currentCenterResolver->get() !== NULL
      || in_array($this->routeMatch->getRouteName(), $this->getExcludedRoutes(), TRUE));
  }

  /**
   * Get list of excluded routes.
   *
   * @return string[]
   *   Array of excluded route names.
   */
  private function getExcludedRoutes(): array {
    return [
      'user.logout',
      'citius_user.select_center',
    ];
  }

}
