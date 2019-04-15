<?php

namespace Drupal\sub_block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;

/**
 * Provides route responses for the Example module.
 */
class Forsubs extends ControllerBase {

    public function content() {
        $account_name = \Drupal::currentUser()->getAccountName();
        $query = \Drupal::database() -> select('users_field_data', 'ufd');
        $query -> fields('ufd', array('changed'));
        $query -> condition('name', $account_name);
        $results = $query -> execute() -> fetchField();
        $changed_date = $results;

        $current_date = \Drupal::time()->getCurrentTime();

        if ($changed_date + 2592000 < $current_date){ // +2592000 seconds in 1 month
            $uid = \Drupal::currentUser()->id();
            $user = \Drupal\user\Entity\User::load($uid);
            $role = "sub10dollar";
            $user->removeRole($role);
            $user->save();
        }

//      $exp_date = $changed_date + 2592000;
//      drupal_set_message('Changed date - ' . $changed_date . ' Current date - ' . $current_date . ' Expired date - ' . $exp_date,'status');

        if (\Drupal::currentUser()->isAnonymous()) {

            drupal_set_message('You are not authorized! Please LOG IN or CREATE new account.','warning');
            return $this->redirect('user.login');

        } else {
            return array(
                '#type' => 'markup',
                '#markup' => t('Press "Pay with PayPal" button for subscription.'),
            );
        }
    }

    public function transstatus($variable) {
        drupal_set_message('Transaction status - ' . $variable,'status');

        if ($variable === "COMPLETED"){
            $uid = \Drupal::currentUser()->id();
            $user = \Drupal\user\Entity\User::load($uid);
            $role = "sub10dollar";
            $user->addRole($role);
            $user->save();
        }

        return array(
            '#type' => 'markup',
            '#markup' => t('Now you are SUBSCRIBED!! Unique content available'),
        );
    }
}