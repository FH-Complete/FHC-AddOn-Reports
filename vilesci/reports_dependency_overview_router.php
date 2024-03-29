<?php

require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/rp_dependency_overview.class.php');

/**
 * Liefert korrekte Abhängigkeiten je nach angefragtem Objekt (z.B. Viewabhängigkeiten, Statistikabhängigkeiten...)
 */

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
	$dependencies = array();

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
		case 'getAnsichtDependencies':
			if ($object_id == "getLongerNotUsedDependencies")
				$dependencies = $dependency_helper->getLongerNotUsedDependencies();
			break;
	}

	echo json_encode($dependencies);
}
