#!/bin/bash

download_link='https://status.eduadmin.live.com/'
local_file='/var/www/html/nlg/sites/all/modules/sfsu/portlet/nlg/resources/html/liveatedu.html'
php_script='/var/www/html/nlg/sites/all/modules/sfsu/portlet/nlg/tests/nlg_liveatedu.test.php'

match_string="Failures: 0"

# Required to run the test
export PORTAL_HOME=/var/www/html/nlg/

curl -o ${local_file}.tmp $download_link
error_check=$(php $php_script | grep "$match_string")

if [ "$error_check" != "" ]; then
 cp ${local_file}.tmp $local_file
 exit 0
fi

exit 1

