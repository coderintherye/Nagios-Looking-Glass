<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_filter.inc.php                                              |
   | Description:      Filter specification for which hosts to show in NLG front-end  |
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

$HostFilter = Array();

$HostFilter[0] = new S3_NetworkFilter();
$HostFilter[0]->Create("Public Servers");
$HostFilter[0]->SetType("host");
$HostFilter[0]->AddHost("Network");
$HostFilter[0]->AddHost("Web Services");
$HostFilter[0]->AddHost("Mail Services");
//$HostFilter[0]->AddHost("Live@Edu");

//$HostFilter[1] = new S3_NetworkFilter();
//$HostFilter[1]->Create("DNS");
//$HostFilter[1]->SetType("host");
//$HostFilter[1]->AddHost("thesun");
//$HostFilter[1]->AddHost("mercury");

//$HostFilter[2] = new S3_NetworkFilter();
//$HostFilter[2]->Create("Down only");
//$HostFilter[2]->SetType("status");
//$HostFilter[2]->AddStatus(1);

//$HostFilter[3] = new S3_NetworkFilter();
//$HostFilter[3]->Create("Network error only");
//$HostFilter[3]->SetType("status");
//$HostFilter[3]->AddStatus(2);

//$HostFilter[4] = new S3_NetworkFilter();
//$HostFilter[4]->Create("Degraded metrics only");
//$HostFilter[4]->SetType("status");
//$HostFilter[4]->AddStatus(3);

//$HostFilter[5] = new S3_NetworkFilter();
//$HostFilter[5]->Create("Hosts with problems");
//$HostFilter[5]->SetType("status");
//$HostFilter[5]->AddStatus(1);
//$HostFilter[5]->AddStatus(2);
//$HostFilter[5]->AddStatus(3);


?>
