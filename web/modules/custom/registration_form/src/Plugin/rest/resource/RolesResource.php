<?php

namespace Drupal\registration_form\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Transactions Resource
 *
 * @RestResource(
 *   id = "roles_resource",
 *   label = @Translation("Roles Resource"),
 *   uri_paths = {
 *     "canonical" = "/roles_resource"
 *   }
 * )
 */
class RolesResource extends ResourceBase {

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
   * Responds to GET requests.
   */
  public function get() {

    $cache = CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'max-age' => 0,
      ],
    ]);
    $role_user = $this->currentUser->getRoles();

    if (in_array("administrator",$role_user)) {
      $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadByProperties();
      foreach ($roles as $role) {
          $options[$role->id()] = $role->label();
        }
      return (new ResourceResponse($options))->addCacheableDependency($cache);
      } else {
      $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadByProperties();
      foreach ($roles as $role) {
        $role_id = $role->id();

        if($role_id == "manufacturer" || $role_id == "seller" || $role_id == "distributor") {
          $options[$role->id()] = $role->label();
        }
      }
      return (new ResourceResponse($options))->addCacheableDependency($cache);
    }
  }
}

