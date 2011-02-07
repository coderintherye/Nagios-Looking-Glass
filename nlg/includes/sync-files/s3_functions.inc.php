<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_functions.inc.php                                           |
   | Description:      Internal functions used by the NLG Poller                      |
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

function _ApplyFilterToHosts($HostObject)
{
  /*
   * _ApplyFilterToHosts()
   * Call-back function to remove un-needed hosts from the array
   */

  return $HostObject->RequiredInFilter;
}

function _ApplyFilterConditionToHosts(&$HostObject, $HostObjectKey, $FilterObject)
{
  /*
   * _ApplyFilterConditionToHosts()
   * Marks whether a host needs to be included in the filter, before it is passed
   * to _ApplyFilterToHosts
   */

    $HostObject->RequiredInFilter = $FilterObject->ApplyFilter($HostObject->HostStatus, $HostObject->HostName);
}

function _ApplyPagerGroupToHosts($HostObject)
{
  /*
   * _ApplyPagerGroupToHosts()
   * Call-back function to remove un-needed hosts from the array (based on current
   *  pager group)
   */

  return $HostObject->RequiredInGroup;
}

function _ApplyPagerGroupConditionToHosts(&$HostObject, $HostObjectKey, $GroupID)
{
  /*
   * _ApplyPagerGroupConditionToHosts()
   * Marks whether a host needs to be included in the pager group, before it is passed
   * to _ApplyPagerGroupToHosts
   */

  if ($HostObject->GroupID == $GroupID)
  {
    $HostObject->RequiredInGroup = true;
  } else {
    $HostObject->RequiredInGroup = false;
  }
}

function CheckStatusFiles($NagiosStatusDirectory, $NagiosStatusFiles)
{
  /*
   * CheckStatusFiles()
   * Check that all status files exist and are readable
   * as of 1.0.2, we open the files here as well
   */

  $StatusFileHandles = Array();

  foreach ($NagiosStatusFiles as $NagiosFileType => $NagiosFileName)
  {
    // try opening the status file
    // ARCi#177 - don't need to open objects.cache any more
    if ($NagiosFileType != "objects")
    {
      $FullFileName = $NagiosStatusDirectory . $NagiosFileName;
      $StatusFileHandles[$NagiosFileType] = @fopen($FullFileName, "r");

      // check it's opened the handle OK
      if ($StatusFileHandles[$NagiosFileType] === false)
      {
        return false;
      }
    }

  }

  // if we get here everything was OK

  settype($FullFileName, "null");

  return $StatusFileHandles;
}

