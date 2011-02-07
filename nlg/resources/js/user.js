/* Use this file for any custom JavaScript functions for your template */

var RefreshTime = 60000;//Moved here from the template file
var controller_status;

controller_status = "on";

function HideElement(element_id)
{
  /*
   * HideElement()
   * Set element_id's display style to 'none'
   */

  document.getElementById(element_id).style.display = "none";
}

function ShowElement_Block(element_id)
{
  /*
   * ShowElement_Block()
   * Set element_id's display style to 'block'
   */

  document.getElementById(element_id).style.display = "block";
}

function ShowElement_Inline(element_id)
{
  /*
   * ShowElement_Inline()
   * Set element_id's display style to 'inline'
   */

  document.getElementById(element_id).style.display = "inline";
}

function ToggleDisplay(element_id)
{
  /*
   * ToggleDisplay()
   * If element is already showing, hide it, if it isn't, show it
   */
  
  if (document.getElementById(element_id).style.display == "block")
  {
    HideElement(element_id);
  } else {
    ShowElement_Block(element_id);
  }
}

function ToggleMetricDisplay(metric_element, link_element)
{
  /*
   * ToggleMetricDisplay()
   * Show/hide the metrics display for a server, and change the link_element text
   */
  
  ToggleDisplay(metric_element);
  
  if (document.getElementById(link_element).innerHTML == nlg_language['metrics_expand'])
  {
    document.getElementById(link_element).innerHTML = nlg_language['metrics_collapse'];
  } else {
    document.getElementById(link_element).innerHTML = nlg_language['metrics_expand'];
  }
}

function ToggleUpdateDetail(detail_element, link_element)
{
  /*
   * ToggleUpdateDetail()
   * Show/hide the update details, and change the link_element text
   */
  
  ToggleDisplay(detail_element);
  
  if (document.getElementById(link_element).innerHTML == nlg_language['update_expand'])
  {
    document.getElementById(link_element).innerHTML = nlg_language['update_collapse'];
  } else {
    document.getElementById(link_element).innerHTML = nlg_language['update_expand'];
  }
}

function ToggleCommentDetail(detail_element, link_element)
{
  /*
   * ToggleCommentDetail()
   * Show/hide the comment details, and change the link_element text
   */
  
  ToggleDisplay(detail_element);
  
  if (document.getElementById(link_element).innerHTML == nlg_language['comment_expand'])
  {
    document.getElementById(link_element).innerHTML = nlg_language['comment_collapse'];
  } else {
    document.getElementById(link_element).innerHTML = nlg_language['comment_expand'];
  }
}

function ServerController(control_element, content_element, server_list)
{
  /*
   * ServerController()
   * If the server list is showing, hide it - if it's hiding, show it
   * Switch the controller image as required
   */
  
  ToggleDisplay(server_list);
  if (controller_status == "off")
  {
    controller_status = "on";
    document.getElementById(content_element).style.width = "73%";
    document.getElementById(control_element).src = "templates/default/images/serverlist_hide.gif";
  } else {
    controller_status = "off";
    document.getElementById(content_element).style.width = "99%";
    document.getElementById(control_element).src = "templates/default/images/serverlist_show.gif";
  }
}
