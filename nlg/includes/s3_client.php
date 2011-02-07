<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_client.php                                                  |
   | Description:      The client that connects to s3_poller.php on server-side       |
   |----------------------------------------------------------------------------------|
   | This application is distributed under the terms of the Creative Commons Public   |
   | license.  Your copy of the license is called 'LICENSE.txt' and is in the root of |
   | the application distribution files.                                              |
   |                                                                                  |
   | You may also view the license online at the following URLs:                      |
   |                                                                                  |
   |     http://creativecommons.org/licenses/by-sa/2.5/                               |
   |     http://creativecommons.org/licenses/by-sa/2.5/legalcode                      |
   |                                                                                  |
   | All work on this application is the copyright of the author (Andy Shellam) and   |
   | the author's company (Network Mail.)  The author and Network Mail retain the     |
   | copyright until further notice or when the application is terminated/            |
   | discontinued.                                                                    |
   |                                                                                  |
   | Please respect the work that has gone into this application - don't charge for   |
   | re-distribution of this application, and don't pass it off as your own work. You |
   | may charge for commercial services relating to this application, but not for the |
   | sale of the application itself, and you must provide all source code, without    |
   | using technologies such as encryption/encoding.                                  |
   |                                                                                  |
   | Thank you for using Nagios Looking Glass!    - Andy                              |
   +----------------------------------------------------------------------------------+
*/

require(drupal_get_path(module, 'nlg') ."/includes/s3_config_stub.inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/s3_download_stub.inc.php");

function init_client() {
// Added in v1.0.0 - check whether to enable debugging
if ($Stub_ClientEnableDebugging == 1)
{
  // Check if we need to enable debugging in query string
  if (isset($_GET['debug']))
  {
    $DebuggingRequired = urldecode(htmlspecialchars($_GET['debug']));
    if ($DebuggingRequired == "yes")
    {
      define("DEBUG_REQUIRED", "yes");
      header("Content-type: text/plain");

      if (defined("DEBUG_REQUIRED")) {
        echo "+---------------------------------------------------------------------------+\n";
        echo "| Running Nagios Looking Glass in Debug mode                                |\n";
        echo "|---------------------------------------------------------------------------|\n";
        echo "| *** WARNING ***                                                           |\n";
        echo "| Leaving \$Stub_ClientEnableDebugging enabled can expose information about |\n";
        echo "| your environment to your visitors - don't forget to disable when you're   |\n";
        echo "| done debugging!                                                           |\n";
        echo "|                                                                           |\n";
        echo "| NOTE: No server/metric statuses will be shown in debug mode.              |\n";
        echo "+---------------------------------------------------------------------------+\n\n";
      }
    }
  }
}

// Now check we've got all files we need
if (defined("DEBUG_REQUIRED")) {echo "*** S3 Client -> CheckSyncFiles() running ***\n";};
if (!CheckSyncFiles())
{
  if(!defined("DEBUG_REQUIRED"))
  {
    echo ProcessTemplate_Error("Could not sync all required files with the server.");
    exit();
  }
}
if (defined("DEBUG_REQUIRED")) {echo "*** S3 Client -> CheckSyncFiles() finished ***\n\n";};
}
// OK we have all our sync files

require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_class.inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_functions.inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_lang_" . variable_get('nlg_lang', 'en') . ".inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_filter.inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_filter_auth.inc.php");

$Templates = Array();

$_SESSION['systat_templates'] = $Templates;

function CheckSyncFiles()
{
  require(drupal_get_path(module, 'nlg') ."/includes/s3_config_stub.inc.php");
  /*
   * CheckSyncFiles()
   * Checks to see that all our required "sync-files" exist and have some data
   */

  $AllFilesOK = true;

  // part of this code pinched from s3_download_stub.php
  foreach($Stub_SharedFiles as $DownloadFile)
  {
    if (defined("DEBUG_REQUIRED")) {echo "Checking " . $DownloadFile . " ... ";};
    // we return FALSE if any file is not found

    if (!file_exists(drupal_get_path(module, 'nlg') ."/includes/sync-files/" . $DownloadFile) || filesize(drupal_get_path(module, 'nlg') ."/includes/sync-files/" . $DownloadFile) == 0)
    {
      $AllFilesOK = FALSE;
      if (defined("DEBUG_REQUIRED")) {echo "FILE NOT FOUND or FILE IS EMPTY\n";};
    } else {
      if (defined("DEBUG_REQUIRED")) {echo "File is OK\n";};
    }
  }

  return $AllFilesOK;
}

function CheckTimezone()
{
  /*
   * CheckTimezone()
   * Check and convert the current timezone if required
   *
   * Added in v1.1.0 Beta 1
   */

  global $SystemTimezone;

  if (strlen($SystemTimezone) > 0)
  {
    return @date_default_timezone_set($SystemTimezone);
  } else {
    return true;
  }
}

function CheckTemplates(&$NagiosPollerObject)
{
  /*
   * CheckTemplates()
   * Read each template directory in ./templates, and check we have all the required files
   * Also check we have a 'default' template
   * If you want to include the template name in a file, put %1\$s in the filename where
   *   you want the template name to be
   */
  
  $Templates = $_SESSION['systat_templates'];
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_lang_" . variable_get('nlg_lang', 'en') . ".inc.php");

  $TemplateFilesRequired = Array(
    "comment_detail.tpl.php",
    "%1\$s.css",
    "%1\$s.tpl.php",
    "feed_info.tpl.php",
    "filter_chooser.tpl.php",
    "network_status.tpl.php",
    "server_detail.tpl.php",
    "server_pager.tpl.php",
    "server_summary.tpl.php",
    "server_summary_no_result.tpl.php",
    "template_chooser.tpl.php",
    "update_available.tpl.php",
    "user.js"
  );

  // find all directories within "./templates", ignoring "." and ".."
  $TemplateScanner = scandir(drupal_get_path(module, 'nlg') ."/theme/");

  for ($intC = 2; $intC < count($TemplateScanner); $intC++)
  {
    // assume the template has all required files, and set it FALSE if it hasn't
    $TemplateFilesOK = true;

    // check each required file is in the template
    foreach($TemplateFilesRequired as $TemplateFile)
    {
      if (!is_readable(drupal_get_path(module, 'nlg') ."/theme/" . $TemplateScanner[$intC] . "/" . sprintf($TemplateFile, $TemplateScanner[$intC])))
      {
        $TemplateFilesOK = FALSE;
      }
      unset($TemplateFile);
    }

    // If the template has all required files, add it to our template list
    if ($TemplateFilesOK === true)
    {
      $Templates[count($Templates)] = $TemplateScanner[$intC];
    }

    unset($TemplateFilesOK);
  }

  unset($TemplateScanner);
  unset($TemplateFilesRequired);
  
  unset($_SESSION['systat_templates']);
  $_SESSION['systat_templates'] = $Templates;
  
  // Check we have the default template
  if (!in_array("default", $Templates))
  {
    $NagiosPollerObject->LastPollerError = $Language['NO_DEFAULT_TEMPLATE'];
    return FALSE;
  }

  return true;
}

/**
 * PingPoller()
 * Sends a call to the poller to get the latest data from Nagios
 * Client caches the result and uses it later if need be (as of 1.0.0#PRE)
 */