function GetInitialInfo(&$NagiosPollerObject, &$StatusFileHandle)
{
  /*
   * GetInitialInfo()
   * Read the Nagios status file and get initial host/service counts
   */

  global $Timings;

  // Read our status file and get line positions
  $Timings['read_status_lines']['start'] = microtime(true);

  $StatusLineSplit = explode("\n", stream_get_contents($StatusFileHandle));
  @fseek($StatusFileHandle, 0);

  $Timings['read_status_lines']['finish'] = microtime(true);

  // Process each index item - hosts/services

  // INFO
  $Timings['collect_info']['start'] = microtime(true);

  // Get the Info details
  $InfoIndex = $NagiosPollerObject->NLG_Index->GetObject(0, "info");
  $NagiosPollerObject->Nagios_Version = ReadRegexValue("version", $StatusLineSplit[$InfoIndex->version]);
  $NagiosPollerObject->Nagios_FeedUpdated = ReadRegexValue("created", $StatusLineSplit[$InfoIndex->created]);
  unset($InfoIndex);

  $Timings['collect_info']['finish'] = microtime(true);

  // HOSTS
  $Timings['collect_hosts']['start'] = microtime(true);

  foreach($NagiosPollerObject->NLG_Index->GetObjectArray("hosts") as $HostIndexItem)
  {
    // create a new host object and set the host ID
    $NewHostObject = new S3_NagiosHost();
    $NewHostID_Seq = count($NagiosPollerObject->Hosts);

    // Read required status directives - Hostname, Status, Last check, Next check, Output result
    $NewHostObject->HostID = $HostIndexItem->ObjectID;
    $NewHostObject->HostName = ReadRegexValue("host_name", $StatusLineSplit[$HostIndexItem->host_name]);
    $NewHostObject->HostStatus = ReadRegexValue("current_state", $StatusLineSplit[$HostIndexItem->current_state]);
    $NewHostObject->LastCheck = ReadRegexValue("last_check", $StatusLineSplit[$HostIndexItem->last_check]);
    $NewHostObject->NextCheck = ReadRegexValue("next_check", $StatusLineSplit[$HostIndexItem->next_check]);
    $NewHostObject->CheckResult = ReadRegexValue("plugin_output", $StatusLineSplit[$HostIndexItem->plugin_output]);

    $Timings['collect_services_for_' . $NewHostObject->HostID]['start'] = microtime(true);

    // Read in services info for this host
    foreach($HostIndexItem->getHostServices() as $ServiceIndexItem)
    {
      $ServiceItem = $NagiosPollerObject->NLG_Index->GetObject($ServiceIndexItem, "service");
      // create a new service object for this metric
      $NewServiceObject = new S3_NagiosService();
      $NewServiceID_Seq = count($NewHostObject->HostServices);
      $NewServiceObject->ServiceID = $ServiceItem->ObjectID;

      // Read required status directives - Hostname, Status, Last check, Next check, Output result
      $NewServiceObject->HostID = $NewHostObject->HostID;
      $NewServiceObject->ServiceName = ReadRegexValue("service_description", $StatusLineSplit[$ServiceItem->service_description]);
      $NewServiceObject->ServiceStatus = ReadRegexValue("current_state", $StatusLineSplit[$ServiceItem->current_state]);
      $NewServiceObject->LastCheck = ReadRegexValue("last_check", $StatusLineSplit[$ServiceItem->last_check]);
      $NewServiceObject->NextCheck = ReadRegexValue("next_check", $StatusLineSplit[$ServiceItem->next_check]);
      $NewServiceObject->CheckResult = ReadRegexValue("plugin_output", $StatusLineSplit[$ServiceItem->plugin_output]);

      // set the service object in the host's services
      $NewHostObject->HostServices[$NewServiceID_Seq] = $NewServiceObject;

      unset($NewServiceObject);
      unset($ServiceItem);
    }

    $Timings['collect_services_for_' . $NewHostObject->HostID]['finish'] = microtime(true);

    // set the host object in the poller
    $NagiosPollerObject->Hosts[$NewHostID_Seq] = $NewHostObject;
    unset($NewHostObject);
    unset($HostIndexItem);
  }
  $Timings['collect_hosts']['finish'] = microtime(true);

  // No longer need the index
  unset($NagiosPollerObject->NLG_Index);

  // update host/service counts
  $NagiosPollerObject = UpdateHostStatusCounts($NagiosPollerObject);
  $NagiosPollerObject->Nagios_HostFilterCount = count($NagiosPollerObject->Hosts);

  @fclose($StatusFileHandle);
  unset($StatusFileHandle);

  return $NagiosPollerObject;
}

function UpdateHostStatusCounts($PollerObject, $FilterObject = false)
{
  /*
   * UpdateHostStatusCounts()
   * Update the host Up, Down, Unreachable, Unknown counts in the $PollerObject
   */

    global $ClientHealth_NewMeterBehaviour;

    $regenStats = false;

    // Work out whether to adjust the stats
    if ($FilterObject === false)
    {
        $regenStats = true;
    }
    else if (
        $FilterObject !== false &&
        in_array($FilterObject->Type, Array("host", "both")) &&
        isset($ClientHealth_NewMeterBehaviour) &&
        $ClientHealth_NewMeterBehaviour === true
    )
    {
        $regenStats = true;
    }

    if ($regenStats === true)
    {
        // Reset current stats to zero
        $PollerObject->Nagios_HostCount = 0;
        $PollerObject->Nagios_ServiceCount = 0;
        $PollerObject->Network_ServerOK = 0;
        $PollerObject->Network_ServerDown = 0;
        $PollerObject->Network_ServerNetworkError = 0;
        $PollerObject->Network_ServerDegradedMetrics = 0;
        $PollerObject->Network_ServerUnknown = 0;
        $PollerObject->Network_MetricOK = 0;
        $PollerObject->Network_MetricWarn = 0;
        $PollerObject->Network_MetricFail = 0;
        $PollerObject->Network_MetricUnknown = 0;

      foreach($PollerObject->Hosts as &$HostEntry)
      {
        // Update service counts (also corrects the host statuses - eg. 3 for
        // degraded metrics
        $HostEntry = UpdateServiceStatusCounts($HostEntry);
        $PollerObject->Nagios_ServiceCount += $HostEntry->ServiceCount_Total;
        $PollerObject->Network_MetricOK += $HostEntry->ServiceCount_OK;
        $PollerObject->Network_MetricWarn += $HostEntry->ServiceCount_Warn;
        $PollerObject->Network_MetricFail += $HostEntry->ServiceCount_Fail;
        $PollerObject->Network_MetricUnknown += $HostEntry->ServiceCount_Unknown;

        switch($HostEntry->HostStatus)
        {
          case 0:
            $PollerObject->Network_ServerOK++;
            $PollerObject->Nagios_HostCount++;
            break;
          case 1:
            $PollerObject->Network_ServerDown++;
            $PollerObject->Nagios_HostCount++;
            break;
          case 2:
            $PollerObject->Network_ServerNetworkError++;
            $PollerObject->Nagios_HostCount++;
            break;
          case 3:
            $PollerObject->Network_ServerDegradedMetrics++;
            $PollerObject->Nagios_HostCount++;
            break;
          default:
            $PollerObject->Network_ServerUnknown++;
            $PollerObject->Nagios_HostCount++;
            break;
        }
      }
    }

  return $PollerObject;
}

