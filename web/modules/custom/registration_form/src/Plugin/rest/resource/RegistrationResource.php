<?php

namespace Drupal\registration_form\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;

/**
 * Provides a Transactions Resource
 *
 * @RestResource(
 *   id = "registration_resource",
 *   label = @Translation("Registration Resource"),
 *   uri_paths = {
 *     "create" = "/registration_form"
 *   }
 * )
 */
class RegistrationResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('registration_form'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   */
  public function post($data) {
    $login = $data['login'];
    $firstname = $data['firstname'];
    $lastname = $data['lastname'];
    $email = $data['email'];
    $pass = $data['pass'];
    $pass2 = $data['pass2'];
    $role = $data['roles'];
    $login_auth = $data['login_auth'];
    $pass_auth = $data['pass_auth'];

    if(!empty($login_auth) || !empty($pass_auth)) {
      $uid = \Drupal::service('user.auth')->authenticate($login_auth, $pass_auth);
      $user_uid = User::load($uid);
      $user_role = $user_uid->getRoles();
    }


    $query_name = \Drupal::database()->select('users_field_data', 'ufd');
    $is_login = (bool)$query_name
      ->condition('ufd.name', $login)
      ->countQuery()
      ->execute()
      ->fetchField();

    if($is_login) {
      return new ResourceResponse('This Login already exists.', 400);
    }

    $query_mail = \Drupal::database()->select('users_field_data', 'ufd');
    $is_mail = (bool)$query_mail
      ->condition('ufd.mail', $email)
      ->countQuery()
      ->execute()
      ->fetchField();

    if($is_mail) {
      return new ResourceResponse('This e-mail already exists.', 400);
    }

    if ($pass!=$pass2) {
      return new ResourceResponse('Your password does not match.', 400);
    }

    if(strlen($pass) < 6 ) {
      return new ResourceResponse('Password length must be at least 6 characters.', 400);
    }

    if(!preg_match('@[A-Z]@', $pass)) {
      return new ResourceResponse('The password must have a capital letter.', 400);
    }

    if (in_array("administrator",$user_role)) {
      $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadByProperties();
      if (array_key_exists($role, $roles)) {
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
        return new ResourceResponse($user);
      } else {
        return new ResourceResponse('There is no such role.', 400);
      }
    } else {
      if($role == "manufacturer" || $role == "seller" || $role == "distributor") {
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
        return new ResourceResponse($user);
      } else {
        return new ResourceResponse('You do not have enough rights or do not have such a role.', 400);
      }
    }
  }
}

