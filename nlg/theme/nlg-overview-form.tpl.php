<?php
// $Id$
?>
<div id="nlgmain">
<?php if (!empty($block->subject)): ?>
  <h2><?php print $block->subject ?></h2>
<?php endif;?>
  <div id="Content">
    <div id="Content_ServerList" style="display: block;">
      <div class="summarylist"><h3>System</h3></div>
      <div class="summarylist"><h3>Status</h3></div>
      <div class="summarylist"><h3>Metric Details</h3></div>
      <br />
      <div id="Content_ServerList_Data" style="display: block;">
        <?php if (!$nlg_systemerror) { ?>
          <?php foreach ($nlg_pollerobject as $server) { ?>
          <div class="Summary_Surround_<?php print $server->ServiceStatus; ?>">
            <div class="statuscolumn">
              <h4><?php print $server->HostName; ?></h4>
            </div>
            <div class="statuscolumn" style="background-image:url('<?php print $nlg_path; ?>/resources/images/metric_<?php print $server->ServiceStatus; ?>.gif'); background-repeat:no-repeat;">
              <p class="marginleft25 fontsize80"><?php print $server->StatusText; ?></p>
            </div>
            <div class="statuscolumn">
              <p><a href="#<?php print $server->HostID . '_MetricLink'; ?>" onclick="$('#<?php print $server->HostID; ?>_MetricList').toggle()" id="<?php print $server->HostID; ?>_MetricLink"><?php print $server->HostName; ?> Details</a></p>
            </div>
          </div>
          <?php } ?>
          <br />
        <div class="clearfloat"></div>
      </div>
      <div id="Content_Data">
        <div id="Content_Data_Data" style="display:block;">
          <?php foreach ($nlg_services as $key => $services) { ?>
          <div id="<?php print $nlg_pollerobject[$key]->HostID; ?>_MetricList" class="Server_MetricList">
            <table class="Server_Metric_Surround" summary="A list of services from Nagios Looking Glass and their status, when last checked, when the next check is at, and response time">
              <caption><?php print $nlg_pollerobject[$key]->HostName; ?> metric details</caption>
              <tr>
                <th>Service type</th>
                <th>Status</th>
                <th>Last check</th>
                <th>Next check at</th>
                <th>Notes</th>
              </tr>
              <?php foreach ($services as $service) { ?>
              <tr>
                <td><?php print $service->ServiceName; ?></td>
                <td><img src="<?php print $nlg_path; ?>/resources/images/metric_<?php print $service->metric_status; ?>.gif" alt="Status is <?php print $service->metric_status; ?>" />Status is <?php print $service->metric_status_text; ?></td>
                <td><?php print $service->metric_last_check; ?></td>
                <td><?php print $service->metric_next_check; ?></td>
                <td><?php print $service->CheckResult; ?></td>
              </tr>
              <?php } ?>
            </table>
            <div class="Mini_Line_Spacer"></div>
          </div>
          <?php
          }
        }
        else { ?>
          <p class="red bold"><?php print $nlg_systemerror; ?></p>
        <?php } ?>
        <noscript>
            <p><?php print t('nlg_no_javascript'); ?></p>
        </noscript>
      </div>
    </div>
  </div>
  <div id="Footer"></div>
  <div id="feed-info"></div>
    <p><?php print $nlg_feed_cached; ?></p>
</div>