function GetNetworkComments(&$NagiosPollerObject, $CommentsFileHandle)
{
  /*
   * GetNetworkComments()
   * Find all comments for the entire Nagios system
   */

  // match any "comment {" declaration and store the offset for each
  preg_match_all("/(?:\n|\r\n)\w*(?:hostcomment|servicecomment) {(?:\n|\r\n)/", stream_get_contents($CommentsFileHandle), $CommentsMatches, PREG_OFFSET_CAPTURE, 0);
  @fseek($CommentsFileHandle, 0);

  foreach($CommentsMatches[0] as $CommentEntry)
  {
    preg_match("/(hostcomment|servicecomment) {(?:\n|\r\n)(.*)}/Us", stream_get_contents($CommentsFileHandle), $CommentMatch, 0, $CommentEntry[1]);
    @fseek($CommentsFileHandle, 0);
    // $CommentMatch[0]; now contains the full comments definition

    // check if the comment has "#NLG:" at the beginning (authorised to be passed back to NLG)
    if (substr(ReadRegexValue("comment_data", $CommentMatch[0]), 0, 5) == "#NLG:")
    {
      // create a new service object and set the service ID
      $NewCommentID_Seq = count($NagiosPollerObject->Comments);
      $NewCommentObject = new S3_NagiosComment();
      $NewCommentObject->CommentID = "C" . $NewCommentID_Seq;
      $NewCommentObject->Type = $CommentMatch[1];

      $NewCommentObject->Host = ReadRegexValue("host_name", $CommentMatch[0]);

      // if this is a service comment, get the service description
      if ($NewCommentObject->Type == "servicecomment")
      {
        $NewCommentObject->Service = ReadRegexValue("service_description", $CommentMatch[0]);
      }

      // Read required status directives - Comment text, entry time, author
      $NewCommentObject->Author = ReadRegexValue("author", $CommentMatch[0]);
      $NewCommentObject->EntryTime = ReadRegexValue("entry_time", $CommentMatch[0]);

      // set comment text and strip the #NLG: off the front
      $NewCommentObject->CommentText = ReadRegexValue("comment_data", $CommentMatch[0]);
      $NewCommentObject->CommentText = substr($NewCommentObject->CommentText, 5, strlen($NewCommentObject->CommentText) - 5);

      // set the comment object in the network poller
      $NagiosPollerObject->Comments[$NewCommentID_Seq] = $NewCommentObject;

      unset($NewServiceObject);
    }
  }

  return $NagiosPollerObject;
}

