<?php

/*
   +----------------------------------------------------------------------------------+
   | (c) 2006-2008 - Network Mail Applications Development                            |
   |----------------------------------------------------------------------------------|
   | File Name:        s3_class.inc.php                                               |
   | Description:      Class templates for hosts/services/comments                    |
   |----------------------------------------------------------------------------------|
   | This application is distributed under the terms of the Creative Commons Public   |
   | license (UK & Wales.)  Your copy of the license is called 'LICENSE.txt' and is   |
   | in the root of the application distribution files.                               |
   |                                                                                  |
   | You may also view the license online at the following URLs:                      |
   |                                                                                  |
   |     http://creativecommons.org/licenses/by-sa/2.0/uk/                            |
   |     http://creativecommons.org/licenses/by-sa/2.0/uk/legalcode                   |
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

class S3_NagiosPoller
{
  /*
   * S3_NagiosPoller()
   * Holds all details of hosts/services and metrics of Nagios
   */

  // Core Poller Info
  public $Poller_Version = "1.1.0/S3";
  public $Nagios_Hostname;
  public $Nagios_Version;
  public $Nagios_FeedUpdated;
  public $NLG_FeedSource = "live";
    public $NLG_CacheCount = 0;
  public $NLG_CachedTime;
  public $NLG_Index;

  // Filtering/Grouping status
  public $Nagios_HostCount = 0;
  public $Nagios_HostFilterCount = 0;
  public $Nagios_ServiceCount = 0;
  public $FilterApplied = "--ALL--";
  public $CurrentFilter;
  public $HostGroups;
  public $CurrentGroup;

  // Network Status
  public $Network_ServerOK;
  public $Network_ServerDown;
  public $Network_ServerNetworkError;
  public $Network_ServerDegradedMetrics;
  public $Network_ServerUnknown;
  public $Network_MetricOK;
  public $Network_MetricWarn;
  public $Network_MetricFail;
  public $Network_MetricUnknown;

  // Data Objects
  public $Hosts = Array();
  // as of 1.0.0 - $Services is now part of the host class as it's host-specific
  public $Comments = Array();

  // Error Info
  public $LastPollerError;

  public function Init($NagiosHostInConfig = "")
  {
    /*
     * Init()
     * Initialise the class - if a hostname is given, use it, if not,
     * look it up from the system
     */

    if (strlen($NagiosHostInConfig) == 0) {
      $this->Nagios_Hostname = trim(`hostname`);
    } else {
      $this->Nagios_Hostname = trim($NagiosHostInConfig);
    }

    $this->Hosts = Array();
    $this->Comments = Array();

    return true;
  }

  public function CreateErrorToken($ErrorDescription)
  {
    /*
     * CreateErrorToken()
     * Create an output return token with error information
     */

    $ErrorText = base64_encode($ErrorDescription);
    $ErrorTextChecksum = md5($ErrorText);
    echo "**NLGPOLLER!!" . $this->Poller_Version . "!!" . $this->Nagios_Hostname . "!!FALSE!!" . $ErrorText . "!!" . $ErrorTextChecksum . "!!NLGPOLLER**";
    exit(1);
  }

  public function CreateOutputToken()
  {
    /*
     * CreateErrorToken()
     * Create an output return token with all current data
     */

    $OutputText = base64_encode(serialize($this));
    $OutputTextChecksum = md5($OutputText);
    echo "**NLGPOLLER!!" . $this->Poller_Version . "!!" . $this->Nagios_Hostname . "!!TRUE!!" . $OutputText . "!!" . $OutputTextChecksum . "!!NLGPOLLER**";
    exit(0);
  }
}

class S3_NagiosHost
{
  /*
   * S3_NagiosHost()
   * The host template class
   */

  public $HostID;
  public $GroupID;
  public $HostName;
  public $HostStatus;
  public $LastCheck;
  public $NextCheck;
  public $CheckResult;
  public $HostServices = Array();
  public $ServiceCount_Total;
  public $ServiceCount_OK;
  public $ServiceCount_Warn;
  public $ServiceCount_Fail;
  public $ServiceCount_Unknown;
  public $RequiredInFilter = true;
  public $RequiredInGroup = true;
}

class S3_NagiosService
{
  /*
   * S3_NagiosService()
   * The service template class
   */

  public $ServiceID;
  public $HostID;
  public $ServiceName;
  public $ServiceStatus;
  public $LastCheck;
  public $NextCheck;
  public $CheckResult;
}

class S3_NagiosComment
{
  /*
   * S3_NagiosComment()
   * Any comment starting with NLG# is passed back to the client
   */

  // Nagios host comments

  public $CommentID;
  public $Type;
  public $Host;
  public $Service;
  public $EntryTime;
  public $Author;
  public $CommentText;
}

class S3_NetworkFilter
{
    public $DisplayText;
    private $IncludedHosts;
    private $IncludedStatuses;
    public $Type;

    public function __construct()
    {
      $this->IncludedHosts = Array();
        $this->IncludedStatuses = Array();
    }

  function Create($DisplayText)
  {
    /*
     * Create()
     * Create a new filter with options given in parameters
     */

    $this->DisplayText = $DisplayText;
  }

  function AddStatus($Status)
  {
    /*
     * AddStatus()
                 * Add a required status to the filter
     */

    settype($Status, "integer");
    $this->IncludedStatuses[] = $Status;
  }

