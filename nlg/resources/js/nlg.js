var CurrentGroupID = -1;
var CurrentFilterID = -1;
var CurrentTemplate = "default";
var PH_Page_Content;
var PH_Server_Summary;
var PH_Feed_Info;
var RefreshHandler;
var RefreshHandler_NS;

function BuildURL(view, template_name, filter_id, group_id)
{
  /*
   * BuildURL()
   * Build a request URL
   */
  
  // set default parameters if not defined when the function was called
  // As of 1.0.2 - if filter_id, group_id are not set, we check the value of a GET parameter for them
  //   If the GET parameters for group_id and filter_id are not set, we'll be left with 0 for each

  if (typeof template_name == "undefined") {
    template_name = CurrentTemplate;
  }
  if (typeof filter_id == "undefined")
  {
    if (CurrentFilterID == -1) {
      CurrentFilterID = GetQSFilter();
      filter_id = CurrentFilterID;
    } else {
      filter_id = CurrentFilterID;
    }
  }
  if (typeof group_id == "undefined")
  {
    if (CurrentGroupID == -1) {
      CurrentGroupID = GetQSGroup();
      group_id = CurrentGroupID;
    } else {
      group_id = CurrentGroupID;
    }
  }

  var RequestURL;
  RequestURL = "?view="+view+"&template="+template_name+"&fid="+filter_id+"&gid="+group_id;
  
  return RequestURL;
}

function RefreshNetworkBrowser()
{
  /*
   * RefreshNetworkBrowser()
   * Refresh the network browser using the current filter and group
   */
  
  document.getElementById(PH_Server_Summary).innerHTML = nlg_language['loading_server_browser']+"...";
  document.getElementById(PH_Feed_Info).innerHTML = nlg_language['loading_feed_info']+"...";
  getRequest(BuildURL('server_summary'), PH_Server_Summary);
  getRequest(BuildURL('feed_info'), PH_Feed_Info);
  
  // as of 1.0.0 - reset and refresh the Network Browser
  window.clearTimeout(RefreshHandler);
  RefreshHandler = window.setTimeout('RefreshNetworkBrowser()', RefreshTime);
}

function RefreshNetworkStatus()
{
  /*
   * RefreshNetworkStatus()
   * Refresh the network status page using the current filter and group
   *
   * Added in 1.0.2
   */
  
  document.getElementById(PH_Page_Content).innerHTML = nlg_language['loading_network_status']+"...";
  document.getElementById(PH_Feed_Info).innerHTML = nlg_language['loading_feed_info']+"...";
  getRequest(BuildURL('network_status'), PH_Page_Content);
  getRequest(BuildURL('feed_info'), PH_Feed_Info);
  
  // as of 1.0.2 - reset and refresh the Network Status
  window.clearTimeout(RefreshHandler_NS);
  RefreshHandler_NS = window.setTimeout('RefreshNetworkStatus()', RefreshTime);
}

function StartUp(ph_server_summary, ph_page_content, ph_feed_info)
{
  /*
   * StartUp()
   * Initial page loading - load server summary, network status (and as of v1.0.2, feed info)
   *
   * Note: in v1.0.2, removed the "_Data" that was tagged onto the end of PH_Page_Content
   * and PH_Server_Summary
   */

  // set our document defaults
  PH_Page_Content = ph_page_content;
  PH_Server_Summary = ph_server_summary;
  PH_Feed_Info = ph_feed_info;
  
  // find our initial template
  CurrentTemplate = GetCurrentTemplate();
  
  if (GetQS("sid") != -1)
  {
    document.getElementById(PH_Server_Summary).innerHTML = nlg_language['refresh_server_details']+"...";
    getRequest(BuildURL('server_detail')+'&sid='+GetQS("sid"), PH_Page_Content);
  } else {
    document.getElementById(PH_Page_Content).innerHTML = nlg_language['loading_network_status']+"...";
    getRequest(BuildURL('network_status'), PH_Page_Content);
  }
  
  document.getElementById(PH_Feed_Info).innerHTML = nlg_language['loading_feed_info']+"...";
  document.getElementById(PH_Server_Summary).innerHTML = nlg_language['loading_server_browser']+"...";
  
  getRequest(BuildURL('server_summary'), PH_Server_Summary);
  getRequest(BuildURL('feed_info'), PH_Feed_Info);
  
  // as of 1.0.0 - periodically refresh the Network Browser
  RefreshHandler = window.setTimeout('RefreshNetworkBrowser()', RefreshTime);
  
  // as of 1.0.2 - periodically refresh the Network Status page
  RefreshHandler_NS = window.setTimeout('RefreshNetworkStatus()', RefreshTime);
}

function SwitchToNetwork()
{
  /*
   * SwitchToNetwork()
   * Load network status
   */
  
  document.getElementById(PH_Server_Summary).innerHTML = nlg_language['loading_server_browser']+"...";
  document.getElementById(PH_Page_Content).innerHTML = nlg_language['loading_network_status']+"...";
  document.getElementById(PH_Feed_Info).innerHTML = nlg_language['loading_feed_info']+"...";
  
  getRequest(BuildURL('server_summary'), PH_Server_Summary);
  getRequest(BuildURL('network_status'), PH_Page_Content);
  getRequest(BuildURL('feed_info'), PH_Feed_Info);
  
  // as of 1.0.0 - reset and refresh the Network Browser
  window.clearTimeout(RefreshHandler);
  RefreshHandler = window.setTimeout('RefreshNetworkBrowser()', RefreshTime);
  
  // as of 1.0.2 - reset and refresh the Network Status
  window.clearTimeout(RefreshHandler_NS);
  RefreshHandler_NS = window.setTimeout('RefreshNetworkStatus()', RefreshTime);
}