function UpdateServiceStatusCounts($ServerObject)
{
  /*
   * UpdateServiceStatusCounts()
   * Update the service OK, Warn, Critical, Unknown counts in $ServerObjects
   */

  $ServerObject->ServiceCount_OK = 0;
  $ServerObject->ServiceCount_Warn = 0;
  $ServerObject->ServiceCount_Fail = 0;
  $ServerObject->ServiceCount_Unknown = 0;
  $ServerObject->ServiceCount_Total = 0;

  foreach($ServerObject->HostServices as &$ServiceEntry)
  {
    switch($ServiceEntry->ServiceStatus)
    {
      case 0:
        $ServerObject->ServiceCount_OK++;
        $ServerObject->ServiceCount_Total++;
        break;
      case 1:
        $ServerObject->ServiceCount_Warn++;
        $ServerObject->ServiceCount_Total++;
        break;
      case 2:
        $ServerObject->ServiceCount_Fail++;
        $ServerObject->ServiceCount_Total++;
        break;
      default:
        $ServerObject->ServiceCount_Unknown++;
        $ServerObject->ServiceCount_Total++;
        break;
    }
  }

  // NLG-specific status: Degraded Metrics
  // if the server status is currently OK (0), AND one or more services are
  // 'failed' or 'warning', set the server status to 'degraded metrics (3)'

  if ($ServerObject->HostStatus == 0)
  {
    if ($ServerObject->ServiceCount_Warn > 0)
    {
      $ServerObject->HostStatus = 3;
    }
    if ($ServerObject->ServiceCount_Fail > 0)
    {
      $ServerObject->HostStatus = 3;
    }
  }

  return $ServerObject;
}

function ReadRegexValue($ValueToRead, $TextToReadFrom)
{
  /*
   * ReadRegexValue()
   * Find the value assigned to $ValueToRead from the text $TextToReadFrom
   */

  preg_match("/" . $ValueToRead . "=(.*)/", $TextToReadFrom, $RegexMatch);

  if (count($RegexMatch) > 0)
  {
    return $RegexMatch[1];
  } else {
    return "Unreadable " . $ValueToRead;
  }

}

function FilterHosts($PollerObject, $FilterObject, $FilterID)
{
  /*
   * FilterHosts()
   * Filter the host list according to the filter defined as $FilterID
   */

  // first create a copy of the hosts array to work on
  $FilteredHostList = $PollerObject->Hosts;

  // apply the filtering
  array_walk($FilteredHostList, "_ApplyFilterConditionToHosts", $FilterObject);
  $FilteredHostList = array_filter($FilteredHostList, "_ApplyFilterToHosts");

  // restore the copy to the original poller object
  $PollerObject->Hosts = $FilteredHostList;
  unset($FilteredHostList);

  // update the filter info in the poller object
  $PollerObject->Nagios_HostFilterCount = count($PollerObject->Hosts);
  $PollerObject->FilterApplied = $FilterObject->DisplayText;
  $PollerObject->CurrentFilter = $FilterID;

    // v1.1.0#beta3 - update the host/service status counts now so the
    // Network Health meter only shows hosts within the current filter

    // update host/service counts
    $PollerObject = UpdateHostStatusCounts($PollerObject, $FilterObject);
    $PollerObject->Nagios_HostFilterCount = count($PollerObject->Hosts);

  return $PollerObject;
}

