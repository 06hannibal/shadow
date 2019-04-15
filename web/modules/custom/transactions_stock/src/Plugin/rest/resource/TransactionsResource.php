<?php

namespace Drupal\transactions_stock\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\commerce_product\ProductVariationStorage;
use Drupal\commerce_stock\StockServiceManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a Transactions Resource
 *
 * @RestResource(
 *   id = "transactions_resource",
 *   label = @Translation("Transactions Resource"),
 *   uri_paths = {
 *     "create" = "/transactions_stock"
 *   }
 * )
 */
class TransactionsResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorage
   */
  protected $productVariationStorage;

  /**
   * The stock service manager.
   *
   * @var \Drupal\commerce_stock\StockServiceManager
   */
  protected $stockServiceManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new CreateArticleResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user, ProductVariationStorage $productVariationStorage, StockServiceManager $stockServiceManager, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->productVariationStorage = $productVariationStorage;
    $this->stockServiceManager = $stockServiceManager;
    $this->request = $request;
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
      $container->get('logger.factory')->get('transactions_stock'),
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('commerce_product_variation'),
      $container->get('commerce_stock.service_manager'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Responds to POST requests.
   */
  public function post($data) {

    if (!$this->currentUser->hasPermission('add commerce stock location entities')) {
      throw new AccessDeniedHttpException();
    }
    $transaction_type = $data['transaction_type'];
    $product_variation_id = $data['product_variation_id'];
    $source_location = $data['source_location'];
    $source_zone = $data['source_zone'];
    $qty = $data['transaction_qty'];
    $transaction_note = $data['transaction_note'];

    $product_variation = $this->productVariationStorage->load($product_variation_id);

    if ($this->currentUser->id() != $product_variation->getOwnerId() || empty($product_variation)) {
      throw new AccessDeniedHttpException();
    }

    if ($transaction_type == 'receiveStock') {
      $this->stockServiceManager->receiveStock($product_variation, $source_location, $source_zone, $qty, NULL, $currency_code = NULL, $transaction_note);
    } elseif ($transaction_type == 'sellStock') {
      $order_id = $data['order'];
      $user_id = $data['user'];
      $this->stockServiceManager->sellStock($product_variation, $source_location, $source_zone, $qty, NULL, $currency_code = NULL, $order_id, $user_id, $transaction_note);
    } elseif ($transaction_type == 'returnStock') {
      $order_id = $data['order'];
      $user_id = $data['user'];
      $this->stockServiceManager->returnStock($product_variation, $source_location, $source_zone, $qty, NULL, $currency_code = NULL, $order_id, $user_id, $transaction_note);
    } elseif ($transaction_type == 'moveStock') {
      $target_location = $data['target_location'];
      $target_zone = $data['target_zone'];
      $this->stockServiceManager->moveStock($product_variation, $source_location, $target_location, $source_zone, $target_zone, $qty, NULL, $currency_code = NULL, $transaction_note);
    }
      return new ResourceResponse($product_variation->getSku());
  }
}