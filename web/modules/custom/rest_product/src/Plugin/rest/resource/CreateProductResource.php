<?php

namespace Drupal\rest_product\Plugin\rest\resource;

/**
 * @file
 * Contains Drupal\rest_product\Plugin\rest\resource\rest_product.
 */
namespace Drupal\rest_product\Plugin\rest\resource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to create new variation.
 *
 * @RestResource(
 *   id = "create_product_resource",
 *   authenticationTypes = TRUE,

 *   label = @Translation("Create product resource"),
 *   uri_paths = {
 *     "create" = "api/entity/product",
 *     "canonical" = "api/entity_update/product/{product_id}",
 *   }
 * )
 */
class CreateProductResource extends ResourceBase {

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   * @param $entity_data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function post($entity_data) {

    try {
//      Creating Price
      $price_number = $entity_data['price'][0]['number'];
      $price_currency = $entity_data['price'][0]['currency_code'] ;
      $price = new \Drupal\commerce_price\Price( $price_number, $price_currency);

//      Creating product variation
      $variation = \Drupal\commerce_product\Entity\ProductVariation::create ([
        'type' => 'default', // The default variation type is 'default'.
        'sku' => $entity_data['variations'][0]['sku'], // The variation sku.
        'status' => 1, // The product status. 0 for disabled, 1 for enabled.
        'price' =>  $price,
      ]);
      $variation->save();

//      Load store
      $store_id = $entity_data['store'];
      $store = \Drupal\commerce_store\Entity\Store::load($store_id);

//      Create the product
      $product = \Drupal\commerce_product\Entity\Product::create ([
        'uid' => $entity_data['uid'],
        'type' => 'default',
        'title' => $entity_data['title'],
        'stores' => $store,
        'variations' => $variation,
      ]);
      $product->save();

      return new ResourceResponse($product);
    } catch (\Exception $e) {
      return new ResourceResponse('Something went wrong during entity creation. Check your data.', 400);
    }
  }

  /**
   * Responds to PATCH requests.
   *
   * Returns a list of bundles for specified entity.
   * @param $product_id
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function patch($product_id, $data) {

    try {
//      Load the chosen product and its first variation
      $product = \Drupal\commerce_product\Entity\Product::load($product_id);
      $variations = $product->getVariations();
      $variation = $variations[0];

//      Getting the data from body of PATCH request
      $title = $data['title'];
      $type = $data['type'];
      $status = $data['status'];
      $sku = $data['sku'];
      $price_number = $data['price'][0]['number'];
      $price_currency = $data['price'][0]['currency_code'] ;
      $price = new \Drupal\commerce_price\Price( $price_number, $price_currency);

//      Setting the new data in product and its first variation
      $variation->setSku($sku);
      $variation->setPrice($price);
      $product->set('title', $title);
      $product->set('type', $type);
      $product->set('status', $status);

//      Saving the product
      $variation->save();
      $product->save();

      return new ResourceResponse($product);
    } catch (\Exception $e) {
      return new ResourceResponse('Something went wrong during entity creation. Check your data.', 400);
    }
  }
}


