<?php
/**
 * @file
 * Contains \Drupal\custom_block\Plugin\Block\MyBlock.
 */

namespace Drupal\sub_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\Unicode;

/**
 * Provides a Sub Block.
 *
 * @Block(
 *   id = "sub_block",
 *   admin_label = @Translation("SubBlock"),
 *   category = @Translation("subscription button block")
 * )
 */
class SubButtons extends BlockBase
{
    /**
     * {@inheritdoc}
     */
    public function build() {

        $output = \Drupal::service('path.current')->getPath();
        $route_name = mb_substr($output, 9, 19);

        $query = \Drupal::database() -> select('commerce_product_variation_field_data', 'cpvfd');
        //$query -> leftJoin('commerce_stock_transaction', 'cst', 'cpvfd.variation_id = cst.entity_id');
        $query -> fields('cpvfd', array('price__number', 'variation_id', 'product_id', 'title'));
        //$query -> fields('cst', array('entity_id', 'qty'));
        $query -> condition('product_id', $route_name);
        $results = $query -> execute() -> fetchAll();

        $rows = [];
        $sum=0;
        foreach ($results as $entity) {
            $rows[] = [
                'product_id' => $entity -> product_id,
                'title' => $entity -> title,
                'price' => $entity -> price__number,
            ];
        }
        $product_id = [
            '#theme' => 'sub_block',
            '#rows' => $rows,
        ];
        $product_id['#attached']['library'][] = 'sub_block/sub_block';
        return $product_id;
    }
    /**
     * {@inheritdoc}
     */
    public function getCacheMaxAge() {
        return 0;
    }
}