function PingPoller(&$PollerObject) { //, $FilterID, $GroupID) {
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_lang_" . variable_get('nlg_lang', 'en') . ".inc.php");
  
  $ServerFeed_URL = variable_get('nlg_serverfeed_url', 'http://localhost/nlg/server/s3_poller.php');

  $ServerFeed_AuthEnabled = variable_get('nlg_serverfeed_authenabled', '');
  $ServerFeed_AuthUsername = variable_get('nlg_authusername', '');
  $ServerFeed_AuthPassword = variable_get('nlg_authpassword', '');
  $Language = variable_get('nlg_lang', 'en');

  if ($ServerFeed_AuthEnabled == 1) {
    $ServerFeed_URL = preg_replace("/^http([s]*):\/\/(.+)$/", "http$1://" . $ServerFeed_AuthUsername . ":" . $ServerFeed_AuthPassword . "@$2", $ServerFeed_URL);
  }
  elseif ($ServerFeed_AuthEnabled == 2) {
    if(array_key_exists("PHP_AUTH_USER", $_SERVER) && array_key_exists("PHP_AUTH_PW", $_SERVER)) {
      $ServerFeed_URL = preg_replace("/^http([s]*):\/\/(.+)$/", "http$1://" . $_SERVER['PHP_AUTH_USER'] . ":" . $_SERVER['PHP_AUTH_PW'] . "@$2", $ServerFeed_URL);
    }
  }

  // apply a filter at the poller feed if requested
  // as of 1.0.0 - $FilterID will always be set, even if it's just zero
  // check if we have already given a query string to $ServerFeedURL
/*
  if (strpos($ServerFeed_URL, "?") === FALSE) {
    $ServerFeed_URL = $ServerFeed_URL . "?fid=" . $FilterID;
  } 
  else {
    $ServerFeed_URL = $ServerFeed_URL . "&fid=" . $FilterID;
  }
  // apply a host group at the poller feed if requested
  // as of 1.0.0 - $GroupID will always be set, even if it's just zero
  // check if we have already given a query string to $ServerFeedURL
  if (strpos($ServerFeed_URL, "?") === FALSE) {
    $ServerFeed_URL = $ServerFeed_URL . "?gid=" . $GroupID;
  }
  else {
    $ServerFeed_URL = $ServerFeed_URL . "&gid=" . $GroupID;
  }
*/
  if(defined("DEBUG_REQUIRED")) {
    echo "Attempting to contact the poller at " . preg_replace("/^http([s]*):\/\/(.+):(.+)@(.+)$/", "http$1://xxx:xxx@$4", $ServerFeed_URL) . "\n\n";
  }
  $PollerFeedRaw = file_get_contents($ServerFeed_URL);

  if ($PollerFeedRaw === FALSE) {
    $PollerObject->LastPollerError = $Language['POLLING_SERVER_DOWN'];
    return FALSE;
  }
  $PollerFeed = split("!!", $PollerFeedRaw);
  /**
   * Now we're left with:
   * $PollerFeed = Array(
   *   [0] => **NLGPOLLER <Token header and app name>
   *   [1] => Feed/X.Y.Z <Version of the feed>
   *   [2] => hostname.domain.co.uk <Hostname the feed came from>
   *   [3] => TRUE <Result of the feed processing on the server - TRUE/FALSE>
   *   [4] => <base64, serialized representation of the NLGPoller class (when [3] == TRUE) > -or- <base64 representation of the feed creation error (when [3] == FALSE) >
   *   [5] => 2ec761ee83f8769108f6612694831116** <MD5 checksum of the base64 data [4]>
   *   [6] => NLGPOLLER** <app name and token trailer>
   * )
   */

  // SANITY CHECKS ON THE DOWNLOADED FEED //
  // ==================================== //
  // First check the base64 data - recalculate the MD5 hash and compare
  if ($PollerFeed[5] != md5($PollerFeed[4])) {
    $PollerObject->LastPollerError = variable_get('nlg_polling_checksum_diff', 'Checksum does not match');
    if (defined("DEBUG_REQUIRED")) {
      echo "The feed download appears to be inaccurate: " . $PollerObject->LastPollerError . "\n\n";
    }
    return FALSE;
  }
  // Check the server generated the feed OK
  if ($PollerFeed[3] == "FALSE") {
    // need to de-crypt the error string
    $PollerObject->LastPollerError = base64_decode($PollerFeed[4]);

    if (defined("DEBUG_REQUIRED")) {
      echo "The poller returned a FAILURE code: " . $PollerObject->LastPollerError . "\n\n";
    }
    return FALSE;
  }

  // Check the version of the class generated on the server is the same version of the class we're using on the client
  // No longer need this as the class files are sync'd between server and client - removed in 1.0.0#PRE

  // ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ //
  // END SANITY CHECKS ON THE DOWNLOADED FEED //

  // all OK so far, now need to decrypt and deserialize the class data and check it is a valid class
  $NLGPoller = unserialize(base64_decode($PollerFeed[4]));

  if ($NLGPoller instanceof S3_NagiosPoller) {
    if (defined("DEBUG_REQUIRED")) {
      echo "All data received and decrypted OK\n\n";
    }
    $PollerObject = $NLGPoller;
    unset($NLGPoller);
    unset($PollerFeed);
    unset($PollerFeedRaw);
    return true;
  } 
  else {
    $PollerObject->LastPollerError = $Language['POLLING_CORRUPT_FEED'];
    if (defined("DEBUG_REQUIRED")) {
      echo "The decrypted data received from the poller is not a valid S3_NagiosPoller class object.\n\n";
    }
    unset($NLGPoller);
    unset($PollerFeed);
    unset($PollerFeedRaw);
    return FALSE;
  }
}

// The below functions are all templating functions

function ReplaceSingleServerInfo($HostTemplateString, $NagiosHost)
{
  /*
   * ReplaceSingleServerInfo()
   * Process the [[SERVER_*]] directives for a single host
   */

  $HostStatus = array("OK", "W", "C", "UN");
  $HostStatusText = array(
          variable_get('nlg_nagios_service_status_ok', 'OK'),
          variable_get('nlg_nagios_service_status_warning', 'Warning'),
          variable_get('nlg_nagios_service_status_critical', 'Critical'),
          variable_get('nlg_nagios_service_status_warning', 'Unknown'),
        );
        $ClientDateFormat = variable_get('nlg_clientdateformat','');
  $ClientTrimServerNames = variable_get('nlg_trim_server_names','0');
        
  // new in v1.0.0 - trim hostnames to a specific length (but only if the config value is not zero)
  $ThisHostName = $NagiosHost->HostName;
  if ($ClientTrimServerNames > 0 && strlen($NagiosHost->HostName) > $ClientTrimServerNames)
  {
    $ThisHostName = substr($ThisHostName, 0, $ClientTrimServerNames) . " ...";
  }
        if($ThisHostName == 'Mail Services') { 
          $ThisHostName = 'Exchange E-mail';
        }
  $HostTemplateString = str_replace("[[SERVER_NAME]]", $ThisHostName, $HostTemplateString);
  $HostTemplateString = str_replace("[[SERVER_NAME_FULL]]", $NagiosHost->HostName, $HostTemplateString);

  $HostTemplateString = str_replace("[[SERVER_UID]]", $NagiosHost->HostID, $HostTemplateString);
  $HostTemplateString = str_replace("[[SERVER_STATUS]]", $HostStatus[$NagiosHost->HostStatus], $HostTemplateString);
  $HostTemplateString = str_replace("[[SERVER_STATUS_TEXT]]", $HostStatusText[$NagiosHost->HostStatus], $HostTemplateString);
  $HostTemplateString = str_replace("[[SERVER_LAST_CHECK]]", FormatDate($NagiosHost->LastCheck, $ClientDateFormat), $HostTemplateString);
  $HostTemplateString = str_replace("[[SERVER_NEXT_CHECK]]", FormatDate($NagiosHost->NextCheck, $ClientDateFormat), $HostTemplateString);
  $HostTemplateString = str_replace("[[SERVER_CHECK_RESULT]]", $NagiosHost->CheckResult, $HostTemplateString);
  $HostTemplateString = str_replace("[+SERVER_METRIC_TOTAL+]", $NagiosHost->ServiceCount_Total, $HostTemplateString);
  $HostTemplateString = str_replace("[+SERVER_METRIC_OK+]", $NagiosHost->ServiceCount_OK, $HostTemplateString);
  $HostTemplateString = str_replace("[+SERVER_METRIC_WARN+]", $NagiosHost->ServiceCount_Warn, $HostTemplateString);
  $HostTemplateString = str_replace("[+SERVER_METRIC_FAIL+]", $NagiosHost->ServiceCount_Fail, $HostTemplateString);
  $HostTemplateString = str_replace("[+SERVER_METRIC_UNKNOWN+]", $NagiosHost->ServiceCount_Unknown, $HostTemplateString);

  // Work out number of degraded metrics
  $HostDegradedMetrics = $NagiosHost->ServiceCount_Warn + $NagiosHost->ServiceCount_Fail + $NagiosHost->ServiceCount_Unknown;
  $HostTemplateString = str_replace("[+SERVER_METRIC_BAD+]", $HostDegradedMetrics, $HostTemplateString);
        $_SESSION['nlg_host_template'] = $HostTemplateString;
  unset($HostDegradedMetrics);

  return $HostTemplateString;
}

