<?php
// Copy to /home/<cpanel-user>/secure_backups/website-config.php on the server
// (outside public_html) and fill in the values. Never commit the real file.
//
// The GRAPH_* values are the same shared "JCCS Apps — noreply sender" values
// already in jccs-projects' config.php.

define('GRAPH_TENANT_ID', '');
define('GRAPH_CLIENT_ID', '');
define('GRAPH_CLIENT_SECRET', '');
define('MAIL_SENDER', 'noreply@jccs-services.com');
define('FROM_NAME', 'JCCS Services Website');

// Real, monitored inbox that receives quote requests.
define('QUOTE_TO', 'estimates@jccs-services.com');
