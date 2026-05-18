<?php

namespace Drupal\citius_user\Hook;

use Drupal\citius_user\UserFields;
use Drupal\citius_user\UserRoles;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Hooks related to user forms.
 */
class UserFormAlter {

  use StringTranslationTrait;
  /**
   * Form weights.
   */
  protected const array WEIGHTS = [
    UserFields::NAME => 0,
    UserFields::SURNAME => 1,
    UserFields::MAIL => 2,
    UserFields::PASSWORD => 2.1,
    UserFields::PHONE => 4,
    UserFields::CENTER => 5,
    UserFields::LOCATION => 6,
    UserFields::ROLES => 7,
    'notify' => 50,
  ];

  use DependencySerializationTrait;

  /**
   * Constructs class instance.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user service.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'user_form' => [
        'render element' => 'form',
      ],
    ];
  }

  /**
   * Implements hook_form_alter().
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $form_id
   *   Form ID.
   */
  #[Hook('form_alter')]
  public function userRegisterFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $form_ids = ['user_register_form', 'user_form'];
    if (!in_array($form_id, $form_ids, TRUE)) {
      return;
    }
    $form[UserFields::NAME]['#weight'] = -100;
    $form[UserFields::SURNAME]['#weight'] = -99;
    $form['account']['name']['#access'] = FALSE;
    $form['account'][UserFields::MAIL]['#required'] = TRUE;
    $form['account'][UserFields::ROLES]['#ajax'] = [
      'callback' => [$this, 'rolesCallback'],
    ];
    unset(
      $form[UserFields::PATHOLOGY_SECONDARY]['widget']['#description'],
      $form[UserFields::CENTER]['widget']['#description'],
    );
    /** @var \Drupal\user\ProfileForm  $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\user\UserInterface $account */
    $account = $form_object->getEntity();
    $is_patient = $this->isPatient($account);
    if ($is_patient) {
      $form[UserFields::CENTER]['widget']['#multiple'] = FALSE;
      $form[UserFields::CENTER]['widget']['#cardinality'] = 1;
      $form[UserFields::CENTER]['widget']['#empty_option'] = '';
      $form[UserFields::CENTER]['widget']['#mode'] = 'select';
      $form[UserFields::CENTER]['widget']['#required'] = TRUE;
    }
    if (!$this->isFormFull($form_id)) {
      $form['account'][UserFields::ROLES]['#default_value'] = $account->getRoles(TRUE);
      $form['account'][UserFields::ROLES]['#access'] = FALSE;
      $form['account']['status']['#access'] = FALSE;
      $form['account']['status']['#default_value'] = TRUE;
      $this->adjustRolesField($form);
      if ($is_patient) {
        $form['account'][UserFields::MAIL]['#access'] = FALSE;
        $form['account'][UserFields::MAIL]['#required'] = FALSE;
        $form['account'][UserFields::PASSWORD]['#access'] = FALSE;
        $form['account'][UserFields::PASSWORD]['#required'] = FALSE;
        $form['account']['notify']['#access'] = FALSE;
        $form_state->set('patient', TRUE);
        $form[UserFields::PATHOLOGY_PRIMARY]['widget']['#required'] = TRUE;
        $this->adjustSecondaryPathologyOptions($form, $form_state);
      }
    }
    foreach (Element::children($form['account']) as $field) {
      $form[$field] = $form['account'][$field];
      unset($form['account'][$field]);
    }
    $form['account']['#access'] = FALSE;
    foreach (Element::children($form) as $field) {
      if (isset(self::WEIGHTS[$field])) {
        $form[$field]['#weight'] = self::WEIGHTS[$field];
      }
    }
    $user_input = $form_state->getUserInput();
    $roles = $user_input[UserFields::ROLES] ?? $form[UserFields::ROLES]['#default_value'] ?? [];
    $form[UserFields::CENTER]['#attributes']['id'] = 'center-field';
    $show_center = !empty(array_intersect([UserRoles::PHYSIOTHERAPIST, UserRoles::PATIENT], $roles));
    $form[UserFields::CENTER]['widget']['#access'] = $show_center;
    $form[UserFields::CENTER]['widget']['#required'] = $show_center;
    $is_user_real = !empty(array_intersect([UserRoles::PHYSIOTHERAPIST, UserRoles::MANAGER, UserRoles::ADMINISTRATOR], $roles));
    $real_user_fields = [
      UserFields::LOCATION,
      UserFields::PHONE,
    ];
    foreach ($real_user_fields as $field) {
      $form[$field]['#attributes']['id'] = Html::getId('user_form_field' . $field);
      $form[$field]['widget']['#access'] = $is_user_real;
      $form[$field]['widget']['#required'] = $is_user_real;
      if (isset($form[$field]['widget'][0]['value'])) {
        $form[$field]['widget'][0]['value']['#required'] = $is_user_real;
      }
    }
    $patient_fields = [
      UserFields::GENDER,
      UserFields::SEX,
      UserFields::BIRTHDATE,
      UserFields::CIVIL_STATUS,
      UserFields::PATHOLOGY_PRIMARY,
      UserFields::PATHOLOGY_SECONDARY,
    ];
    foreach ($patient_fields as $field) {
      $form[$field]['#access'] = $is_patient;
    }
    array_unshift($form['#validate'], [$this, 'validateForm']);
    $form['#theme'] = 'user_form';
  }

  /**
   * Adjust secondary pathology options.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  protected function adjustSecondaryPathologyOptions(array &$form, FormStateInterface $form_state): void {
    $form[UserFields::PATHOLOGY_SECONDARY]['#attributes']['id'] = 'secondary-pathology-field';
    $form[UserFields::PATHOLOGY_PRIMARY]['widget']['#ajax'] = [
      'callback' => [$this, 'pathologyCallback'],
      'event' => 'change',
      'wrapper' => 'secondary-pathology-field',
    ];
    $selected_pathology = $form_state->getValue([UserFields::PATHOLOGY_PRIMARY, 0, 'target_id']) ?? $form[UserFields::PATHOLOGY_PRIMARY]['widget']['#default_value'][0] ?? [];
    if (empty($selected_pathology)) {
      return;
    }
    unset($form[UserFields::PATHOLOGY_SECONDARY]['widget']['#options'][$selected_pathology]);
  }

  /**
   * Check if we need to show the full form.
   *
   * @param string $form_id
   *   Form ID.
   *
   * @return bool
   *   TRUE if we need to show the full form.
   */
  protected function isFormFull(string $form_id): bool {
    if ($this->routeMatch->getRouteName() === 'citius_user.add_user') {
      return FALSE;
    }
    if ($form_id === 'user_form') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Check if it is a patient form.
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   *
   * @return bool
   *   TRUE if it's a patient form.
   */
  protected function isPatient(UserInterface $user): bool {
    if ($this->routeMatch->getParameter('role') === UserRoles::PATIENT) {
      return TRUE;
    }
    if ($user->hasRole(UserRoles::PATIENT)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Limit roles options and show element if needed.
   *
   * @param array $form
   *   Form array.
   */
  protected function adjustRolesField(array &$form): void {
    if ($this->routeMatch->getParameter('role') === 'user') {
      $form['account'][UserFields::ROLES]['#access'] = TRUE;
      $form['account'][UserFields::ROLES]['#title'] = $this->t('Type');
      $allowed_roles = [
        UserRoles::PHYSIOTHERAPIST,
        UserRoles::MANAGER,
      ];
      $form['account'][UserFields::ROLES]['#options'] = array_intersect_key($form['account'][UserFields::ROLES]['#options'], array_flip($allowed_roles));
      $form['account'][UserFields::ROLES]['#default_value'] = array_intersect($form['account'][UserFields::ROLES]['#default_value'], $allowed_roles);
      $form['account'][UserFields::ROLES]['#required'] = TRUE;
    }
  }

  /**
   * AJAX callback for user roles element.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function rolesCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $fields = [
      UserFields::CENTER,
      UserFields::LOCATION,
      UserFields::PHONE,
    ];
    foreach ($fields as $field) {
      $response->addCommand(new ReplaceCommand('#' . $form[$field]['#attributes']['id'], $form[$field]));
    }
    return $response;
  }

  /**
   * AJAX callback for a secondary pathology element.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Updated element.
   */
  public function pathologyCallback(array &$form, FormStateInterface $form_state): array {
    return $form[UserFields::PATHOLOGY_SECONDARY] ?? [];
  }

  /**
   * Add custom validation to the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateForm(array $form, FormStateInterface $form_state): void {
    $email = $form_state->getValue('mail');
    $position = strpos($email, '@');
    $username = $position === FALSE ? $email : substr($email, 0, $position);
    $username .= '_' . random_int(1, 99999);
    $form_state->setValue('name', $username);
    $roles = $form_state->getValue('roles');
    $is_user_real = !empty(array_intersect([UserRoles::PHYSIOTHERAPIST, UserRoles::MANAGER, UserRoles::ADMINISTRATOR], $roles));
    $is_patient = in_array(UserRoles::PATIENT, $roles, TRUE);
    $real_user_fields = [
      UserFields::LOCATION,
      UserFields::PHONE,
    ];
    if (in_array(UserRoles::PHYSIOTHERAPIST, $roles, TRUE)) {
      $real_user_fields[] = UserFields::CENTER;
    }
    if ($is_patient) {
      $this->validateField($form, $form_state, UserFields::CENTER);
    }
    foreach ($real_user_fields as $field) {
      if ($is_user_real) {
        $this->validateField($form, $form_state, $field);
      }
      else {
        $form_state->setValue($field, []);
      }
    }
    if ($form_state->get('patient')) {
      $first_name = $form_state->getValue([UserFields::NAME, 0, 'value']);
      $surname = $form_state->getValue([UserFields::SURNAME, 0, 'value']);

      $timestamp = date('Ymd_His');
      // Use name+surname to deterministically derive a 4-digit code.
      $seed = $first_name . $surname . microtime(TRUE);
      $code = str_pad((string) (crc32((string) $seed) % 10000), 4, '0', STR_PAD_LEFT);
      $local_part = "dummy-{$timestamp}_{$code}";
      $dummy_email = $local_part . '@citius.dummy';
      $form_state->setValue('name', $local_part);
      $form_state->setValue('mail', $dummy_email);
    }
  }

  /**
   * Validate single field.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param string $field
   *   Field machine name.
   */
  protected function validateField(array $form, FormStateInterface $form_state, string $field): void {
    $value = $form_state->getValue($field);
    $filtered_value = NestedArray::filter(NestedArray::filter($value));
    if (empty($filtered_value)) {
      $title = $form[$field]['widget']['#title'] ?? $field;
      $form_state->setErrorByName($field, $this->t('@name field is required.', ['@name' => $title]));
    }
  }

}
