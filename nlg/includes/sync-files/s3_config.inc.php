<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_config.inc.php                                              |
   | Description:      Server and client configuration file for Network Looking Glass |
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

// *** CLIENT-SIDE CONFIGURATION *** //

  // Site name (add [[NLG_VERSION]] if you want to insert NLG's version, and [[NAGIOS_VERSION]] if you want Nagios's version)
  // Note: NLG_VERSION and NAGIOS_VERSION are the only 2 template variables that can be expanded in this string.

        // Company name
        $ClientCompanyName = variable_get('nlg_clientcompanyname', '');

        // Admin e-mail address
        $ClientAdminEmailAddress = variable_get('nlg_clientadminemail', 'systems@sfsu.edu');

        // HTTP path to the logo - can be absolute or relative to index.php
        $ClientCompanyLogo = variable_get('nlg_clientcompanylogo', 'templates/default/images/logo.gif');

  // the template to use
  //   to add your own template copy the 'default' folder and change the source code
  //   then change $ClientDefaultTemplate to be the name of the new folder
        $ClientDefaultTemplate = variable_get('nlg_clientdefaulttemplate', 'default');

        // The format to output date/times in (client-side)
        // [Format string to be passed to date() function]
        $ClientDateFormat = variable_get('nlg_clientdateformat', 'd/m/Y H:i:s');

        // ***** Caching Options ***** //

        // Enable caching on the client-side?  Will improve performance on medium-large installations.
        // (Recommended)
        $ClientEnableCaching = variable_get('nlg_clientenablecaching', '1');

        // Directory to write cache files to.  If using NLG on a hosting cluster with shared storage,
        // it's recommended to set this directory to a non-shared directory (e.g. /tmp)
        // If NLG cannot find this directory, caching will be silently disabled.
        // This can be absolute (e.g. "/tmp", or relative to the client folder - e.g. "cache")
        $ClientCacheDirectory = variable_get('nlg_clientcachedirectory', 'cache');

        // How long should cached data be saved for (in seconds) ?
        $ClientCacheTTL = variable_get('nlg_clientcachettl', '60');

  // ***** End Caching Options ***** //

  // Trim server names nicely - this is the length to trim to
  // Set to 0 to disable
  $ClientTrimServerNames = variable_get('nlg_clienttrimservernames', '0');

  // Trim metric names nicely - this is the length to trim to
  // Set to 0 to disable
  $ClientTrimMetricNames = variable_get('nlg_clienttrimetricnames', '0');

  // How often to refresh the 'Network Browser' in the UI (and 'Network Status' page)
  // [In seconds]

  // If this value is too low, you will get sporadic "Too many concurrent XML requests"
  // errors in the UI
  $ClientRefreshTime = variable_get('nlg_clientrefreshtime', '60');

  // ***** Pager Options ***** //

  // Group size - how many servers to have per page group
        $ClientPager_GroupSize = variable_get('nlg_clientpager_groupsize', '10');

  // Next/previous group size - how many groups either side of our current group to show?
  $ClientPager_RangeSize = variable_get('nlg_clientpager_rangesize', '1');

  // ***** End Pager Options ***** //

  // ***** Network Health Meter Options ***** //

  // The threshold at which server health is defined as being 'OK'
  $ClientHealth_ServerOKThreshold = 80;

  // The threshold at which server health is defined as being 'Warning'
  // Anything below this threshold is classed as 'critical'.
  $ClientHealth_ServerWarnThreshold = 40;

  // The threshold at which metric health is defined as being 'OK'
  $ClientHealth_MetricOKThreshold = 80;

  // The threshold at which metric health is defined as being 'Warning'
  // Anything below this threshold is classed as 'critical'.
  $ClientHealth_MetricWarnThreshold = 40;

  // The threshold at which the network is defined as being 'OK'
  // Normally this will be 100, so that only when everything is OK, is the
  // network classed as being fully working.
  $ClientHealth_NetworkOKThreshold = 100;

  // The threshold at which the network is defined as being 'Warning'
  // Anything below this threshold is classed as 'critical'.
  $ClientHealth_NetworkWarnThreshold = 50;

        // Behaviour for the network health meter
        // false = Meter shows status of all hosts regardless of filter chosen
        // true = Meter only shows status of hosts in the current filter (default)
        $ClientHealth_NewMeterBehaviour = true;

  // ***** End Network Health Meter Options ***** //

  // Restrict access to the client interface using an 'allow'/'deny' list?
  $ClientACL_Enabled = 0;

  // Which order to apply the restrictions on IP?
  //    AD = allow first, then deny
  //    DA = deny first, then allow
  $ClientACL_ApplyOrder = "DA";

  // IP Access Control Lists
  $ClientACL_AllowList = Array('127.0.0.1');
  $ClientACL_BlockList = Array();

        // *** SERVER-SIDE CONFIGURATION *** //

  // The URL to download the server feed from (client-side)
  $ServerFeed_URL = variable_get('nlg_serverfeedurl','http://localhost/server/s3_poller.php');

  // Does the poller ($ServerFeed) need HTTP authentication? (0 = no; 1 = yes, 2 = auto)
        // Auto means use the login username and password of the current session (required
        // for filter authentication)
  $ServerFeed_AuthEnabled = 0;

  // If HTTP authentication is required, set username and password here:
  $ServerFeed_AuthUsername = "";
  $ServerFeed_AuthPassword = "";

  // The local path to Nagios's "var" directory
  // MUST include the trailing slash (/ on Unix/Linux, \\ on Windows) - NLG will NOT add it for you
  $ServerNagiosFeed = "/var/cache/nagios3/";

  // Some systems and 3rd party tools, such as Groundwork and Monarch,
  // generate different filenames than Nagios' default - so please change
  // as appropriate
  //
  // comments  = the comments data file (default: comments.dat for Nagios 2, status.dat for Nagios 3)
  // downtime  = the downtime data file (default: downtime.dat for Nagios 2, status.dat for Nagios 3)
  // obj_cache  = cache of object definitions (default: objects.cache)
  // status  = the status data file (default: status.dat)
  //
  // If you're using Nagios 3, set comments and downtime to "status.dat", as the majority of status
  // information in Nagios 3 is now held in status.dat instead of separate files.
  $ServerNagiosFiles = Array(
    "comments" => "status.dat",
    "downtime" => "status.dat",
    "obj_cache" => "objects.cache",
    "status" => "status.dat"
  );

  // The hostname Nagios runs on
  // [Leave blank to use the system default from the gethostname() call]
  $ServerNagiosHost = "";

  // Restrict access to the polling server using an 'allow'/'deny' list?
  $ServerACL_Enabled = 1;

  // Which order to apply the restrictions on IP?
  //    AD = allow first, then deny
  //    DA = deny first, then allow
  $ServerACL_ApplyOrder = "DA";

  // IP Access Control Lists
  $ServerACL_AllowList = Array('127.0.0.1');
  $ServerACL_BlockList = Array();

        // *** MISC CONFIGURATION *** //

  // You may or may not need to change anything here

  // NLG's language
  $NLG_Language = "en";

  // Set this to "/" on Linux/Unix or "\\" on Windows
  $NLG_LineEnding = "/";

  // By default, you're only notified of updates if you go to
  // www.your_nlg_server.com/s3_client.php?checkupdate=yes
  // Enabling this will always alert you to updates
  $Update_AlwaysCheck = 0;

  // The download link on the update notification - only change this if you
  // have a locally-hosted copy of the download files
  $Update_DownloadLink = "";

  // The DNS record to query for the current version
  // Ordinarily you won't need to change this
  $Update_DNSRecordToCheck = "";

  // If the times in NLG appear incorrect (or, more accurately, if the times appear in UTC and not
  // your native timezone) set your timezone here according to one of the zones listed in VALID-TIMEZONES.txt
  // Leave this blank if the times are correct.
  // Note: if you're running NLG on CentOS, you will probably need to set this.
  $SystemTimezone = "";

  // If you want to show PHP errors, set "display_errors" to "on" and optionally the
  // error_reporting level to E_ALL
  ini_set("error_reporting", E_ALL);
?>
