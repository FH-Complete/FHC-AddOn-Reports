<?php

require_once('../../../config/vilesci.config.inc.php');
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/rp_problemcheck.class.php');

/**
 * Liefert korrekte Problemchek Issues je nach angefragtem Objekt (z.B. Viewissues, Statistikissues...)
 */

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('addon/reports_verwaltung'))
	die($rechte->errormsg);

if (isset($_REQUEST['action']))
{
	$problemcheck = new problemcheck();
	switch($_REQUEST['action'])
	{
		case 'getViewIssues':
			$view_ids = isset($_REQUEST['view_ids']) ? $_REQUEST['view_ids'] : null;
			echo $problemcheck->getViewIssues($view_ids);
			break;
		case 'getStatistikIssues':
			$statistik_ids = isset($_REQUEST['statistik_ids']) ? $_REQUEST['statistik_ids'] : null;
			echo $problemcheck->getStatistikIssues($statistik_ids);
			break;
		case 'getChartIssues':
			$chart_ids = isset($_REQUEST['chart_ids']) ? $_REQUEST['chart_ids'] : null;
			echo $problemcheck->getChartIssues($chart_ids);
			break;
	}
}
