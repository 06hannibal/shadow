<?php
/**
 * @file
 * Contains \Drupal\manufacture_marketplace\Controller\ManufactureMarketplaceController.
 */

namespace Drupal\manufacture_marketplace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class ManufactureMarketplaceController extends ControllerBase {

  public function content() {
    $user_role = \Drupal::currentUser()->getRoles();
    if (in_array("administrator", $user_role) || in_array('distributor', $user_role)) {
      $products = \Drupal\commerce_product\Entity\Product::loadMultiple();//loadByProperties(['uid' => $user_id]);
      $count = 0;
      $product_list = [];
      foreach ($products as $product) {
        $owner_role = $product->getOwner()->getRoles();
        if (in_array("manufacturer", $owner_role)) {
          $count++;
          $product_list[$count]['id'] = $product->id();
          $product_list[$count]['title'] = $product->getTitle();
          $product_list[$count]['link'] = Url::fromRoute(
            'manufacture_marketplace.form',
            ['product_id' => $product->id()]
          )->toString();
        }
      }
      return ([
        '#theme' => 'manufacture_marketplace_list_template',
        '#product_list' => $product_list,
      ]);
    }
  }
}




