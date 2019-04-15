<?php

namespace Drupal\pay_block\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class BuyNow extends ControllerBase {

    public function buynowstatus($variable) {
        $status = mb_substr($variable, 0, 9);
        $prod_id = mb_substr($variable, 10, 3);
        $entity_id = mb_substr($variable, 14, 3);

//        drupal_set_message('Transaction status - ' . $status . ' prod id - ' .$prod_id . ' Entity ID - ' . $entity_id ,'status');
        drupal_set_message('Transaction status - ' . $status ,'status');

        if ($status === "COMPLETED"){

            $query = \Drupal::database() -> select('commerce_product_variation_field_data', 'cpvfd');
            $query -> leftJoin('commerce_stock_transaction', 'cst', 'cpvfd.variation_id = cst.entity_id');
            $query -> fields('cpvfd', array('variation_id', 'product_id'));
            $query -> fields('cst', array('entity_id', 'qty'));
            $query -> condition('product_id', $prod_id);
            $query -> condition('transaction_type_id', 6);
            $results = $query -> execute() ->fetchField(3);
            $current_qty = $results;

            $current_qtys = mb_substr($current_qty, 0, 3);

//            drupal_set_message('Current qty - ' . $current_qtys,'status');

            $update = \Drupal::database()->update('commerce_stock_transaction');
            $update->fields([
                'qty' => $current_qtys -1,
            ]);
            $update->condition('entity_id', $entity_id);
            $update->condition('transaction_type_id',6);
            $update->execute();

            $build = [
                '#theme' => 'pay_block_fin',
                '#rows' => $current_qty,
            ];
            $build['#attached']['library'][] = 'pay_block/pay_block';

            return $build;

        } else {
            return array(
                '#type' => 'markup',
                '#markup' => t('Not success'),
            );
        }
    }
}