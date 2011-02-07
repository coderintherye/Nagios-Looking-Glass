<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_poller.php                                                  |
   | Description:      Network Looking Glass - poller as requested by client          |
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

require("sync-files/s3_class.inc.php");
require("sync-files/s3_functions.inc.php");
require("sync-files/s3_config.inc.php");
require("sync-files/s3_lang_" . $NLG_Language . ".inc.php");
require("sync-files/s3_filter.inc.php");
require("sync-files/s3_filter_auth.inc.php");

$Timings['processing']['start'] = microtime(true);

// Tell browsers to display as plain text
header("Content-type: text/plain");

// Create the class declaration
$NLGPoller = new S3_NagiosPoller();
$NLGPoller->Init($ServerNagiosHost);

// Check if the caller is allowed according to the ACL configuration
if (!CheckIPAgainstACL($_SERVER['REMOTE_ADDR'], $ServerACL_Enabled, $ServerACL_ApplyOrder, $ServerACL_AllowList, $ServerACL_BlockList))
{
  $NLGPoller->CreateErrorToken($Language['ACL_DENIED']);
}

// Check the $ServerNagiosFeed has a trailing slash - on Unix this is "/", Windows it is "\\"
if (substr($ServerNagiosFeed, strlen($ServerNagiosFeed) - strlen($NLG_LineEnding), strlen($NLG_LineEnding)) != $NLG_LineEnding)
{
  $NLGPoller->CreateErrorToken($Language['CONFIG_NAGIOSFEED_INVALID']);
}

// Check the files we need from Nagios exist and are readable
// as of v1.0.2, keep an array of open file handles
$StatusFileHandles = CheckStatusFiles($ServerNagiosFeed, $ServerNagiosFiles);
if ($StatusFileHandles === false)
{
  $NLGPoller->CreateErrorToken($Language['CANNOT_READ_NAGIOS_FILES']);
}

// ARCi#120 - Rebuild the indexes if they don't exist (or Nagios has changed it's config)

// assume an index rebuild
$RebuildIndex = true;

// check the index files exist and are valid
if (file_exists("index/objects.index") && file_exists("index/objects.hash"))
{
  $NagiosObjCacheHash = file_get_contents("index/objects.hash");

  // compare the current objects.cache MD5 with the saved one
  if (md5_file($ServerNagiosFeed . $ServerNagiosFiles["obj_cache"]) == $NagiosObjCacheHash)
  {
    $RebuildIndex = false;
  }
}

// Need to rebuild the index
if ($RebuildIndex)
{
  $Timings['build_index']['start'] = microtime(true);
  if (!RebuildIndexes($NLGPoller, $StatusFileHandles["status"], $ServerNagiosFeed . $ServerNagiosFiles["obj_cache"]))
  {
    $NLGPoller->CreateErrorToken($Language['CANNOT_REBUILD_INDEXES']);
  }
  $Timings['build_index']['finish'] = microtime(true);
}

// Read the previously-stored index
$Timings['read_index']['start'] = microtime(true);
$NLGPoller->NLG_Index = unserialize(file_get_contents("index/objects.index"));
$Timings['read_index']['finish'] = microtime(true);

// Apply filter authentications
if (array_key_exists("PHP_AUTH_USER", $_SERVER))
{
    $HostFilter = ApplyFilterAuth($_SERVER['PHP_AUTH_USER'], $HostFilter);
}
else
{
    $HostFilter = ApplyFilterAuth(null, $HostFilter);
}

// Pull the initial info from the Nagios status files
if (!GetInitialInfo($NLGPoller, $StatusFileHandles["status"]))
{
  $NLGPoller->CreateErrorToken($Language['GENERIC_PROCESS_ERROR'] . "<br /><br />" . $NLGPoller->LastPollerError);
}

// Get any comments prefixed with "#NLG:" and send to the NLG client
if (!GetNetworkComments($NLGPoller, $StatusFileHandles["comments"]))
{
  $NLGPoller->CreateErrorToken($Language['GENERIC_PROCESS_ERROR'] . "<br /><br />" . $NLGPoller->LastPollerError);
}

// check if we need to apply a filter to the hostlist
if (isset($_GET['fid']))
{
  // sanitise the GET parameter - fid
  settype($_GET['fid'], "integer");
  if (!is_integer($_GET['fid']))
  {
    // if fid is not an integer, force it to zero
    $FilterToApply = 0;
  } else {
    // otherwise set it to our received value
    $FilterToApply = $_GET['fid'];
  }
} else {
  // no filter given so default to zero
  $FilterToApply = 0;
}

// check if the filter actually exists
if (!isset($HostFilter[$FilterToApply]))
{
  $NLGPoller->CreateErrorToken(sprintf($Language['INVALID_FILTER'], $FilterToApply));
}

// Our filter is fine - so use it!
$NLGPoller = FilterHosts($NLGPoller, $HostFilter[$FilterToApply], $FilterToApply);

// check if we need to apply a grouping filter to the hostlist
if (isset($_GET['gid']))
{
  // sanitise the GET parameter - gid
  settype($_GET['gid'], "integer");
  if (!is_integer($_GET['gid']))
  {
    // if gid is not an integer, force it to zero
    $GroupToApply = 0;
  } else {
    // otherwise set it to our received value
    $GroupToApply = $_GET['gid'];
  }
} else {
  // no group filter given so default to zero
  $GroupToApply = 0;
}

// we have to initally create the host filters to run the "does host filter exist?" check
// supply the group ID as -1 so no grouping gets applied
$NLGPoller = GroupHosts($NLGPoller, -1);

// check if the group filter actually exists - removed from v1.0.0 so it doesn't generate
// a poller error when no hosts match the chosen filter.

//if (!isset($NLGPoller->HostGroups[$GroupToApply]))
//{
//  $NLGPoller->CreateErrorToken(sprintf($Language['INVALID_GROUP'], $GroupToApply));
//}

// Our group filter is fine - so use it!
$NLGPoller = GroupHosts($NLGPoller, $GroupToApply);

// Remove the Index as we don't need to send it to the client
unset($NLGPoller->NLG_Index);

// Final statistics
$Timings['processing']['finish'] = microtime(true);

//print_r($NLGPoller);

// Output our completed object (or timings)
$NLGPoller->CreateOutputToken();
//DebugOutputTimings($Timings);

?>
