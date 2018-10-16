<?php

/**
* @file
* Contains \Drupal\manufacture_marketplace\Form\ManufactureMarketplaceForm.
*/

namespace Drupal\manufacture_marketplace\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
* Form for sending letters on email.
*/
class ManufactureMarketplaceForm extends FormBase {

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'manufacture_marketplace';
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state, $product_id = NULL) {

    // load Product for getting variations, title, storesId,
    $product = \Drupal\commerce_product\Entity\Product::load($product_id);
    $variations = $product->getVariations();
    // Creating $variation_items for variation #options
    $variation_items = [];
    foreach ($variations as $variation){
      $id = $variation->id();
      $sku = $variation->getSku();
      $price = $variation->getPrice();
      $variation_items[$id] = $sku . ' - ' . $price;
    }

    $store_id = array_shift($product->getStoreIds());
    $form_state->set('store_id', $store_id);

    $form['text']['#markup'] = "<div class='question-text'><h3>Order the product: </h3><p>'" .
      $product->getTitle() .
      "'</p></div>";
    $form['variation'] = [
      '#type' => 'radios',
      '#title' => $this->t('Variation'),
      '#options' => $variation_items,
      '#required' => TRUE,
    ];
    $form['quantity'] = [
      '#title' => t('Quantity'),
      '#type' => 'number',
      '#attributes' => [
        'type' => 'number',
        'min' => 1,
      ],
      '#default_value' => 1,
    ];
    $url_products_list = Url::fromRoute('manufacture_marketplace.list')->toString();
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Order'),
      '#suffix' => '<a href="' .
        $url_products_list .
        '"><h3>back to Manufacture products list ...</h3></a>',
    ];

    return $form;
  }

  /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state) { }

  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $store_id = $form_state->get('store_id');
    $variation_id = $form_state->getValue('variation');
    $quantity = $form_state->getValue('quantity');
    $order_type = 'default'; // If you have several order types, specify one here.

    $entity_manager = \Drupal::entityManager();
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');

    // Following line is the same as Drupal\commerce_store\Entity\Store::load($store_id);
    $store = $entity_manager->getStorage('commerce_store')->load($store_id);
    $product_variation = $entity_manager->getStorage('commerce_product_variation')->load($variation_id);
    // This is special: We must know if there is already a cart for the provided
    // order type and store:
    $cart = $cart_provider->getCart($order_type, $store);
    if (!$cart) {
      $cart = $cart_provider->createCart($order_type, $store);
    }
    // Here, we must create a new order item
    $order_item = $entity_manager->getStorage('commerce_order_item')->create(array(
      'type' => 'default', // Also, Commerce 2.x have a feature to define custom "line item types".
      'purchased_entity' => (string) $variation_id,
      'quantity' => $quantity, // Amount or quantity to be added to the cart.
      'unit_price' => $product_variation->getPrice(),
    ));
    $order_item->save();

//    $cart_manager->addOrderItem($cart, $order_item);
//    drupal_set_message(t('Product added to the cart.'));

    $user_id = \Drupal::currentUser()->id();
    $user_email = \Drupal::currentUser()->getEmail();
    $user_ip =  \Drupal::request()->getClientIp();

   // Next we create the billing profile.
    $profile = \Drupal\profile\Entity\Profile::create([
      'type' => 'customer',
      'uid' => $user_id, // The user id that the billing profile belongs to.
    ]);
    $profile->save();

    // Next, we create the order.
    $order = \Drupal\commerce_order\Entity\Order::create([
      'type' => 'default', // The default order type is 'default'.
      'state' => 'draft', // The states for the default order type are draft, completed, canceled
      'mail' => $user_email, // The email address for the order.
      'uid' => $user_id , // The user id the order belongs to.
      'ip_address' => $user_ip, // The ip address the user ordered from.
//      'order_number' => '6', // Sets the order number. If left out, will use the order's entity ID.
      'billing_profile' => $profile, // The profile we just created.
      'store_id' => $store_id, // The store we created above.
      'order_items' => [$order_item], // The order item we just created.
      'placed' => time(), // The time the order was placed.
    ]);
    $order->save();

    // You can also add order items to an order.
    $order->addItem($order_item);
    $order->save();

        drupal_set_message(t('The order is processed!'));

    return ;
  }
}