function ReplaceSingleMetricInfo($ServiceTemplateString, $NagiosService)
{
  /*
   * ReplaceSingleMetricInfo()
   * Process the [[METRIC_*]] directives for a specific metric
   */

  $ClientDateFormat = variable_get('nagios_client_date_format', "m/d/Y H:i:s");
  $ClientTrimMetricNames = variable_get('nagios_client_trim_metric_names', 0);
        $ServiceStatus = variable_get('nagios_service_status', Array("OK", "W", "C", "UN"));
  $ServiceStatusText = variable_get('nagios_service_status_text', Array("OK", "Warning", "Critical", "Unknown"));
  // new in v1.0.0 - trim hostnames to a specific length (but only if the config value
  // is not zero)
  $ThisServiceName = $NagiosService->ServiceName;
  if ($ClientTrimMetricNames > 0 && strlen($NagiosService->ServiceName) > $ClientTrimServerNames)
  {
    $ThisServiceName = substr($ThisServiceName, 0, $ClientTrimMetricNames) . " ...";
  }
  $ServiceTemplateString = str_replace("[[METRIC_NAME]]", $ThisServiceName, $ServiceTemplateString);
  $ServiceTemplateString = str_replace("[[METRIC_NAME_FULL]]", $NagiosService->ServiceName, $ServiceTemplateString);

  $ServiceTemplateString = str_replace("[[METRIC_UID]]", $NagiosService->ServiceID, $ServiceTemplateString);
  $FixNagiosStatus = (string) $NagiosService->ServiceStatus;
        $ServiceTemplateString = str_replace("[[METRIC_STATUS]]", $ServiceStatus[$NagiosService->ServiceStatus], $ServiceTemplateString);
  $ServiceTemplateString = str_replace("[[METRIC_STATUS_TEXT]]", $ServiceStatusText[$NagiosService->ServiceStatus], $ServiceTemplateString);
  $ServiceTemplateString = str_replace("[[METRIC_LAST_CHECK]]", FormatDate($NagiosService->LastCheck, $ClientDateFormat), $ServiceTemplateString);
  $ServiceTemplateString = str_replace("[[METRIC_NEXT_CHECK]]", FormatDate($NagiosService->NextCheck, $ClientDateFormat), $ServiceTemplateString);
  $ServiceTemplateString = str_replace("[[METRIC_CHECK_RESULT]]", $NagiosService->CheckResult, $ServiceTemplateString);

  return $ServiceTemplateString;
}

function ReplaceSingleCommentInfo($CommentTemplateString, $NagiosComment, $CommentType)
{
  /*
   * ReplaceSingleCommentInfo()
   * Process the [[COMMENT_*]] directives for a single comment
   */
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
  //global $ClientDateFormat;

  $CommentTemplateString = str_replace("[[COMMENT_UID]]", $NagiosComment->CommentID, $CommentTemplateString);
  $CommentTemplateString = str_replace("[[COMMENT_TEXT]]", $NagiosComment->CommentText, $CommentTemplateString);
  $CommentTemplateString = str_replace("[[COMMENT_AUTHOR]]", $NagiosComment->Author, $CommentTemplateString);
  $CommentTemplateString = str_replace("[[COMMENT_TIME]]", date($ClientDateFormat, $NagiosComment->EntryTime), $CommentTemplateString);
  $CommentTemplateString = str_replace("[[COMMENT_SERVER]]", $NagiosComment->Host, $CommentTemplateString);

  // added in 1.0.0 - any [*METRIC*]...[*METRIC*] sections are replaced for a service comment
  if ($CommentType == "service")
  {
    // remove the [*METRIC*]...[*METRIC*] tags
    $CommentTemplateString = preg_replace("/\[\*METRIC\*\](.+)\[\*METRIC\*\]/", "$1", $CommentTemplateString);
    $CommentTemplateString = str_replace("[[COMMENT_METRIC]]", $NagiosComment->Service, $CommentTemplateString);

  } else {
    $CommentTemplateString = preg_replace("/\[\*METRIC\*\].+\[\*METRIC\*\]/", "", $CommentTemplateString);
  }

  return $CommentTemplateString;
}

function ReplaceFilterChooser($Template, $NagiosPollerObject)
{
  /*
   * ReplaceFilterChooser()
   * Process the [[FILTER_CHOOSER]] directive with our templated filter-chooser
   */
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_filter.inc.php");
  //global $HostFilter;

  $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/theme/filter_chooser.tpl.php");

  $FilterTemplateMatchCount = preg_match("/\<!--\[\*S_FILTER_ITEM\*\]-->(.*)<!--\[\*E_FILTER_ITEM\*\]-->/Us", $TemplateString, $FilterTemplateMatches);
  if ($FilterTemplateMatchCount > 0)
  {
    if (count($FilterTemplateMatches[0]) > 0)
    {
      $FilterListTemplate = "";
      $FilterID = 0;

      // Process each record for each filter in the config
      foreach($HostFilter as $NLGFilter)
      {
        // process this host template and add to our server list
        $FilterListTemplate .= $FilterTemplateMatches[1];
        $FilterListTemplate = str_replace("[[FILTER_UID]]", $FilterID, $FilterListTemplate);
        $FilterListTemplate = str_replace("[[FILTER_NAME]]", $NLGFilter->DisplayText, $FilterListTemplate);

        // ARCi#87 - replace [*THIS*] containers
        if ($NLGFilter->DisplayText == $NagiosPollerObject->FilterApplied)
        {
          $FilterListTemplate = preg_replace("/\[\*CURRENT\*\](.*)\[\*CURRENT\*\]/", "$1", $FilterListTemplate);
        } else {
          $FilterListTemplate = preg_replace("/\[\*CURRENT\*\].*\[\*CURRENT\*\]/", "", $FilterListTemplate);
        }

        $FilterID++;
        unset($NLGFilter);
      }

      $TemplateString = preg_replace("/\<!--\[\*S_FILTER_ITEM\*\]-->(.*)<!--\[\*E_FILTER_ITEM\*\]-->/Us", $FilterListTemplate, $TemplateString);
      unset($FilterListTemplate);
    }
  }

  return $TemplateString;
}

