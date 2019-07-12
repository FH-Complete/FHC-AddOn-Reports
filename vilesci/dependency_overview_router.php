<?php

require_once('../../../config/vilesci.config.inc.php');
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/dependency_overview.class.php');

/**
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
	$action = $_REQUEST['action'];

	switch($action)
	{
		case 'getViewDependencies':
			if (isset($_REQUEST['view_id']))
			{
				$view_id = $_REQUEST['view_id'];

				$dependency_helper = new dependency_overview();

				$dependencies = array();
				$dependencies[] = $dependency_helper->getViewDependencies($view_id);

				echo json_encode($dependencies);
			}
			break;
		case 'getGroupDependencies':
			if (isset($_REQUEST['groupname']))
			{
				$groupname = $_REQUEST['groupname'];

				$dependency_helper = new dependency_overview();

				$dependencies = $dependency_helper->getGroupDependencies($groupname);

				echo json_encode($dependencies);
			}
			break;
	}
}
