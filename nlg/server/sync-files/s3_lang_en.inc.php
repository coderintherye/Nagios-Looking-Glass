<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_lang_en.inc.php                                             |
   | Description:      English language file for Network Looking Glass                |
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

$Language = Array(
  "ACL_DENIED" => "The ACL configuration of the server does not allow you access",
  "CONFIG_NAGIOSFEED_INVALID" => "The line ending on your \$ServerNagiosFeed does not match \$NLG_LineEnding",
  "CONFIG_TIMEZONE_INVALID" => "The timezone specified in the configuration file is invalid.  See the VALID-TIMEZONES text file for a list of timezones NLG supports.",
  "CANNOT_READ_NAGIOS_FILES" => "The poller could not read all of Nagios' status files - Nagios might not be running",
  "CANNOT_REBUILD_INDEXES" => "The poller could not re-build the indexes",
  "GENERIC_PROCESS_ERROR" => "An error occurred gathering the status information from Nagios; further information may be shown below",
  "PROCESS_STATUS_FILE_READ_FAILED" => "Could not stream contents from the Nagios status file",
  "PROCESS_COMMENTS_FILE_READ_FAILED" => "Could not stream contents from the Nagios comments file",
  "POLLING_SERVER_DOWN" => "Could not connect to the polling feed; the polling server may be down",
  "POLLING_CHECKSUM_DIFF" => "The checksum I received with the feed is invalid compared to my local check.  Network problems may be causing packet loss.<br /><br /><b>Hint:</b> Try increasing PHP's 'memory_limit' and/or 'max_execution_time' settings in php.ini on the polling server, and restarting the web service.",
  "POLLING_SERVER_RETURN_FAIL" => "The polling server feed returned a 'failure' code; the polling server said: %1\$s",
  "POLLING_CORRUPT_FEED" => "The feed received from the polling server appears to be corrupted",
  "POLLING_NO_SID" => "No server was specified, or the server could not be found",
  "NO_DEFAULT_TEMPLATE" => "The 'default' template is not present or invalid, cannot continue",
  "JS_REBUILD_FAILED" => "Could not rebuild the JavaScript language file",
  "INVALID_FILTER" => "The filter with ID #%1\$d does not exist",
  "INVALID_GROUP" => "The host group with ID #%1\$d does not exist",
  "IS_FEED_CACHED_Y" => "Using data retrieved from the cache (this data was retrieved at [[FEED_CACHED_AT]])",
  "IS_FEED_CACHED_N" => "This data came from the live Nagios system (this data was fetched at [[FEED_CACHED_AT]])",
  "NETWORK_STATUS_OK" => "The services ([+NETWORK_METRIC_TOTAL+] metrics) are stable and running.",
  "NETWORK_STATUS_WARNING" => "Some of our servers are having problems - this may or may not be affecting services running on the network.",
  "NETWORK_STATUS_CRITICAL" => "The network is experiencing severe outages which is having a negative effect on services.  We apologise for the inconvenience, and are working hard to restore the network to normal service."
);

$JSLanguage = Array(
  "LOADING_SERVER_BROWSER" => "Loading server browser",
  "LOADING_NETWORK_STATUS" => "Processing current network status",
  "LOADING_FEED_INFO" => "Collecting data about the feed",
  "REFRESH_SERVER_DETAILS" => "Refreshing the server details screen",
  "SWITCH_SERVER_GROUP" => "Changing the server group",
  "REFRESH_PAGE_CONTENT" => "Refreshing the current page content",
  "APPLY_SERVER_FILTER" => "Applying the server filter",
  "SWITCH_PAGE_TEMPLATE" => "User requested change of template, switching",
  "VIEWING_SERVER_DETAILS" => "Viewing Extended Server Details",
  "VIEWING_NETWORK_STATUS" => "Overall Network Status",
  "METRICS_EXPAND" => 'Show Metrics',
  "METRICS_COLLAPSE" => 'Hide Metrics',
  "UPDATE_EXPAND" => 'more details',
  "UPDATE_COLLAPSE" => 'hide details',
  "COMMENT_EXPAND" => 'show',
  "COMMENT_COLLAPSE" => 'hide'
);

$HostStatus = Array("U", "D", "NE", "DM", "UN");
$HostStatusText = Array("Up", "Down", "Network Error", "Degraded Metrics", "Unknown");
$ServiceStatus = Array("OK", "W", "C", "UN");
$ServiceStatusText = Array("OK", "Warning", "Critical", "Unknown");

?>