function GroupHosts($PollerObject, $GroupID)
{
  /*
   * GroupHosts()
   * Apply a pager grouping to the host collection
   * Note if we have a filter applied, we count the hosts returned from the
   * filter, not from Nagios
   */

  global $ClientPager_GroupSize;
  global $ClientPager_RangeSize;

  $GroupsRequired = $PollerObject->Nagios_HostFilterCount / $ClientPager_GroupSize;

  // if $GroupsRequired is an integer, use that value
  // if it isn't, we need to find it's integer, and add an extra group
  //   (to cover the part-group)
  if (!is_int($GroupsRequired))
  {
    $GroupsRequired = (int)$GroupsRequired;
    $GroupsRequired++;
  }

  for ($intC = 0; $intC < $GroupsRequired; $intC++)
  {
    $PollerObject->HostGroups[$intC]['lower_bound_index'] = $ClientPager_GroupSize * $intC;
    $PollerObject->HostGroups[$intC]['upper_bound_index'] = ($ClientPager_GroupSize * ($intC + 1)) - 1;
    $PollerObject->HostGroups[$intC]['lower_bound_display'] = $PollerObject->HostGroups[$intC]['lower_bound_index'] + 1;
    $PollerObject->HostGroups[$intC]['upper_bound_display'] = $PollerObject->HostGroups[$intC]['upper_bound_index'] + 1;
  }

  unset($GroupsRequired);

  // ARCi#15
  // If the final group in the groups list doesn't have the full amount of servers to make
  // a full group, find the final server in the list

  $LastGroupID = count($PollerObject->HostGroups) - 1;
  $LastGroupLastServerID = $PollerObject->HostGroups[$LastGroupID]['upper_bound_index'];

  // assign each host to a group ID
  $HostSeqID = 0;
  foreach ($PollerObject->Hosts as &$NagiosHost)
  {
    // find the group this host fits in - $intC is the group ID
    for ($intC = 0; $intC < count($PollerObject->HostGroups); $intC++)
    {
      if ($HostSeqID >= $PollerObject->HostGroups[$intC]['lower_bound_index'])
      {
        if ($HostSeqID <= $PollerObject->HostGroups[$intC]['upper_bound_index'])
        {
          // is this host in our last group?
          $NagiosHost->GroupID = $intC;

          // ARCi#15 - check if this host is in our "last" group
          // keep an onging update of the 'last server in this group'
          if ($intC == $LastGroupID)
          {
            $LastGroupLastServerID = $HostSeqID;
          }
        }
      }
    }
    unset($intC);
    $HostSeqID++;
  }
  unset($HostSeqID);

  // ARCi#15 - update the last group with the actual last host
  $PollerObject->HostGroups[$LastGroupID]['upper_bound_index'] = $LastGroupLastServerID;
  $PollerObject->HostGroups[$LastGroupID]['upper_bound_display'] = ($LastGroupLastServerID + 1);
  unset($LastGroupID);
  unset($LastGroupLastServerID);

  // added in v1.0.0 - if $GroupID is -1, don't apply any grouping
  // this is so we can see which groups are defined but not yet applied
  $PollerObject->CurrentGroup = $GroupID;

  if ($GroupID != -1)
  {
    // now filter according to $GroupID

    // first create a copy of the hosts array to work on
    $GroupedHostList = $PollerObject->Hosts;

    // apply the grouping
    array_walk($GroupedHostList, "_ApplyPagerGroupConditionToHosts", $GroupID);
    $GroupedHostList = array_filter($GroupedHostList, "_ApplyPagerGroupToHosts");

    // restore the copy to the original poller object
    $PollerObject->Hosts = $GroupedHostList;

    unset($GroupedHostList);
  }

  return $PollerObject;
}

function FormatDate($Timestamp, $DateFormat)
{
  /*
   * FormatDate()
   * Return a formatted date according to $DateFormat, from timestamp $Timestamp
   */

  settype($Timestamp, "integer");

  $FormattedDate = date($DateFormat, $Timestamp);

  return $FormattedDate;
}

function CheckIPAgainstACL($IPAddress, $ACL_Enabled, $ACL_ApplyOrder, $ACL_AllowList, $ACL_BlockList)
{
  /*
   * CheckIPAgainstACL()
   * Check whether the given IP address is allowed to connect using the supplied ACL
   * Added in v1.0.0 to replace CheckIPAgainstACL_Client and CheckIPAgainstACL_Server
   */

  $ACL_Result = false;

  // first check if ACLs are enabled

  if (defined("DEBUG_REQUIRED")) {echo "*** S3 Client -> CheckIPAgainstACL() running ***\n\n";};

  if ($ACL_Enabled == 1)
  {
    if (defined("DEBUG_REQUIRED")) {echo "Client ACLs are enabled, comparing " . $IPAddress . " against rules ... \n";};
    switch ($ACL_ApplyOrder)
    {
      case "AD":
        // Order Allow/Deny - process rules
        // If in the allow list, allow
        // If not in the allow list, but in the block list, deny
        // If not in the allow list, and not in the block list, allow
        if (!in_array($IPAddress, $ACL_AllowList))
        {
          if (in_array($IPAddress, $ACL_BlockList))
          {
            // We're in the block list so deny
            if (defined("DEBUG_REQUIRED")) {echo $IPAddress . " is in the block list, BLOCKED\n";};
            $ACL_Result = false;
          } else {
            // We're not in the allow list or the block list so we're OK
            if (defined("DEBUG_REQUIRED")) {echo $IPAddress . " is not in either list, ALLOWED according to \$ACL_ApplyOrder";};
            $ACL_Result = true;
          }
        } else {
          // We're in the allow list so we're OK
          if (defined("DEBUG_REQUIRED")) {echo $IPAddress . " is in the allow list, ALLOWED\n";};
          $ACL_Result = true;
        }
        break;

      case "DA":
        // Order Deny/Allow - process rules
        // If we're in the block list, deny
        // If we're not in the block list, but in the allow list, allow
        // If not in the block list, and not in the allow list, deny
        if (in_array($IPAddress, $ACL_BlockList))
        {
          // We're in the block list so block
          if (defined("DEBUG_REQUIRED")) {echo $IPAddress . " is in the block list, BLOCKED\n";};
          $ACL_Result = false;
        } else {
          if (in_array($IPAddress, $ACL_AllowList))
          {
            // We're in the allow list, so OK
            if (defined("DEBUG_REQUIRED")) {echo $IPAddress . " is in the allow list, ALLOWED\n";};
            $ACL_Result = true;
          } else {
            // We're in neither list, so block
            if (defined("DEBUG_REQUIRED")) {echo $IPAddress . " is not in either list, BLOCKED according to \$ACL_ApplyOrder";};
            $ACL_Result = false;
          }
        }

        break;

      default:
        $ACL_Result = false;
    }
  } else {
    if (defined("DEBUG_REQUIRED")) {echo "Client ACLs are disabled, skipped comparing IP to access lists.\n\n";};
    $ACL_Result = true;
  }

  if (defined("DEBUG_REQUIRED")) {echo "*** S3 Client -> CheckIPAgainstACL() finished ***\n\n";};

  return $ACL_Result;
}