    public function AddHost($Hostname)
  {
    /*
                 * AddHost()
                 * Add a required host name to the filter
     */

        settype($Hostname, "string");
    $this->IncludedHosts[] = $Hostname;
  }

  function ApplyFilter($Status, $Hostname)
  {
    /*
     * ApplyFilter()
                 * Lookup the current filter type, and either apply the status, hostname or both
     */

    settype($Status, "integer");
        settype($Hostname, "string");

        switch(strtolower($this->Type))
        {
          case "host":
                return in_array($Hostname, $this->IncludedHosts);

            case "status":
                return in_array($Status, $this->IncludedStatuses);

            case "both":
                if (
                    in_array($Hostname, $this->IncludedHosts) &&
                    in_array($Status, $this->IncludedStatuses)
                )
                {
                  return true;
                } else {
                  return false;
                }
        }
  }

    /**
     * Applies this filter to a host regardless of the filter type
     *
     * @param string $hostname Hostname to filter
     */
    function ApplyHostFilter($hostname)
    {
        if ($this->Type == "host")
        {
            return in_array($hostname, $this->IncludedHosts);
        }
        else
        {
            return true;
        }
    }

    /**
     * Sets the type of this filter
     *
     * @param string $Type Filter type (host, status or both)
     */
    public function SetType($Type)
    {
      switch(strtolower($Type))
        {
          case "host":
                $this->Type = $Type;
                break;
            case "status":
                $this->Type = $Type;
                break;
            case "both":
                $this->Type = $Type;
                break;
        }
    }
}

class S3_Index
{
  private $InfoIndex;
  private $HostIndex = Array();
  private $ServiceIndex = Array();

  public function AddObject($Object, $ObjectType)
  {
    /*
     * AddObject()
     * Add the object into the current index
     */

    switch($ObjectType)
    {
      case "info":
        $this->InfoIndex = $Object;
        break;

      case "host":
        $this->HostIndex[count($this->HostIndex)] = $Object;
        break;

      case "service":
        $this->ServiceIndex[count($this->ServiceIndex)] = $Object;
        break;

      default:
        return false;
    }

    return true;
  }

  public function AddHost($Hostname, $Object)
  {
    /*
     * AddHost()
     * Add the host object into the current index
     */

    $this->HostIndex[$Hostname] = $Object;

    return true;
  }

  public function GetObjectArray($ObjectType)
  {
    /*
     * GetObjectArray()
     * Retrieve the given object array from the current index
     */

    switch($ObjectType)
    {
      case "hosts":
        return $this->HostIndex;
        break;
      case "services":
        return $this->ServiceIndex;
        break;
      default:
        return false;
    }
  }

  public function GetObject($Index, $ObjectType)
  {
    /*
     * GetObject()
     * Retrieve the given object type from the current index
     */

    switch($ObjectType)
    {
      case "info":
        return $this->InfoIndex;

      case "host":
        if ($Index > count($this->HostIndex))
        {
          return false;
        } else {
          return $this->HostIndex[$Index];
        }

      case "service":
        if ($Index > count($this->ServiceIndex))
        {
          return false;
        } else {
          return $this->ServiceIndex[$Index];
        }

      default:
        return false;
    }
  }

  public function ReplaceObject($Index, $ObjectType, $Object)
  {
    /*
     * ReplaceObject()
     * Replace the given object type with $Object
     */

    switch($ObjectType)
    {
      case "info":
        $this->InfoIndex = $Object;
        break;

      case "host":
        if (isset($this->HostIndex[$Index]))
        {
          $this->HostIndex[$Index] = $Object;
        } else {
          return false;
        }
        break;

      case "service":
        if (isset($this->ServiceIndex[$Index]))
        {
          $this->ServiceIndex[$Index] = $Object;
        } else {
          return false;
        }
        break;
    }
  }

  public function FindHostByName($Name)
  {
    /*
     * FindHostByName()
     * Find the service object index where the hostname is $Name
     */

    if (isset($this->HostIndex[$Name]))
    {
      return $this->HostIndex[$Name];
    } else {
      return false;
    }
  }
}

class S3_IndexEntry
{
  private $m_HostServices = Array();
  private $m_Parameters = Array();

  public function __construct($ObjectID, $LineStart, $LineEnd, $LineCapture)
  {
    /*
     * __construct()
     * Constructor for this class - set line numbers
     */

    $this->ObjectID = $ObjectID;
    $this->LineStart = $LineStart;
    $this->LineEnd = $LineEnd;

    foreach($LineCapture as $Param => $Value)
    {
      $this->$Param = $Value;
    }
  }

  public function __get($Name)
  {
    /*
     * __get()
     * Get value of parameter $Name
     */

    if (isset($this->m_Parameters[$Name]))
    {
      return $this->m_Parameters[$Name];
    } else {
      return false;
    }
  }

  public function __set($Name, $Value)
  {
    /*
     * __set()
     * Set parameter $Name to $Value
     */

    $this->m_Parameters[$Name] = $Value;

    return true;
  }

  public function AddHostService($ServiceEntry)
  {
    /*
     * AddHostService()
     * Add a new host service to the array
     */

    $this->m_HostServices[count($this->m_HostServices)] = $ServiceEntry;

    return true;
  }

  public function getHostServices()
  {
    /*
     * getHostServices()
     * Return all host services from this instance
     */

    return $this->m_HostServices;
  }
}

?>
