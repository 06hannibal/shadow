<?php

namespace Drupal\registration_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * @property  userAuth
 */
class FormRegistration extends FormBase {

  /**
   * @return string
   */
  public function getFormId() {
    return 'form registration';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|void
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['login'] = [
      '#type' => 'textfield',
      '#required' => true,
      '#title' => t('Login'),
    ];

    $form['firstname'] = [
      '#type' => 'textfield',
      '#required' => true,
      '#title' => t('First-name'),
    ];

    $form['lastname'] = [
      '#type' => 'textfield',
      '#required' => true,
      '#title' => t('Last-name'),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#title' => t('e-mail'),
    ];

    $form['pass'] = [
      '#type' => 'password',
      '#required' => TRUE,
      '#title' => t('password'),
      '#size' => 25,
    ];

    $form['pass2'] = [
      '#type' => 'password',
      '#required' => TRUE,
      '#title' => t('confirm password'),
      '#size' => 25,
    ];

    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadByProperties();
    foreach ($roles as $role) {
      $role_id = $role->id();

      if($role_id == "manufacturer" || $role_id == "seller" || $role_id == "distributor") {
        $options[$role->id()] = $role->label();
      }
    }

    $form['role'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('Select Roles'),
      '#empty_option' => 'Roles',
      '#required' => true,
    ];

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $pass = $form_state->getValue('pass');
    $pass2 = $form_state->getValue('pass2');
    $login = $form_state->getValue('login');
    $email = $form_state->getValue('email');

    $query_name = \Drupal::database()->select('users_field_data', 'ufd');
    $is_login = (bool)$query_name
      ->condition('ufd.name', $login)
      ->countQuery()
      ->execute()
      ->fetchField();

    if($is_login) {
      $form_state->setErrorByName('login', $this->t('This Login already exists.'));
    }

    $query_mail = \Drupal::database()->select('users_field_data', 'ufd');
    $is_mail = (bool)$query_mail
      ->condition('ufd.mail', $email)
      ->countQuery()
      ->execute()
      ->fetchField();

    if($is_mail) {
      $form_state->setErrorByName('email', $this->t('This e-mail already exists.'));
    }

    if ($pass!=$pass2) {
      $form_state->setErrorByName('pass', $this->t('Your password does not match.'));
    }

    if(strlen($pass) < 6 ) {
      $form_state->setErrorByName('pass', $this->t('Password length must be at least 6 characters.'));
    }

    if(!preg_match('@[A-Z]@', $pass)) {
      $form_state->setErrorByName('pass', $this->t('The password must have a capital letter.'));
    }
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $login = $form_state->getValue('login');
    $firstname = $form_state->getValue('firstname');
    $lastname = $form_state->getValue('lastname');
    $email = $form_state->getValue('email');
    $pass = $form_state->getValue('pass');
    $role = $form_state->getValue('role');

    $user = User::create([
      'type' => 'user',
      'name' => $login,
      'field_first_name' => $firstname,
      'field_last_name' => $lastname,
      'mail' => $email,
      'init' => $email,
      'pass' => $pass,
      'roles' => $role,
    ]);
    $user->activate();
    $user->save();

    drupal_set_message(t("user saved."), 'status');
    $form_state->setRedirect('<front>');

  }
}