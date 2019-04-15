<?php

namespace Drupal\automatic_billing\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Automatic_billing module.
 */
class Automaticbilling extends ControllerBase {

    public function billing() {
        $build[] = [
            '#theme' => 'automatic_billing',
//            '#rows' => $rows,
        ];
        $build['#attached']['library'][] = 'automatic_billing/automatic_billing';
        return $build;

    }

    public function billingfin($variable) {

        $price = mb_substr($variable, 0, 2);
        $status = mb_substr($variable, 3, 9);
        $prod_name = mb_substr($variable, 13, 40);

//        kint($variable);
//        kint($status);
//        kint($price);
//        kint($prod_name);
//        die();

        $build[] = [
            '#theme' => 'automatic_billing_fin',
            '#status' => $status,
            '#price' => $price,
            '#prod_name' => $prod_name,
        ];
        $build['#attached']['library'][] = 'automatic_billing/automatic_billing';

        return $build;
    }
}