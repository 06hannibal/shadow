<?php

namespace Drupal\stores_tax;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Applies taxes to orders during the order refresh process.
 */
class StoreOrderProcessor implements OrderProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Constructs a new TaxOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    $tax_type_storage = $this->entityTypeManager->getStorage('commerce_tax_type');
    $tax_types = $tax_type_storage->loadMultiple();
    $store = $order->getStore();
    $fields_tax = $store->get('field_tax')->getValue();
    foreach ($fields_tax as $field_tax) {
      foreach ($tax_types as $tax_type) {
        if($field_tax['target_id']==$tax_type->id() && $tax_type->getPlugin()->applies($order)) {
          $tax_type->getPlugin()->apply($order);
        }
      }
    }
    if ($order->getStore()->get('prices_include_tax')->value) {
      foreach ($order->getItems() as $order_item) {
        $adjustments = $order_item->getAdjustments();
        $tax_adjustments = array_filter($adjustments, function ($adjustment) {
          /** @var \Drupal\commerce_order\Adjustment $adjustment */
          return $adjustment->getType() == 'tax' && $adjustment->isNegative();
        });
        $adjustments = array_diff_key($adjustments, $tax_adjustments);
        $unit_price = $order_item->getUnitPrice();
        $order_item->setUnitPrice($unit_price);
        $order_item->setAdjustments($adjustments);
      }
    }
  }
}