<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_download_stub.inc.php                                       |
   | Description:      Downloads configuration files from server-side                 |
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

function DownloadFiles()
{
  require(drupal_get_path('module', 'nlg') . "/client/s3_config_stub.inc.php");
  global $Stub_HowToDownload;
  global $Stub_DownloadSource;
  global $Stub_SharedFiles;
  global $Stub_HTTPAuthEnabled;
  global $Stub_HTTPAuthUsername;
  global $Stub_HTTPAuthPassword;

  if (defined("DEBUG_REQUIRED")) {echo "*** Download Stub -> DownloadFiles() running ***\n\n";};

  // Check if we need to use HTTP authentication
  if ($Stub_HTTPAuthEnabled == 1 && $Stub_HowToDownload == "http")
  {
    $Stub_DownloadSource = preg_replace("/^http([s]*):\/\/(.+)$/", "http$1://" . $Stub_HTTPAuthUsername . ":" . $Stub_HTTPAuthPassword . "@$2", $Stub_DownloadSource);
  }

  if (defined("DEBUG_REQUIRED")) {echo "Attempting to download files from " . preg_replace("/^http([s]*):\/\/(.+):(.+)@(.+)$/", "http$1://xxx:xxx@$4", $Stub_DownloadSource) . "\n\n";};

  foreach($Stub_SharedFiles as $DownloadFile)
  {
    $CurrentDownloadSource = $Stub_DownloadSource;

    // finalise how/where to download update from
    switch($Stub_HowToDownload)
    {
      case "http":
        $CurrentDownloadSource .= "?filename=" . urlencode($DownloadFile) . "&action=";
        $FileCheckURL = $CurrentDownloadSource . "check";
        $FileUpdateURL = $CurrentDownloadSource . "update";
        break;
      case "file":
        $CurrentDownloadSource .= $DownloadFile;
        $FileCheckURL = $CurrentDownloadSource;
        $FileUpdateURL = $CurrentDownloadSource;
        break;
      default:
        return false;
    }

    // if we already have a local copy, hash it and compare the hash
    if (file_exists(drupal_get_path('module', 'nlg') . "/client/sync-files/" . $DownloadFile))
    {
      if (defined("DEBUG_REQUIRED")) {echo "Synchronising " . $DownloadFile . " with server ... ";};

      // ARCi#85 - bug fix in 1.0.0 - we were MD5 hashing an MD5 hash!
      $CurrentMD5Hash = md5_file(drupal_get_path('module', 'nlg') . "/client/sync-files/" . $DownloadFile);

      // RT#234 - file_get_contents sends back an MD5 hash on a http
      // request but not on a local file
      if ($Stub_HowToDownload == "http")
      {
        $ServerMD5Hash = file_get_contents($FileCheckURL);
      } else {
        $ServerMD5Hash = md5_file($FileCheckURL);
      }

      // added in 1.0.0 - check the download succeeded
      if ($ServerMD5Hash === false)
      {
        if (defined("DEBUG_REQUIRED")) {echo " failed sync'ing with server\n";};
      } else {
        // check for mismatched MD5 and update if different
        if ($CurrentMD5Hash != $ServerMD5Hash)
        {
          if (defined("DEBUG_REQUIRED")) {echo "out-of-date ... updating ... ";};

          // download the files and check how many bytes we've written
          $BytesWritten = 0;

          // RT#234 - we don't need to base64_decode a local file or
          // it doesn't get written very well :S

          if ($Stub_HowToDownload == "http")
          {
            $BytesWritten = file_put_contents($_SERVER['DOCUMENT_ROOT'] . base_path() . drupal_get_path('module', 'nlg') . "/client/sync-files/" . $DownloadFile, base64_decode(file_get_contents($FileUpdateURL)));
          } else {
            $BytesWritten = file_put_contents(drupal_get_path('module', 'nlg') . "/client/sync-files/" . $DownloadFile, file_get_contents($FileUpdateURL));
          }

          if (defined("DEBUG_REQUIRED")) {echo $BytesWritten . " bytes written\n";};
        } else {
          if (defined("DEBUG_REQUIRED")) {echo "up-to-date\n"; };
        }
      }
    } else {
      if (defined("DEBUG_REQUIRED")) {echo "Downloading " . $DownloadFile . " from server ... ";};

      // download the file as we don't currently have it
      $BytesWritten = 0;

      // RT#234 - we don't need to base64_decode a local file or
      // it doesn't get written very well :S

      if ($Stub_HowToDownload == "http")
      {
        $BytesWritten = file_put_contents(drupal_get_path('module', 'nlg') . "/client/sync-files/" . $DownloadFile, base64_decode(file_get_contents($FileUpdateURL)));
      } else {
        $BytesWritten = file_put_contents(drupal_get_path('module', 'nlg') . "/client/sync-files/" . $DownloadFile, file_get_contents($FileUpdateURL));
      }

      if ($BytesWritten === false)
      {
        $BytesWritten = 0;
      }

      if (defined("DEBUG_REQUIRED")) {echo $BytesWritten . " bytes written\n";};
    }

    unset($CurrentDownloadSource);
    unset($FileCheckURL);
    unset($FileUpdateURL);
    unset($DownloadFile);
  }

  if (defined("DEBUG_REQUIRED")) {echo "\n*** Download Stub -> DownloadFiles() finished ***\n\n";};
  return true;
}

?>