function ReplaceTemplateChooser($Template)
{
  /*
   * ReplaceTemplateChooser()
   * Process the [[TEMPLATE_CHOOSER]] directive with our templated template-chooser
   */
  
  //global $Templates;
  $Templates = $_SESSION['systat_templates'];
  
  $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/theme/template_chooser.tpl.php");

  $TemplateChooserMatchCount = preg_match("/\<!--\[\*S_TEMPLATE_ITEM\*\]-->(.*)<!--\[\*E_TEMPLATE_ITEM\*\]-->/Us", $TemplateString, $TemplateChooserMatches);
  if ($TemplateChooserMatchCount > 0)
  {
    if (count($TemplateChooserMatches[0]) > 0)
    {
      $TemplateChooserTemplate = "";

      // Process each record for each filter in the config
      foreach($Templates as $aTemplate)
      {
        // process this template entry and add to our drop-down
        $TemplateChooserTemplate .= $TemplateChooserMatches[1];
        $TemplateChooserTemplate = str_replace("[[TEMPLATE_NAME]]", $aTemplate, $TemplateChooserTemplate);

        // ARCi#87 - replace [*THIS*] containers
        if ($aTemplate == $Template)
        {
          $TemplateChooserTemplate = preg_replace("/\[\*CURRENT\*\](.*)\[\*CURRENT\*\]/", "$1", $TemplateChooserTemplate);
        } else {
          $TemplateChooserTemplate = preg_replace("/\[\*CURRENT\*\].*\[\*CURRENT\*\]/", "", $TemplateChooserTemplate);
        }

        unset($aTemplate);
      }

      $TemplateString = preg_replace("/\<!--\[\*S_TEMPLATE_ITEM\*\]-->(.*)<!--\[\*E_TEMPLATE_ITEM\*\]-->/Us", $TemplateChooserTemplate, $TemplateString);
      unset($TemplateChooserTemplate);
    }
  }

  return $TemplateString;
}

function ReplaceServerPager($Template, $NagiosPollerObject)
{
  /*
   * ReplaceServerPager()
   * Process the [[SERVER_PAGER]] directive with our templated pager navigator
   */
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
  //global $ClientPager_RangeSize;

  $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/includes/templates/" . $Template . "/server_pager.tpl");

  $PagerGroupMatchCount = preg_match("/\<!--\[\*S_PAGER_GROUP\*\]-->(.*)<!--\[\*E_PAGER_GROUP\*\]-->/Us", $TemplateString, $PagerGroupMatches);
  if ($PagerGroupMatchCount > 0)
  {
    if (count($PagerGroupMatches[0]) > 0)
    {
      $PagerGroupTemplate = "";

      // first check we do actually have some servers returned
      if ($NagiosPollerObject->Nagios_HostFilterCount > 0)
      {

        // find out our first and last group to show in the pager, courtesy of the
        // $ClientPager_RangeSize setting
        $StartGroupID = $NagiosPollerObject->CurrentGroup - $ClientPager_RangeSize;
        $EndGroupID = $NagiosPollerObject->CurrentGroup + $ClientPager_RangeSize;

        // Process each record for each filter in the config
        for ($intC = $StartGroupID; $intC <= $EndGroupID; $intC++)
        {
          // only run the template if the group exists (eg. this wont be run if
          // we're at the start or end groups)
          if (array_key_exists($intC, $NagiosPollerObject->HostGroups))
          {
            // process the pager group template and add to our pager
            $PagerGroupTemplate .= $PagerGroupMatches[1];
            $PagerGroupTemplate = str_replace("[[GROUP_UID]]", $intC, $PagerGroupTemplate);
            $PagerGroupTemplate = str_replace("[[GROUP_START]]", $NagiosPollerObject->HostGroups[$intC]['lower_bound_display'], $PagerGroupTemplate);

            // ARCi#6 - improved handling for the GROUP_END directives

            // if the end group index is the same as the start group index, remove the
            // 'ending' group display so we don't get a situation like "hosts 12 to 12"
            // instead we get "hosts 12"
            if ($NagiosPollerObject->HostGroups[$intC]['lower_bound_index'] == $NagiosPollerObject->HostGroups[$intC]['upper_bound_index'])
            {
              // first preg_replace for any mutliple instances
              $PagerGroupTemplate = preg_replace("/<!--\[\*GROUP_END\*\]-->(.*)<!--\[\*GROUP_END\*\]-->/", "", $PagerGroupTemplate);
            }

            // replace the group ending directive
            $PagerGroupTemplate = str_replace("[[GROUP_END]]", $NagiosPollerObject->HostGroups[$intC]['upper_bound_display'], $PagerGroupTemplate);
            // and finally remove any remaining <!--[*GROUP_END*]--> directives
            $PagerGroupTemplate = str_replace("<!--[*GROUP_END*]-->", "", $PagerGroupTemplate);
          }
        }

        unset($StartGroupID);
        unset($EndGroupID);
      }

      $TemplateString = preg_replace("/\<!--\[\*S_PAGER_GROUP\*\]-->(.*)<!--\[\*E_PAGER_GROUP\*\]-->/Us", $PagerGroupTemplate, $TemplateString);

      unset($PagerGroupTemplate);
    }
  }

  return $TemplateString;
}

function ReplaceGlobalParameters($TemplateString, $Template, $NagiosPollerObject)
{
  /*
   * ReplaceGlobalParameters()
   * Change parameters that can be included in any template file
   */
   
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_lang_" . variable_get('nlg_lang', 'en') . ".inc.php");
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_filter.inc.php");
  
  /*global $HostFilter;
  global $ClientDateFormat;
  global $ClientAdminEmailAddress;
  global $ClientSiteName;
  global $ClientCompanyName;
  global $Language;
  global $ClientRefreshTime;*/

  $TemplateString = str_replace("[[COMPANY_NAME]]", $ClientCompanyName, $TemplateString);
  $TemplateString = str_replace("[[NLG_ADMIN_CONTACT]]", $ClientAdminEmailAddress, $TemplateString);
  $TemplateString = str_replace("[[NAGIOS_VERSION]]", $NagiosPollerObject->Nagios_Version, $TemplateString);
  $TemplateString = str_replace("[[NAGIOS_FEED_UPDATED]]", FormatDate($NagiosPollerObject->Nagios_FeedUpdated, $ClientDateFormat), $TemplateString);
  $TemplateString = str_replace("[[TEMPLATE_NAME]]", $Template, $TemplateString);
  $TemplateString = str_replace("<!--[*FILTER_CHOOSER*]-->", ReplaceFilterChooser($Template, $NagiosPollerObject), $TemplateString);
  $TemplateString = str_replace("<!--[*TEMPLATE_CHOOSER*]-->", ReplaceTemplateChooser($Template), $TemplateString);
  $TemplateString = str_replace("<!--[*FEED_INFO*]-->", ProcessTemplate_FeedInfo($Template, $NagiosPollerObject), $TemplateString);
  $TemplateString = str_replace("[[SITE_NAME]]", $ClientSiteName, $TemplateString);
  $TemplateString = str_replace("[[NLG_VERSION]]", $NagiosPollerObject->Poller_Version, $TemplateString);
  $TemplateString = str_replace("[[NLG_URL]]", $_SERVER['SCRIPT_NAME'], $TemplateString);
  $TemplateString = str_replace("[+NUMBER_COMMENTS+]", count($NagiosPollerObject->Comments), $TemplateString);
  $TemplateString = str_replace("[[REFRESH_TIME]]", ($ClientRefreshTime * 1000), $TemplateString);

  // ARCi#6 - hide the pager if there's only group
  if (count($NagiosPollerObject->HostGroups) > 1)
  {
    $TemplateString = str_replace("<!--[*PAGER_CHOOSER*]-->", ReplaceServerPager($Template, $NagiosPollerObject), $TemplateString);
  } else {
    $TemplateString = str_replace("<!--[*PAGER_CHOOSER*]-->", "", $TemplateString);
  }

  // check current filter and apply filter[0] if none set
  if (strlen($NagiosPollerObject->FilterApplied) == 0)
  {
    $NagiosPollerObject->FilterApplied = $HostFilter[0]->DisplayText;
  }

  $TemplateString = str_replace("[[CURRENT_FILTER_NAME]]", $NagiosPollerObject->FilterApplied, $TemplateString);

  return $TemplateString;
}

