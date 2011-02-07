<?php
// $Id$

/**
  * nlg_overview_page page 
  * @file
  * nlg_overview_page.inc 
  * @package
  * nlg.includes
  *
  * The hook required for nlg_overview_page page and form.
  */

/**
 * Implementation of hook_template_preprocess().
 */
function template_preprocess_nlg_overview_form($vars) {
  $nlg_module_path = drupal_get_path(module, 'nlg');
  $nlg_status_ok = variable_get('nlg_nagios_service_status_ok', 'OK');
  $nlg_status_warning = variable_get('nlg_nagios_service_status_warning', 'Warning');
  $nlg_status_critical = variable_get('nlg_nagios_service_status_critical', 'Critical');
  $nlg_status_unknown = variable_get('nlg_nagios_service_status_unknown', 'Unknown');
  $nlg_service_status_text = array(
    $nlg_status_ok, 
    $nlg_status_warn,
    $nlg_status_critical,
    $nlg_status_unknown
  );
  
  $nlg_service_status_ok_short = variable_get('nlg_nagios_service_status_ok_short', 'OK');
  $nlg_service_status_warn_short = variable_get('nlg_nagios_service_status_warn_short', 'W');
  $nlg_service_status_critical_short = variable_get('nlg_nagios_service_status_critical_short', 'C');
  $nlg_service_status_unknown_short = variable_get('nlg_nagios_service_status_unknown_short', 'U');
  $nlg_service_status_short = array(
    $nlg_service_status_ok_short,
    $nlg_service_status_warn_short,
    $nlg_service_status_critical_short,
    $nlg_service_status_unknown_short
  );

  $nlg_pollerobject = get_output_template();
  if (!$nlg_pollerobject) {
    drupal_set_message(t('Nagios Looking Glass is currently unavailable.'), 'error', FALSE);
    $nlg_systemerror = TRUE;
  }
  else {
    $nlg_services = array();
    foreach ($nlg_pollerobject->Hosts as &$hosts) {
      if ($hosts->HostStatus == '0') {
        $hosts->StatusText = $nlg_status_ok;
        $hosts->ServiceStatus = $nlg_service_status_ok_short;
      }
      elseif ($hosts->HostStatus == '1') {
        $hosts->StatusText = $nlg_status_warning;
        $hosts->ServiceStatus = $nlg_service_status_warn_short;      
      }
      elseif ($hosts->HostStatus == '2') {
        $hosts->StatusText = $nlg_status_critical;
        $hosts->ServiceStatus = $nlg_service_status_critical_short;
      }
      elseif ($hosts->HostStatus == '3') {
        $hosts->StatusText = $nlg_status_unknown;
        $hosts->ServiceStatus = $nlg_service_status_unknown_short;
      }
      $nlg_services[] = nlg_replacesingleserverinfo($hosts->HostServices, $nlg_service_status_short, $nlg_service_status_text);
      unset($hosts);
    }
    // if feed is cached, ::$NLG_FeedSource = "cache", else ::$NLG_FeedSource = "live"
    if ($nlg_pollerobject->NLG_FeedSource == "live") {
      $nlg_feed_cached_text = variable_get('nlg_feed_live_text', 'Feed retrieved live at ');
    }
    else {
      $nlg_feed_cached_text = variable_get('nlg_feed_cached_text', 'Cache feed retrieved at ');
    }

    $nlg_feed_cached = $nlg_feed_cached_text . FormatDate($nlg_pollerobject->Nagios_FeedUpdated, "H:i A l, F d, Y");
  }

  $vars['nlg_systemerror'] = $nlg_systemerror;
  $vars['nlg_pollerobject'] = $nlg_pollerobject->Hosts;
  $vars['nlg_services'] = $nlg_services;
  $vars['nlg_path'] = base_path() . drupal_get_path(module, 'nlg');
  $vars['nlg_feed_cached'] = $nlg_feed_cached;
}

/**
  * Implementation of Drupal Form API form function.
  * Works similarly to a Drupal hook, in the form of [form_name]_form()
  */
function nlg_overview_page_form() {
  $form = array();

  return $form;
}

/**
  * Implementation of Drupal Form API form function.
  * Works similarly to a Drupal hook, in the form of [form_name]_form_validate()
  * In this case we don't need it, but it's here for future purposes
  */
function nlg_overview_page_form_validate($form, $form_state) {
}

/**
  * Implementation of Drupal Form API form_submit function.
  * Works similarly to a Drupal hook, in the form of [form_name]_form_submit()
  * Also not needed now, but it's here for future purposes
  */
function nlg_overview_page_form_submit($form, $form_state) {

}

/** 
 * Function for handling status of servers
 */

function nlg_replacesingleserverinfo($pollerobject_services, $status_short, $status_text) {
        $nlg_clientdateformat = variable_get('nagios_client_date_format', "m/d/Y H:i:s");
        $nlg_clienttrimmetricnames = variable_get('nagios_client_trim_metric_names', 0);

        // TODO: Add this functionality back in
        // trim hostnames to a specific length if configured to
        /*
        if ($nlg_clienttrimemetricnames > 0 && strlen($NagiosService->ServiceName) > $nlg_clientrimmetricnames) {
          $ThisServiceName = substr($ThisServiceName, 0, $ClientTrimMetricNames) . " ...";
        }*/

        //$FixNagiosStatus = (string) $NagiosService->ServiceStatus;

        foreach ($pollerobject_services as &$services) {
          $services->metric_name = $services->ServiceName;
          $services->metric_name_full = $services->ServiceName;
          $services->metric_status = $status_short[$services->ServiceStatus];
          $services->metric_status_text = $status_text[$services->ServiceStatus];
          $services->metric_last_check = FormatDate($services->LastCheck, $nlg_clientdateformat);
          $services->metric_next_check = FormatDate($services->NextCheck, $nlg_clientdateformat);
        }
        
        return $pollerobject_services;
}
