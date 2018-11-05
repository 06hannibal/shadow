<?php

namespace Drupal\transactions_stock\EventSubscriber;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_stock_local\Entity\StockLocation;
use Drupal\commerce_store\Entity\Store;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OrderWorkflowSubscriber event subscriber.
 */
class OrderFulfillHandler implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['orderFulfillHandler'];

    return $events;
  }

  /**
   * Creates a log when an order is placed.
   */
  public function OrderFulfillHandler(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    foreach ($order->getItems() as $order_item) {
      $product_variation = $order_item->getPurchasedEntity();
      $quantity = $order_item->getQuantity();
      $title = $product_variation->getTitle();
      $variation_type = $product_variation->bundle();
      $prise = $product_variation->getPrice();
      $prise_number = $prise->getNumber();
      $prise_currencyCode = $prise->getCurrencyCode();
      $product = $product_variation->getProduct();
      $product_type = $product->bundle();
      $stores = $product->getStoreIds();
      $sku = $product_variation->getSku() . " " . \Drupal::currentUser()->getAccountName();
      $sku_author = $product_variation->getSku();
      $uid = $product_variation->get('uid')->getString();
      $order_id = $order_item->getOrderId();
    }
    //commerce store new product
    $currentuserid = \Drupal::currentUser()->id();
    $mail = \Drupal::currentUser()->getEmail();
    $commerce_store = \Drupal::entityTypeManager()
      ->getStorage('commerce_store')
      ->loadByProperties(['uid'=>$currentuserid]);
    $commerce_store_currentuserid = array_values($commerce_store);

    if(empty($commerce_store)) {
      $store = Store::create([
      'type' => 'online',
      'uid' => $currentuserid,
      'name' => $sku,
      'mail' => $mail,
      'label' => 'My store',
      'address' => [
        'country_code' => 'CA',
      ],
      'prices_include_tax' => FALSE,
    ]);
    $store->save();
    } else {
      $store = $commerce_store_currentuserid[0];
    }

    $commerce_stock_location = \Drupal::entityTypeManager()
      ->getStorage('commerce_stock_location')
      ->loadByProperties(['field_author'=>$currentuserid]);
    $commerce_location = array_values($commerce_stock_location);

    if(empty($commerce_stock_location)) {
      $location = StockLocation::create([
        'name' => 'Location'.$sku,
        'status' => TRUE,
        'type' => "default",
        'field_author' => $currentuserid,
        ]);
      $location->save();
    } else {
      $location = $commerce_location[0];
    }

    $commerce_product_variation = \Drupal::entityTypeManager()
      ->getStorage('commerce_product_variation')
      ->loadByProperties(['sku'=> $sku]);
    $commerce_variation = array_values($commerce_product_variation);

    if(empty($commerce_product_variation)) {
      $variation = ProductVariation::create([
      'type' => $variation_type,
      'sku' => $sku,
      'title' => $title,
      'status' => 1,
      'price' => new Price($prise_number, $prise_currencyCode),
    ]);
    $variation->save();
    } else {
      $variation = $commerce_variation[0];
    }
    $commerce_product = \Drupal::entityTypeManager()
      ->getStorage('commerce_product')
      ->loadByProperties(['uid'=>$currentuserid,'title'=>$title]);
    $existing_product = array_values($commerce_product);

    if(empty($commerce_product)) {
    $product = Product::create([
      'uid' => $currentuserid,
      'type' => $product_type,
      'title' => $title,
      'stores' => [$store],
      'variations' => [$variation],
    ]);
    $product->save();
    } else {
      $product = $existing_product;
    }
    $qty = floatval($quantity);
    $stockService = \Drupal::service('commerce_stock.service_manager');
    $stockService->receiveStock(
      $variation,
      $location->id(),
      t('default zone'),
      $qty,
      NULL,
      $currency_code = NULL);
  }
}