function ProcessTemplate_Default($Template, $NagiosPollerObject)
{
  /*
   * ProcessTemplate_Default()
   * Process the "default.tpl" file
   */
   
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
  $ClientSiteName = variable_get('nlg_clientsitename', '');
  $ClientCompanyName = variable_get('nlg_clientcompanyname', '');
        $ClientCompanyLogo = variable_get('nlg_clientcompanylogo', '');

  $TemplateString = file_get_contents(drupal_get_path('module', 'nlg') ."/theme/default.tpl.php");

  return $TemplateString;
}

function ProcessTemplate_NetworkStatus($Template, $NagiosPollerObject)
{
  /*
   * ProcessTemplate_NetworkStatus()
   * Process the [[NETWORK_*]] directives
   */
  
  //require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
  $ClientHealth_ServerOKThreshold = variablet_get('nlg_clienthealth_serverokthreshold', '80');
  $ClientHealth_ServerWarnThreshold = variable_get('nlg_clienthealth_servewarnthreshold', '40');
  $ClientHealth_MetricOKThreshold = variable_get('nlg_clienthealth_metricokthreshold', '80');
  $ClientHealth_MetricWarnThreshold = variable_get('nlg_clienthealth_metricwarnthreshold', '40');
  $ClientHealth_NetworkOKThreshold = variable_get('nlg_clienthealth_networkokthreshold', '80');
  $ClientHealth_NetworkWarnThreshold = variable_get('nlg_clienthealth_networkwarnthreshold', '40');
  //require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_lang_" . variable_get('nlg_lang', 'en') . ".inc.php");
  $Language = variable_get('nlg_language', 'en');

  // as of 1.0.0 -
  // Instead of colouring the network health bars with a HTML colour, we set
  // a status label instead - rather than re-writing this bit of code, I've simply
  // over-ridden the old config variables
  // -- We still need the thresholds though to determine which status label to set

  $ClientHealth_OKColour = "OK";
  $ClientHealth_WarnColour = "W";
  $ClientHealth_BadColour = "C";

  $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/includes/templates/" . $Template . "/network_status.tpl");
  // ARCi#95 - when there are no comments, don't show the link
  if (count($NagiosPollerObject->Comments) == 0)
  {
    $TemplateString = str_replace("<!--[*NETWORK_COMMENTS*]-->", "", $TemplateString);
  } else {
    $TemplateString = str_replace("<!--[*NETWORK_COMMENTS*]-->", ProcessTemplate_Comments($Template, $NagiosPollerObject), $TemplateString);
  }

  // find out the 'not OK' servers and metrics...
  $HostsWithProblems = $NagiosPollerObject->Network_ServerDown + $NagiosPollerObject->Network_ServerNetworkError + $NagiosPollerObject->Network_ServerDegradedMetrics + $NagiosPollerObject->Network_ServerUnknown;
  $ServicesWithProblems = $NagiosPollerObject->Network_MetricWarn + $NagiosPollerObject->Network_MetricFail + $NagiosPollerObject->Network_MetricUnknown;

  $HostsOK = $NagiosPollerObject->Nagios_HostCount - $HostsWithProblems;
  $ServicesOK = $NagiosPollerObject->Nagios_ServiceCount - $ServicesWithProblems;

  // Work out the percentage of hosts/services that are UP/OK
  // (hosts/services Up/OK) / (Total hosts/services) * 100
  $HostsUpPercent = sprintf("%0.1f", ($HostsOK / $NagiosPollerObject->Nagios_HostCount) * 100);
  $ServicesOkPercent = sprintf("%0.1f", ($ServicesOK / $NagiosPollerObject->Nagios_ServiceCount) * 100);

  // Work out the overall network health
  $NetworkHealth = (($HostsUpPercent + $ServicesOkPercent) / 200) * 100;

  // find out which colour our server health threshold fits
  if ($HostsUpPercent >= $ClientHealth_ServerOKThreshold)
  {
    $HostHealthColour = $ClientHealth_OKColour;
  } elseif ($HostsUpPercent >= $ClientHealth_ServerWarnThreshold) {
    $HostHealthColour = $ClientHealth_WarnColour;
  } else {
    $HostHealthColour = $ClientHealth_BadColour;
  }

  // find out which colour our metric health threshold fits
  if ($ServicesOkPercent >= $ClientHealth_MetricOKThreshold)
  {
    $ServiceHealthColour = $ClientHealth_OKColour;
  } elseif ($ServicesOkPercent >= $ClientHealth_MetricWarnThreshold) {
    $ServiceHealthColour = $ClientHealth_WarnColour;
  } else {
    $ServiceHealthColour = $ClientHealth_BadColour;
  }

  // ARCi#93 - option to show custom text instead of "the network is running at xx% of healthy capacity"
  // find out which text to show for our network health threshold
  if ($NetworkHealth >= $ClientHealth_NetworkOKThreshold)
  {
    $NetworkHealthText = $Language['NETWORK_STATUS_OK'];
  } elseif ($NetworkHealth >= $ClientHealth_NetworkWarnThreshold) {
    $NetworkHealthText = $Language['NETWORK_STATUS_WARNING'];
  } else {
    $NetworkHealthText = $Language['NETWORK_STATUS_CRITICAL'];
  }

    // Minimum width for the health charts (min. 1%) under a certain percentage
    // http://project.networkmail.eu/task/show.html?projid=5&id=80

//##    settype($HostsUpPercent, "numeric");
///    settype($ServicesOkPercent, "numeric");

    if ($HostsUpPercent < 1)
    {
        $TemplateString = str_replace("[+NETWORK_SERVER_PERCENT_UP_WIDTH+]", 1, $TemplateString);
    }
    else
    {
        $TemplateString = str_replace("[+NETWORK_SERVER_PERCENT_UP_WIDTH+]", $HostsUpPercent, $TemplateString);
    }

    if ($ServicesOkPercent < 1)
    {
        $TemplateString = str_replace("[+NETWORK_METRIC_PERCENT_OK_WIDTH+]", 1, $TemplateString);
    }
    else
    {
        $TemplateString = str_replace("[+NETWORK_METRIC_PERCENT_OK_WIDTH+]", $ServicesOkPercent, $TemplateString);
    }

  // ...and replace the values in the template
  $TemplateString = str_replace("[[NETWORK_GLOBAL_HEALTH_TEXT]]", $NetworkHealthText, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_SERVER_TOTAL+]", $NagiosPollerObject->Nagios_HostCount, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_SERVER_UP+]", $NagiosPollerObject->Network_ServerOK, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_SERVER_DOWN+]", $NagiosPollerObject->Network_ServerDown, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_SERVER_NETERROR+]", $NagiosPollerObject->Network_ServerNetworkError, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_SERVER_DEGRADED+]", $NagiosPollerObject->Network_ServerDegradedMetrics, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_SERVER_UNKNOWN+]", $NagiosPollerObject->Network_ServerUnknown, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_METRIC_TOTAL+]", $NagiosPollerObject->Nagios_ServiceCount, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_METRIC_OK+]", $NagiosPollerObject->Network_MetricOK, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_METRIC_WARN+]", $NagiosPollerObject->Network_MetricWarn, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_METRIC_FAIL+]", $NagiosPollerObject->Network_MetricFail, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_METRIC_UNKNOWN+]", $NagiosPollerObject->Network_MetricUnknown, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_SERVER_BAD+]", $HostsWithProblems, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_METRIC_BAD+]", $ServicesWithProblems, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_SERVER_PERCENT_UP+]", $HostsUpPercent, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_METRIC_PERCENT_OK+]", $ServicesOkPercent, $TemplateString);
  $TemplateString = str_replace("[[NETWORK_SERVER_STATUS]]", $HostHealthColour, $TemplateString);
  $TemplateString = str_replace("[[NETWORK_METRIC_STATUS]]", $ServiceHealthColour, $TemplateString);
  $TemplateString = str_replace("[+NETWORK_GLOBAL_HEALTH+]", $NetworkHealth, $TemplateString);

  // Clean up
  unset($HostsOK);
  unset($ServicesOK);
  unset($HostsUpPercent);
  unset($ServicesOkPercent);
  unset($HostsWithProblems);
  unset($ServicesWithProblems);
  unset($HostHealthColour);
  unset($ServiceHealthColour);
  unset($NetworkHealth);
  unset($NetworkHealthText);

  return $TemplateString;
}