function RebuildIndexes(&$NagiosPollerObject, &$StatusFileHandle, $ObjectsCacheFileName)
{
  /*
   * RebuildIndexes()
   * Find the line offset locations of hosts and services
   */

  global $Timings;

  // Set max execution times for the script
  $OldExecTime = ini_get('max_execution_time');
  set_time_limit(300);

  // get the host/service info from the status file
  $StatusFileContents = explode("\n", stream_get_contents($StatusFileHandle));
  @fseek($StatusFileHandle, 0);

  // Create our index object to work with
  $NagiosPollerObject->NLG_Index = new S3_Index();

  // Process each file in the status file and find out what type of value it is
  // Read the info/host/services definitions into our objects
  $InfoStartFound = false;
  $InfoStart = 0;
  $InfoEnd = 0;
  $HostStartFound = false;
  $HostID = 0;
  $HostStart = 0;
  $HostEnd = 0;
  $ServiceStartFound = false;
  $ServiceID = 0;
  $ServiceStart = 0;
  $ServiceEnd = 0;

  foreach($StatusFileContents as $LineNumber => $LineContent)
  {
    // When we hit the start of an object definition, set the starting marker
    // and keep reading until we hit the ending marker
    switch ($LineContent)
    {
      case "info {":
        $InfoStartFound = true;
        $InfoStart = $LineNumber;
        $InfoCapture = Array();
        break;

      // RT#363 - Nagios 3.0 support - host and service statuses
      // are now called hoststatus and servicestatus, so check
      // for both to allow backward-compatibility with Nagios 2.x.

      case "host {":
        $HostStartFound = true;
        $HostStart = $LineNumber;
        break;

      case "hoststatus {":
        $HostStartFound = true;
        $HostStart = $LineNumber;
        break;

      case "service {":
        $ServiceStartFound = true;
        $ServiceStart = $LineNumber;
        break;

      case "servicestatus {":
        $ServiceStartFound = true;
        $ServiceStart = $LineNumber;
        break;

      case "\t}";
        // When our objects have been defined, add the index entry to our Index
        if ($InfoStartFound)
        {
          $InfoEnd = $LineNumber;
          $InfoStartFound = false;
          // Dont currently store Info section in Index
          $NagiosPollerObject->NLG_Index->AddObject(new S3_IndexEntry("I0", $InfoStart, $InfoEnd, $InfoCapture), "info");
        } elseif ($HostStartFound) {
          $HostEnd = $LineNumber;
          $HostStartFound = false;
          $NagiosPollerObject->NLG_Index->AddHost($Hostname, new S3_IndexEntry("H" . $HostID, $HostStart, $HostEnd, $HostCapture));
          $HostID++;
        } elseif ($ServiceStartFound) {
          $ServiceEnd = $LineNumber;
          $ServiceStartFound = false;
          $NagiosPollerObject->NLG_Index->AddObject(new S3_IndexEntry("S" . $ServiceID, $ServiceStart, $ServiceEnd, $ServiceCapture), "service", Array());
          $ServiceID++;
        } else {
          // Don't do anything else if a section hasn't been started
          // Will probably never happen but you never know!
        }
        break;

      default:
        // Now we check to see if we're in a host/service/info definition
        // and find out relevant info to our object
        switch(substr($LineContent, 0, 1))
        {
          case "\t":
            if ($InfoStartFound)
            {
              if (substr($LineContent, 1, 7) == "created")
              {
                $InfoCapture['created'] = $LineNumber;
              } elseif (substr($LineContent, 1, 7) == "version") {
                $InfoCapture['version'] = $LineNumber;
              }
            } elseif ($HostStartFound) {
              if (substr($LineContent, 1, 9) == "host_name")
              {
                $HostCapture['host_name'] = $LineNumber;
                $Hostname = ReadRegexValue("host_name", $LineContent);
              } elseif (substr($LineContent, 1, 13) == "plugin_output") {
                $HostCapture['plugin_output'] = $LineNumber;
              } elseif (substr($LineContent, 1, 13) == "current_state") {
                $HostCapture['current_state'] = $LineNumber;
              } elseif (substr($LineContent, 1, 10) == "last_check") {
                $HostCapture['last_check'] = $LineNumber;
              } elseif (substr($LineContent, 1, 10) == "next_check") {
                $HostCapture['next_check'] = $LineNumber;
              }
            } elseif ($ServiceStartFound) {
              if (substr($LineContent, 1, 9) == "host_name")
              {
                $ServiceCapture['host_name'] = $LineNumber;
                // Update the owner host object with this service
                $HostObject = $NagiosPollerObject->NLG_Index->FindHostByName(ReadRegexValue("host_name", $LineContent));
                $HostObject->AddHostService($ServiceID);
                $NagiosPollerObject->NLG_Index->ReplaceObject(ReadRegexValue("host_name", $LineContent), "host", $HostObject);
                unset($HostObject);
              } elseif (substr($LineContent, 1, 19) == "service_description") {
                $ServiceCapture['service_description'] = $LineNumber;
              } elseif (substr($LineContent, 1, 13) == "plugin_output") {
                $ServiceCapture['plugin_output'] = $LineNumber;
              } elseif (substr($LineContent, 1, 13) == "current_state") {
                $ServiceCapture['current_state'] = $LineNumber;
              } elseif (substr($LineContent, 1, 10) == "last_check") {
                $ServiceCapture['last_check'] = $LineNumber;
              } elseif (substr($LineContent, 1, 10) == "next_check") {
                $ServiceCapture['next_check'] = $LineNumber;
              }
            }
        }
    }
  }

  set_time_limit($OldExecTime);

  $IndexBytesWritten = file_put_contents("index/objects.index", serialize($NagiosPollerObject->NLG_Index));

  if ($IndexBytesWritten == 0 || $IndexBytesWritten === false)
  {
    unset($IndexBytesWritten);
    return false;
  }

  unset($IndexBytesWritten);

  // MD5-hash the Nagios "objects.cache" file
  $HashBytesWritten = file_put_contents("index/objects.hash", md5_file($ObjectsCacheFileName));
  @fseek($ObjCacheFileHandle, 0);

  if ($HashBytesWritten == 0 || $HashBytesWritten === false)
  {
    unset($HashBytesWritten);
    return false;
  }

  unset($HashBytesWritten);
  unset($NagiosPollerObject->NLG_Index);

  return true;
}

function DebugOutputTimings($Timings)
{
  /*
   * DebugOutputTimings()
   * Process and output debug timings
   */

  foreach($Timings as &$TimingEntry)
  {
    $TimingEntry['time'] = $TimingEntry['finish'] - $TimingEntry['start'];
  }

  print_r($Timings);
  exit(1);
}

function ApplyFilterAuth($username, $filters)
{
    /*
     * ApplyFilterAuth()
     * Provides different filters to different users
     */
    require(drupal_get_path('module', 'nlg') ."/client/sync-files/s3_filter_auth.inc.php");
    //global $FilterAuth;
    $userFilters = Array();

    // Provide default set of filters if none requested
    if (!array_key_exists("default", $FilterAuth))
    {
        $FilterAuth['default'] = array_keys($filters);
    }

    if ($username != null && array_key_exists($username, $FilterAuth))
    {
        foreach ($FilterAuth[$username] as $filter)
        {
            $userFilters[] = $filters[$filter];
        }
    }
    else
    {
        foreach ($FilterAuth['default'] as $filter)
        {
            $userFilters[] = $filters[$filter];
        }
    }

    return $userFilters;
}

?>
