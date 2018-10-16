<?php
//
///**
//* @file
//* Contains \Drupal\pager\Plugin\Block\PagerBlock.
//*/
//namespace Drupal\pager\Plugin\Block;
//
//use Drupal\Core\Block\BlockBase;
//use Drupal\Core\Link;
//use Drupal\Core\Render\Markup;
//use Drupal\Core\Template\Attribute;
//use Drupal\Core\Url;
//
///**
//* Form for redirect on node.
//*
//* @Block(
//* id = "pager",
//* admin_label = @Translation("Pager Form"),
//* category = @Translation("Blocks")
//* )
//*/
//
//class PagerBlock extends BlockBase {
//  /**
//  * {@inheritdoc}
//  */
//
//  public function build() {
//
//      // Contact_us form
//      $attribute_contact_button = new Attribute ([
//        'type' => "button",
//        'value' => "Contact us",
//      ]);
//      $ajax_attr_contact = [
//        'class' => 'use-ajax',
//        'data-dialog-type' => 'modal',
//        'data-dialog-options'=>'{"width":"50%"}',
//        'id' => 'contact-us'
//      ];
//      $options = [
//        'absolute' => TRUE,
//        'attributes' => $ajax_attr_contact
//      ];
//      $button = "<input" . $attribute_contact_button . ">";
//      $link = Url::fromUri('internal:/contact_us', $options);
//      $linked_contact_button = Link::fromTextAndUrl(Markup::create($button), $link);
//
//    return ([
//      '#theme' => 'pager_us_template',
//      '#button_contact' => $linked_contact_button,
//    ]);
//  }
//}