function ProcessTemplate_ServerSummary($Template, $NagiosPollerObject) {
  /*
   * ProcessTemplate_ServerSummary()
   * Process the [*x_SERVER_SUMMARY*] directives
   */

  // ARCi#109 - "More Servers" link was displayed when it shouldn't
  // first check if we have some hosts (eg. a filter may return 0 (zero) hosts)
        if ($NagiosPollerObject->Nagios_HostFilterCount == 0) {
          $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/includes/templates/" . $Template . "/server_summary_no_result.tpl");
        } 
        else {
    $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/includes/templates/" . $Template . "/server_summary.tpl");

    $HostTemplateMatchCount = preg_match("/\<!--\[\*S_SERVER_SUMMARY\*\]-->(.*)<!--\[\*E_SERVER_SUMMARY\*\]-->/Us", $TemplateString, $HostTemplateMatches);
    if ($HostTemplateMatchCount > 0)
    {
      if (count($HostTemplateMatches[0]) > 0)
      {
        $HostListTemplate = "";

        // Process each record for each host in the poller object
        foreach($NagiosPollerObject->Hosts as $NagiosHost)
        {
          // process this host template and add to our server list
          $HostListTemplate .= ReplaceSingleServerInfo($HostTemplateMatches[1], $NagiosHost);

          unset($NagiosHost);
        }
      }

      $TemplateString = preg_replace("/\<!--\[\*S_SERVER_SUMMARY\*\]-->(.*)<!--\[\*E_SERVER_SUMMARY\*\]-->/Us", $HostListTemplate, $TemplateString);
                        // This should not be here but the templating system needs to be refactored
                        // Get Ilearn status
                        $ilearn_status = drupal_http_request('http://ilearncentral.sfsu.edu/status');
                        $qp_ilearn_status_text = @qp($ilearn_status->data, 'div#ilearn-status-page h2', array('ignore_parser_warnings' => TRUE));
                        if($qp_ilearn_status_text->hasClass('up')) {
                          $qp_ilearn_status_class = 'OK';
                        }
                        elseif($qp_ilearn_status_text->hasClass('down')) {
                          $qp_ilearn_status_class = 'C';
                        }
                        $qp_ilearn_template_string = '<div class="statuscolumn status_' . $qp_ilearn_status_class .'"><p class="marginleft25 fontsize80">' . $qp_ilearn_status_text->text() . '</p></div>';
                        $TemplateString = str_replace("[[ILEARN_SERVER_SUMMARY]]", $qp_ilearn_template_string, $TemplateString);
                        // Get Microsoft Live @ Edu status
                        $live_status = drupal_http_request('https://status.eduadmin.live.com');
                        $qp_live_status_text = @qp($live_status->data, '#ctl00_contentPlaceholder_exchangeStatusPanel_serviceRepeater_ctl00_headerCell img', array('ignore_parser_warnings' => TRUE));

                        if($qp_live_status_text->hasClass('serviceUp_32_gif') || $qp_live_status_text->hasClass('serviceUp_16_gif')) {
                          $qp_live_status_class = 'OK';
                        }
                        elseif($qp_live_status_text->hasClass('serviceUpWithIssues_32_gif') || $qp_live_status_text->hasClass('serviceUpWithIssues_16_gif')) {
                          $qp_live_status_class = 'W';
                        }
                        elseif($qp_live_status_text->hasClass('serviceDown_32_gif') || $qp_live_status_text->hasClass('serviceDown_16_gif')) {
                          $qp_live_status_class = 'C';
                        }
                        $qp_live_template_string = '<div class="statuscolumn status_' . $qp_live_status_class .'"><p class="marginleft25 fontsize80">' . $qp_live_status_text->attr('alt') . '</p></div>';
                        $TemplateString = str_replace("[[LIVE_SERVER_SUMMARY]]", $qp_live_template_string, $TemplateString);

                        // End of stuff that should not be here
      unset($HostListTemplate);
    }
  }

  return $TemplateString;
}

function ProcessTemplate_ServerDetail($Template, $NagiosHostObject, $NagiosPollerObject)
{
  /*
   * ProcessTemplate_ServerDetail()
   * Process the [[SERVER_*]] directives
   */

  $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/includes/templates/" . $Template . "/server_detail.tpl");

  $ServiceTemplateMatchCount = preg_match("/\<!--\[\*S_METRIC_SUMMARY\*\]-->(.*)<!--\[\*E_METRIC_SUMMARY\*\]-->/Us", $TemplateString, $ServiceTemplateMatches);
  if ($ServiceTemplateMatchCount > 0) {
    if (count($ServiceTemplateMatches[0]) > 0) {
      $ServiceListTemplate = "";

      // Process each record for each host in the poller object
      foreach($NagiosHostObject->HostServices as $NagiosService)
      {
        // process this host template and add to our server list
        $ServiceListTemplate .= ReplaceSingleMetricInfo($ServiceTemplateMatches[1], $NagiosService);
        unset($NagiosService);
      }

      $TemplateString = preg_replace("/\<!--\[\*S_METRIC_SUMMARY\*\]-->(.*)<!--\[\*E_METRIC_SUMMARY\*\]-->/Us", $ServiceListTemplate, $TemplateString);
      unset($ServiceListTemplate);
    }
  }

  $TemplateString = ReplaceSingleServerInfo($TemplateString, $NagiosHostObject);

  return $TemplateString;
}

function ProcessTemplate_Comments($Template, $NagiosPollerObject)
{
  /*
   * ProcessTemplate_Comments()
   * Process the [*x_xxx_COMMENT_ITEM*] directives
   */

  $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/includes/templates/" . $Template . "/comment_detail.tpl");
  $CommentTemplate = "";

  // Get our host and service comment templates
  // Host comment template
  $CommentTemplateMatchCount = preg_match("/\<!--\[\*S_COMMENT_ITEM\*\]-->(.*)<!--\[\*E_COMMENT_ITEM\*\]-->/Us", $TemplateString, $CommentTemplateMatches);
  if ($CommentTemplateMatchCount > 0 && count($CommentTemplateMatches[0]) > 0)
  {
    $HostCommentTemplate = $CommentTemplateMatchCount[1];
  }
  unset($CommentTemplateMatchCount);

  // Service comment template
  $CommentTemplateMatchCount = preg_match("/\<!--\[\*S_COMMENT_ITEM\*\]-->(.*)<!--\[\*E_COMMENT_ITEM\*\]-->/Us", $TemplateString, $CommentTemplateMatches);
  if ($CommentTemplateMatchCount > 0 && count($CommentTemplateMatches[0]) > 0)
  {
    $MetricCommentTemplate = $CommentTemplateMatchCount[1];
  }
  unset($CommentTemplateMatchCount);

  // Now run through each comment item and replace the variables
  foreach($NagiosPollerObject->Comments as $NagiosComment)
  {
    switch($NagiosComment->Type)
    {
      case "hostcomment":
        // Process this as a host comment
        $CommentTemplate .= ReplaceSingleCommentInfo($CommentTemplateMatches[1], $NagiosComment, "host");
        break;
      case "servicecomment":
        // Process this as a service comment
        $CommentTemplate .= ReplaceSingleCommentInfo($CommentTemplateMatches[1], $NagiosComment, "service");
        break;
    }
  }

  //  Now replace the host and service definitions in the host
  $TemplateString = preg_replace("/\<!--\[\*S_COMMENT_ITEM\*\]-->(.*)<!--\[\*E_COMMENT_ITEM\*\]-->/Us", $CommentTemplate, $TemplateString);

  return $TemplateString;
}

