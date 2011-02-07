var NumAsyncRequests = 10;

function getHTTPrequest() {
  if (window.XMLHttpRequest) {
    return new XMLHttpRequest(); // Not IE
  } else if(window.ActiveXObject) {
    return new ActiveXObject("Microsoft.XMLHTTP"); // IE
  } else {
    alert("Your browser doesn't support the XmlHttpRequest object - consider upgrading to a Web 2.0-compliant browser.");
  }
}

var receiveReq = new Array(NumAsyncRequests);
var target_object = new Array(NumAsyncRequests);

for (var x = 0; x < NumAsyncRequests; x++)
{
  receiveReq[x] = getHTTPrequest();  
  target_object[x] = "no_target";
}

function getRequest(page_to_request, target_container)
{
  var y = -1;
  
  // find a free XML object to use
  for (var x = 0; x < NumAsyncRequests; x++)
  {
    if (y == -1)
    {
      if (receiveReq[x].readyState == 4 || receiveReq[x].readyState == 0)
      {
        y = x;
      }
    }
  }
  
  if (y == -1)
  {
    document.getElementById(target_container).innerHTML = "Too many concurrent XML requests.";
    return false;
  }
  
  // if the XML HTTP object is not in the middle of a request, start a new request
  target_object[y] = target_container;
  receiveReq[y].open("GET", page_to_request, true);
  receiveReq[y].onreadystatechange = function() { _callback_getRequest(y); };
  // parameters enclosed in () for a POST request
  receiveReq[y].send(null);
}

function postRequest(page_to_request, target_container, send_parameters)
{
  // if the XML HTTP object is not in the middle of a request, start a
  // new request
  if (receiveReq.readyState == 4 || receiveReq.readyState == 0)
  {
    target_object = target_container

      receiveReq.open("POST", page_to_request, true);
      receiveReq.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
      receiveReq.onreadystatechange = _callback_getRequest;
      receiveReq.send(send_parameters);
  }
}

function _callback_getRequest(object_id)
{
  if (receiveReq[object_id].readyState == 4)
  {
    document.getElementById(target_object[object_id]).innerHTML = receiveReq[object_id].responseText.substring(receiveReq[object_id].responseText.indexOf('Status</h2>') + 11,receiveReq[object_id].responseText.indexOf('<!-- x main -->'));
  }
}
