<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_config_stub.inc.php                                         |
   | Description:      Configuration for downloading shared files from server-side    |
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

// *** CLIENT-SIDE CONFIGURATION STUB *** //

  // How to download the shared files
  //   http = download from the server's s3_download.php script
  //   file = read files from the local filesystem
  $Stub_HowToDownload = "http";

  // Does the download URL require authentication? (0 = no, 1 = yes)
  // Only takes effect when $Stub_HowToDownload is "http"
  $Stub_HTTPAuthEnabled = 0;

  // The username to authenticate as
  // Only takes effect when $Stub_HowToDownload is "http"
  $Stub_HTTPAuthUsername = "";

  // The password to authenticate with
  // Only takes effect when $Stub_HowToDownload is "http"
  $Stub_HTTPAuthPassword = "";

  // Where to download the shared files from
  //   If $Stub_HowToDownload is http, $DownloadSource needs to be a full URL to s3_download.php
  //   If $Stub_HowToDownload is file, this is the _directory_ where shared files can be found
  //     (with trailing slash)
  $Stub_DownloadSource = variable_get('nlg_downloadsource', 'http://10.10.10.159/server/s3_download.php');

  // Files to download
  //   Which files need to be downloaded?
  $Stub_SharedFiles = Array(
    "s3_class.inc.php",
    "s3_config.inc.php",
    "s3_filter.inc.php",
                "s3_filter_auth.inc.php",
    "s3_lang_en.inc.php",
    "s3_functions.inc.php"
  );

  // Allow user to specify "debug=yes" in the URL to show debug info
  // *** SECURITY RISK IF LEFT ENABLED ***
  $Stub_ClientEnableDebugging = 0;

  // If you want to show PHP errors, set "display_errors" to "on" and optionally the
  // error_reporting level to E_ALL
  ini_set("error_reporting", E_ALL);
  ini_set("display_errors", "on");

  // set cache time
  $CurrentCacheTime = date("YmdHm");
?>