function ProcessTemplate_UpdateNotify($Template)
{
  /*
   * ProcessTemplate_UpdateNotify()
   * Process the update notify directives
   */

  global $Update_DownloadLink;
  global $UpdateNewVersion;

  $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/includes/templates/" . $Template . "/update_available.tpl");

  $TemplateString = str_replace("[[UPDATE_NEW_VERSION]]", $UpdateNewVersion, $TemplateString);
  $TemplateString = str_replace("[[UPDATE_DL_LINK]]", $Update_DownloadLink, $TemplateString);

  return $TemplateString;
}

function ProcessTemplate_FeedInfo($Template, $NagiosPollerObject)
{
  /*
   * ProcessTemplate_FeedInfo()
   * Process the feed information
   */
  
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_lang_" . variable_get('nlg_lang', 'en') . ".inc.php");
  
  /*global $Language;
  global $ClientDateFormat;*/

  $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/theme/feed_info.tpl.php");

  // ARCi#88 - replacements dependent on whether the feed is cached or live
  // if feed is cached, ::$NLG_FeedSource = "cache", else ::$NLG_FeedSource = "live"
  if ($NagiosPollerObject->NLG_FeedSource == "live")
  {
    $TemplateString = str_replace("[[IS_FEED_CACHED]]", $Language['IS_FEED_CACHED_N'], $TemplateString);
  } else {
    $TemplateString = str_replace("[[IS_FEED_CACHED]]", $Language['IS_FEED_CACHED_Y'], $TemplateString);
  }

  $TemplateString = str_replace("[[FEED_CACHED_AT]]", FormatDate($NagiosPollerObject->Nagios_FeedUpdated, "H:i A l, F d, Y"), $TemplateString);

  return $TemplateString;
}

function ProcessTemplate_Error($ErrorMessage = "")
{
  /*
   * ProcessTemplate_Error()
   * Process the error template
   * Added in v1.0.0
   */
   
  print $ErrorMessage;
   
  $TemplateString = file_get_contents(drupal_get_path(module, 'nlg') ."/theme/nlg-systemerror-form.tpl.php");
  $TemplateString = str_replace("[[ERROR_TEXT]]", $ErrorMessage, $TemplateString);
  $TemplateString = str_replace("[[ERROR_TIME]]", date("d/M/Y H:i:s"), $TemplateString);

  return $TemplateString;
}

function RebuildJSLangFile()
{
  /*
   * RebuildJSLangFile()
   * Create the nlg_lang.js file with contents of $JSLanguage
   */
  
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
  require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_lang_" . variable_get('nlg_lang', 'en') . ".inc.php");
  //global $JSLanguage;

  $JSLanguageString = "var nlg_language = new Array(" . count($JSLanguage) . ");\n";

  while ($LanguageEntry = current($JSLanguage))
  {
    $JSLanguageString .= "nlg_language['" . strtolower(key($JSLanguage)) . "'] = '" . $LanguageEntry . "';\n";
    next($JSLanguage);
  }

  return file_put_contents(drupal_get_path(module, 'nlg') ."/includes/nlg_lang.js", $JSLanguageString);
}

function ReadFromCache(&$PollerObject, $FilterID, $GroupID)
{
  /*
   * ReadFromCache()
   * Check if a cached file exists and restore it if necessary
   * If the cached object is older than $ClientCacheTTL, re-create it
   */

  global $ClientCacheTTL;
  global $ClientCacheDirectory;
  global $NLG_LineEnding;
  global $CurrentCacheTime;
  // If CacheDirectory does not terminate with the character specified by $NLG_LineEnding
  // then explicitly add it
  if (
    substr(
      $ClientCacheDirectory,
      strlen($ClientCacheDirectory) - strlen($NLG_LineEnding),
      strlen($NLG_LineEnding)
    ) != $NLG_LineEnding
  )
  {
    $ClientCacheDirectory .= $NLG_LineEnding;
  }

  // RT#366 - Cache locking, to prevent clustered servers reading/writing data at the same time

  // Note for "Padlock" release - lock on a per-cache file basis (e.g. 1 lock
  // file per group/filter combination)

  // Set a time-limit, and don't cache if this limit is exceeded (in micro-seconds)
  /* $TimeLimit = 2000;
  $TimeElapsed = 0;
  $LockPollInterval = 10;

  while (
    $TimeElapsed < $TimeLimit &&
    file_exists($ClientCacheDirectory . "nlg_cache.lock")
  ) {
    usleep($LockPollInterval);
    $TimeElapsed += $LockPollInterval;
  }

  // If we exited the loop because the time-limit exceeded, then don't cache
  if ($TimeElapsed == $TimeLimit)
  {
    return FALSE;
  }
  */
  $CurrentCacheFile = "%1\$snlg_f%2\$d_g%3\$d%4\$s.cacheobj";
  $CurrentCacheTimestamp = "%1\$snlg_f%2\$d_g%3\$d%4\$s.cachetime";
  $CurrentCacheFile = sprintf($CurrentCacheFile, $ClientCacheDirectory, $FilterID, $GroupID, $CurrentCacheTime);
  $CurrentCacheTimestamp = sprintf($CurrentCacheTimestamp, $ClientCacheDirectory, $FilterID, $GroupID, $CurrentCacheTime);
  // check if we have a cached record - both object and timestamp
  // and read them both - if either fail, return FALSE to force a new poll
  if (file_exists($CurrentCacheTimestamp))
  {
    $ContentCachedAt = file_get_contents($CurrentCacheTimestamp);
    if ($ContentCachedAt === FALSE)
    {
      return FALSE;
    }
  } else {
    return FALSE;
  }

  if (file_exists($CurrentCacheFile))
  {
    $CachedContent = file_get_contents($CurrentCacheFile);;
    if ($CachedContent === FALSE)
    {
      return FALSE;
    }
  } else {
    return FALSE;
  }

  // Now check if our cache is older than our configured TTL
  $CurrentTimestamp = time();
  if (($ContentCachedAt + $ClientCacheTTL) < $CurrentTimestamp)
  {
    return FALSE;
  } else {
    // provide our $PollerObject as the cached object content
    $PollerObject = unserialize($CachedContent);
    return true;
  }
}

function WriteCacheTimestamp($FilterID, $GroupID, $CurrentCacheTime)
{
  /*
     * WriteCacheTimestamp()
     * Write cache timestamp information to disk
   */

  global $CurrentCacheTime;
  global $ClientCacheDirectory;
  global $NLG_LineEnding;

  // If CacheDirectory does not terminate with the character specified by $NLG_LineEnding
  // then explicitly add it
  if (
    substr(
      $ClientCacheDirectory,
      strlen($ClientCacheDirectory) - strlen($NLG_LineEnding),
      strlen($NLG_LineEnding)
    ) != $NLG_LineEnding
  )
  {
    $ClientCacheDirectory .= $NLG_LineEnding;
  }

  $NewCacheTimestamp = sprintf("%1\$snlg_f%2\$d_g%3\$d%4\$s.cachetime", $ClientCacheDirectory, $FilterID, $GroupID, $CurrentCacheTime);
  @file_put_contents($NewCacheTimestamp, time());

  unset($NewCacheTimestamp);
}

