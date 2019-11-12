<?php

//PHANTOM
define('PHANTOM_SERVER','http://phantomjs.example.com:3003');

//Dashboard verwenden Ja/Nein
define('DASHBOARD',false);

/*
Benutzerdefinierte Dashboards
Beispiel:
	serialize(
	array('lehre/lehrauftrag/Lehrauftrag/Dashboard' => array(
		array('chart_id' => 66, 'dashboard_layout' => 'full'),
		array('chart_id' => 67, 'dashboard_layout' => 'half'),
		array('chart_id' => 68, 'dashboard_layout' => 'third')
		)
	)
*/
define('CUSTOM_DASHBOARD', serialize(array()));
?>
