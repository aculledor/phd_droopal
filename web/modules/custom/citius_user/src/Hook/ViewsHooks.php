<?php

namespace Drupal\citius_user\Hook;

use Drupal\citius_user\UserRoles;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxy;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Views hooks related to users.
 */
class ViewsHooks {

  public function __construct(
    #[Autowire(service: 'current_user')]
    protected AccountProxy $currentUser,
  ) {}

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_views_exposed_form_alter')]
  public function viewsExposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $view = $form_state->get('view');
    if ($view instanceof ViewExecutable
      && $view->current_display === 'patients'
      && $view->id() === 'users_list'
      && $this->currentUser->hasRole(UserRoles::PHYSIOTHERAPIST)) {
      $form['center']['#access'] = FALSE;
    }
  }

}