function WriteToCache(&$PollerObject, $FilterID, $GroupID, $CurrentCacheTime)
{
  /*
   * WriteToCache()
   * Store the given $PollerObject as a cached object - check if we need to disable caching
   * If any part of the writes fail, it just means this record wont be cached
   */

  global $CurrentCacheTime;
  global $ClientCacheDirectory;
  global $ClientCacheTTL;
  global $NLG_LineEnding;

  // If CacheDirectory does not terminate with the character specified by $NLG_LineEnding
  // then explicitly add it
  if (
    substr(
      $ClientCacheDirectory,
      strlen($ClientCacheDirectory) - strlen($NLG_LineEnding),
      strlen($NLG_LineEnding)
    ) != $NLG_LineEnding
  )
  {
    $ClientCacheDirectory .= $NLG_LineEnding;
  }

  // RT#366 - Cache locking, to prevent clustered servers writing data at the same time

  // Note for "Padlock" release - lock on a per-cache file basis (e.g. 1 lock
  // file per group/filter combination)

  // Set a time-limit, and don't cache if this limit is exceeded (in micro-seconds)
  $TimeLimit = 20000;
  $TimeElapsed = 0;
  $LockPollInterval = 10;
  while (
    $TimeElapsed < $TimeLimit &&
    file_exists($ClientCacheDirectory . "nlg_cache.lock")
  ) {
    usleep($LockPollInterval);
    $TimeElapsed += $LockPollInterval;
  }

  // If we exited the loop because the time-limit exceeded, then don't cache...
  if ($TimeElapsed == $TimeLimit) {
          return FALSE;
  }

  // ...otherwise, create the lock file
  touch($ClientCacheDirectory . "nlg_cache.lock");
  $NewCacheFile = sprintf("%1\$snlg_f%2\$d_g%3\$d%4\$s.cacheobj", $ClientCacheDirectory, $FilterID, $GroupID, $CurrentCacheTime);

  @file_put_contents($NewCacheFile, serialize($PollerObject));

  unset($NewCacheFile);

  // Remove lock file
  unlink($ClientCacheDirectory . "nlg_cache.lock");

  return true;
}

function get_output_template() {

require(drupal_get_path(module, 'nlg') ."/includes/s3_config_stub.inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_config.inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_lang_" . variable_get('nlg_lang', 'en') . ".inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_filter.inc.php");
require(drupal_get_path(module, 'nlg') ."/includes/sync-files/s3_filter_auth.inc.php");

$PollerObject = new S3_NagiosPoller();
$CurrentTemplate = $ClientDefaultTemplate;

// ARCi#1 - data caching

// RT#367 - check cache directory exists and is writeable, disable caching if it isn't
if ($ClientEnableCaching == 1) {
  if (defined("DEBUG_REQUIRED")) {
    echo "Checking cache directory ... ";
  }
  if (!file_exists($ClientCacheDirectory) || !is_writeable($ClientCacheDirectory)) {
    if (defined("DEBUG_REQUIRED")) {
      echo "failed, disabling caching.\n";
    }
    $ClientEnableCaching = 0;
  } 
  else {
    if (defined("DEBUG_REQUIRED")) {
      echo "OK\n";
    }
  }
}

// RT#143 - check if we need to disable caching, or use our cached content
// instead of connecting to the live feed
if ($ClientEnableCaching == 0) {
  if (defined("DEBUG_REQUIRED")) {
    echo "Caching is disabled, need to fetch live data.\n\n";
  };
  if (!PingPoller($PollerObject)) { //, $FilterID, $GroupID)) {
    if (!defined("DEBUG_REQUIRED")) {
      echo ProcessTemplate_Error($PollerObject->LastPollerError);
      exit();
    }
  }
} 
else {
  if (defined("DEBUG_REQUIRED")) {
    echo "Checking to see if we have a cache record ... ";
  };
  $CachedContentUsed = ReadFromCache($PollerObject, $FilterID, $GroupID);
  if (!$CachedContentUsed) {
    if (defined("DEBUG_REQUIRED")) {
      echo "no, need to contact the server\n\n";
    };
    if (defined("DEBUG_REQUIRED")) {
      echo "*** S3 Client -> PingPoller() running ***\n\n";
    };
    if (!PingPoller($PollerObject)) { //, $FilterID, $GroupID)) {
      if (!defined("DEBUG_REQUIRED")) {
        echo 'Service metric data is updating and will be available again shortly.'; //ProcessTemplate_Error($PollerObject->LastPollerError);
      }
    } 
    else {
      // ARCi#1 - cache poller results
      if (WriteToCache($PollerObject, $FilterID, $GroupID, $CurrentCacheTime) !== FALSE) {
        WriteCacheTimestamp($FilterID, $GroupID);
      }
    }
    if (defined("DEBUG_REQUIRED")) {
      echo "*** S3 Client -> PingPoller() finished ***\n\n";
    }
  } 
  else {
    if (defined("DEBUG_REQUIRED")) {
      echo "yes, using cached data\n\n";
    }
    // ARCi#88 - set the feed source to cache...
    // RT#245 - ... but only if we've been cached more than 3 times (the number
    // of times this script is initially called during the template creation)
    if ($PollerObject->NLG_FeedSource == "live" && $PollerObject->NLG_CacheCount == 3) {
      $PollerObject->NLG_FeedSource = "cache";
      WriteToCache($PollerObject, $FilterID, $GroupID, $CurrentCacheTime);
    }
    else {
      $PollerObject->NLG_CacheCount++;
      WriteToCache($PollerObject, $FilterID, $GroupID, $CurrentCacheTime);
    }
  }
}
if (defined("DEBUG_REQUIRED")) {
  echo "-- OBJECT SUMMARY USING CURRENT DATA SET --\n\n";
  echo "Servers (hosts):    " . $PollerObject->Nagios_HostCount . "\n";
  echo "Metrics (services): " . $PollerObject->Nagios_ServiceCount . "\n\n";
  echo "+---------------------------------------------------------------------------+\n";
  echo "| This is as far as we're going to get in debug mode - the rest is parsing  |\n";
  echo "| the data received into the templates.                                     |\n";
  echo "|                                                                           |\n";
  echo "| If you're reporting a problem, the above output will be crucial in trying |\n";
  echo "| to diagnose problems - so please include it where possible!               |\n";
  echo "+---------------------------------------------------------------------------+\n\n";

  exit();
}

// RT#245 - move the check for the GET['view'] variable further up, so that we
// don't change the "Feed source" if we're only showing the feed status
return $PollerObject;

// The Below is all turned off in an attempt to remove templating out of this file and into the theming files
/*
switch($ViewRequired)
{
  case "network_status":
    $OutputTemplate = ProcessTemplate_NetworkStatus($CurrentTemplate, $PollerObject);
    break;
  case "server_summary":
    $OutputTemplate = ProcessTemplate_ServerSummary($CurrentTemplate, $PollerObject);
    break;
  case "feed_info":
    $OutputTemplate = ProcessTemplate_FeedInfo($CurrentTemplate, $PollerObject);
    break;
  case "server_detail":
    if (!isset($_GET['sid'])) {
      echo ProcessTemplate_Error($Language['POLLING_NO_SID']);
      exit();
    } else {

      // v1.0.0 - sanitise the GET variable - sid
      $ServerID = urldecode(htmlspecialchars($_GET['sid']));

      // Try and find the host specified in our polling object
      $HostFound = FALSE;
      $HostObject = new S3_NagiosHost();

      // run through the list of hosts and set $HostFound to true if found
      foreach ($PollerObject->Hosts as $NagiosHost)
      {
        if ($NagiosHost->HostID == $ServerID)
        {
          $HostFound = true;
          $HostObject = $NagiosHost;
          unset($NagiosHost);
        }
      }

      // If our host wasn't found, output error message
      // If it was, create the template
      if ($HostFound === FALSE)
      {
        $OutputTemplate = $Language['POLLING_NO_SID'];
      } else {
        $OutputTemplate = ProcessTemplate_ServerDetail($CurrentTemplate, $HostObject, $PollerObject);
      }
    }
    break;
  default:
    $OutputTemplate = ProcessTemplate_Default($CurrentTemplate, $PollerObject);
}

// Run template through global parameters
$OutputTemplate = ReplaceGlobalParameters($OutputTemplate, $CurrentTemplate, $PollerObject);

// Show to browser
return $OutputTemplate;
*/
}
?>
