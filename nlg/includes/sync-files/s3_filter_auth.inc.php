<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_filter_auth.inc.php                                         |
   | Description:      Provides different filters to different users                  |
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

// Leave this line intact!
$FilterAuth = Array();

// By default if no "default" filter auth is set, NLG will use provide all
// filters available.  Override this by uncommenting the 'default' below.
// e.g. the below says that all users can only see filter #0
//$FilterAuth['default'] = Array(0);

// Provide one of these lines for each user you want to have customised filters
// e.g. the below says that user1 can see filters #0 and #1
//$FilterAuth['user1'] = Array(0, 1);

?>
