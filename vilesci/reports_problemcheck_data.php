<?php

require_once('../../../config/vilesci.config.inc.php');
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/rp_problemcheck.class.php');

/**
 */

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('addon/reports_verwaltung'))
	die($rechte->errormsg);

if (isset($_GET['action']))
{
	$problemcheck = new problemcheck();
	switch($_GET['action'])
	{
		case 'checkViews':
			echo $problemcheck->getViewData();
			break;
		case 'checkStatistics':
			echo $problemcheck->getStatistikData();
			break;
		case 'checkCharts':
			echo $problemcheck->getChartData();
			break;
	}
}
