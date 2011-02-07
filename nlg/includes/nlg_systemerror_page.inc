<?php
// $Id$

/**
  * nlg_systemerror_page page 
  * @file
  * nlg_systemerror_page.inc 
  * @package
  * sfsu.portlet.systemerror.portlets.includes
  *
  * The hook required for nlg_systemerror_page page and form.
  */

/**
  * Implementation of hook template_preprocess().
  */
function template_preprocess_nlg_systemerror_form($vars) {
  $vars['nlg_systemerror_page_form_tag'] = drupal_get_form('nlg_systemerror_page_form');
}

/**
  * Implementation of Drupal Form API form function.
  * Works similarly to a Drupal hook, in the form of [form_name]_form()
  */
function nlg_systemerror_page_form() {
  $form = array();

  return $form;
}

/**
  * Implementation of Drupal Form API form function.
  * Works similarly to a Drupal hook, in the form of [form_name]_form_validate()
  */
function nlg_systemerror_page_form_validate($form, $form_state) {
}

/**
  * Implementation of Drupal Form API form_submit function.
  * Works similarly to a Drupal hook, in the form of [form_name]_form_submit()
  */
function nlg_systemerror_page_form_submit($form, $form_state) {
}
