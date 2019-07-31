<?php

require_once('../../../config/vilesci.config.inc.php');
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/rp_dependency_overview.class.php');

/**
 */

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('addon/reports_verwaltung'))
	die($rechte->errormsg);

if (isset($_REQUEST['action']) && isset($_REQUEST['object_id']))
{
	$action = $_REQUEST['action'];
	$object_id = $_REQUEST['object_id'];

	$dependency_helper = new dependency_overview();

	switch($action)
	{
		case 'getViewDependencies':
			if ($object_id == "getAllDependencies")
				$dependencies = $dependency_helper->getAllViewDependencies();
			else
			{
				$dependencies = array();
				$dependencies[] = $dependency_helper->getViewDependencies($object_id);
			}
			break;
		case 'getStatistikGroupDependencies':
				$dependencies = $dependency_helper->getStatistikGroupDependencies($object_id);
			break;
		case 'getMenuGroupDependencies':
			if ($object_id == "getAllDependencies")
				$dependencies = $dependency_helper->getAllMenuGroupDependencies();
			else
				$dependencies = $dependency_helper->getMenuGroupDependencies($object_id);
			break;
	}

	echo json_encode($dependencies);
}