function SwitchServer(sid)
{
  /*
   * SwitchServer()
   * Switch the main page view to server details for server 'sid'
   */
  
  document.getElementById(PH_Page_Content).innerHTML = nlg_language['refresh_server_details']+"...";

  getRequest(BuildURL('server_detail')+'&sid='+sid, PH_Page_Content);
  
  // as of 1.0.2 - cancel the 'network status' page refresh
  window.clearTimeout(RefreshHandler_NS);
}

function SwitchGroup(gid)
{
  /*
   * SwitchGroup()
   * Switch the current group of servers we're using
   * If parameter 'gid' is not given, will look the value from an element with ID 'gid'
   */

  var group_id;
  if (typeof gid == "undefined") {
    group_id = document.getElementById('gid').value;
  } else {
    group_id = gid;
  }
  
  if (group_id != "na")
  {
    CurrentGroupID = group_id;
    document.getElementById(PH_Server_Summary).innerHTML = nlg_language['loading_server_browser']+"...";
    document.getElementById(PH_Page_Content).innerHTML = nlg_language['loading_network_status']+"...";
    document.getElementById(PH_Feed_Info).innerHTML = nlg_language['loading_feed_info']+"...";

    getRequest(BuildURL('server_summary'), PH_Server_Summary);
    getRequest(BuildURL('network_status'), PH_Page_Content);
    getRequest(BuildURL('feed_info'), PH_Feed_Info);
    
    // as of 1.0.0 - reset and refresh the Network Browser
    window.clearTimeout(RefreshHandler);
    RefreshHandler = window.setTimeout('RefreshNetworkBrowser()', RefreshTime);
  }
}

function ApplyFilter(fid)
{
  /*
   * ApplyFilter()
   * Apply a filter to the host-list and re-load the initial page views
   * If parameter 'fid' is not given, will look the value from an element with ID 'fid'
   */

  var filter_id;
  if (typeof fid == "undefined") {
    filter_id = document.getElementById('fid').value;
  } else {
    filter_id = fid;
  }
  
  if (filter_id != "na")
  {
    // Bug fix in SVNr29 - if we change the filter, reset group
    CurrentGroupID = 0;
    CurrentFilterID = filter_id;

    document.getElementById(PH_Server_Summary).innerHTML = nlg_language['apply_server_filter']+"...";
    document.getElementById(PH_Page_Content).innerHTML = nlg_language['loading_network_status']+"...";
    document.getElementById(PH_Feed_Info).innerHTML = nlg_language['loading_feed_info']+"...";

    getRequest(BuildURL('server_summary'), PH_Server_Summary);
    getRequest(BuildURL('network_status'), PH_Page_Content);
    getRequest(BuildURL('feed_info'), PH_Feed_Info);
    
    // as of 1.0.0 - reset and refresh the Network Browser
    window.clearTimeout(RefreshHandler);
    RefreshHandler = window.setTimeout('RefreshNetworkBrowser()', RefreshTime);
  }
}

function ApplyTemplate(tname)
{
  /*
   * ApplyTemplate()
   * Apply a template to this session of NLG
   * If parameter 'tname' is not set, get it from an element with ID 'tname'
   */

  var template_name;
  if (typeof tname == "undefined") {
    template_name = document.getElementById('tname').value;
  } else {
    template_name = tname;
  }
  
  if (template_name != "na")
  {
    CurrentTemplate = template_name;
    document.getElementById('body').innerHTML = nlg_language['switch_page_template']+"...";
    document.location = "?template="+CurrentTemplate;
  }
}

function GetCurrentTemplate()
{
  /*
   * GetCurrentTemplate()
   * Reads the value from the query string "template" parameter
   * If 'template' is not set, use 'default' instead
   */
  
  var template_id;
  template_id = GetQS("gid");
  
  if (template_id == -1)
  {
    return "default";
  } else {
    return template_id;
  }
}

function GetQS(qsParameter)
{
  /*
   * GetQS()
   * Reads the value from the given query string parameter
   *
   * Added in v1.0.2
   */
  
  var queryString = window.location.search.substring(1);
  var queryStringParams = queryString.split("&");
  
  var return_value = -1;
  
  for (var item in queryStringParams)
  {
    var queryStringParam = queryStringParams[item].split("=");
    if (queryStringParam[0] == qsParameter) {
      return_value = queryStringParam[1];
    }
  }
  
  return return_value;
}

function GetQSGroup()
{
  /*
   * GetQSGroup()
   * Wrapper function to read the "gid" query string and return 0 if not found
   *
   * Added in v1.0.2
   */
  
  var group_id;
  group_id = GetQS("gid");
  
  if (group_id == -1)
  {
    return "0";
  } else {
    return group_id;
  }
}

function GetQSFilter()
{
  /*
   * GetQSFilter()
   * Wrapper function to read the "fid" query string and return 0 if not found
   *
   * Added in v1.0.2
   */
  
  var filter_id;
  filter_id = GetQS("fid");
  
  if (filter_id == -1)
  {
    return "0";
  } else {
    return filter_id;
  }
}
