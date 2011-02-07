<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_download.php                                                |
   | Description:      Generates an MD5 hash of files and sends them to the client    |
   |                   in base64-encoded format                                       |
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

require("sync-files/s3_config.inc.php");

header("Content-type: text/plain");

if (isset($_GET['filename']))
{
  $FileToDownload = basename(urldecode($_GET['filename']));
  switch($_GET['action'])
  {
    case "check":
      $MD5_Hash = md5_file("sync-files/" . $FileToDownload);
      if ($MD5_Hash === false)
      {
        echo "***Could not hash " . $FileToDownload . "***";
      } else {
        echo $MD5_Hash;
      }
      unset($MD5_Hash);
      break;

    case "update":
      $FileContent = file_get_contents("sync-files/" . $FileToDownload);
      if ($FileContent === false)
      {
        echo "***Could not read file " . $FileToDownload . "***";
      } else {
        echo base64_encode($FileContent);
      }
      unset($FileContent);
      break;

    default:
      echo "***No action given***";
  }

  unset($FileToDownload);

} else {
  echo "***No filename given***";
}

?>