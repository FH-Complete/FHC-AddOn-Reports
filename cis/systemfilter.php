<?php
/**
 * Interface für Management von Systemfiltern (Ansichten für Statistiken)
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/person.class.php');
require_once('../include/rp_system_filter.class.php');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if (!$rechte->isBerechtigt('addon/reports', null, 'suid'))
	die($rechte->errormsg);

if (!isset($_REQUEST["action"]))
	die("Keine Aktion spezifiziert!");

$action = $_REQUEST['action'];

$logged_person_id = null;
$person = new person();
if ($person->getPersonFromBenutzer($user))
{
	$logged_person_id = $person->person_id;
}

$systemfilter = new rp_system_filter();
$json = false;

switch($action)
{
	case 'getPreferences':
		if (isset($_GET["statistik_kurzbz"]) && isset($_GET['systemfilter_id']))
		{
			$systemfilter->load($_GET["statistik_kurzbz"], $logged_person_id, $_GET['systemfilter_id']);
			$json = $systemfilter->getPreferencesString();
			echo $json;
			die();
		}
		break;
	case 'savePrivate':
		if (isset($_POST["statistik_kurzbz"]) && isset($_POST["filter"]) && isset($logged_person_id))
		{
			$systemfilter->person_id = $logged_person_id;
			$systemfilter->statistik_kurzbz = $_POST["statistik_kurzbz"];
			$systemfilter->filter = $_POST["filter"];

			//update wenn systemfilter_id gesetzt
			if (isset($_POST["systemfilter_id"]))
			{
				$systemfilter_id = $_POST["systemfilter_id"];
				$systemfilterupdate = new rp_system_filter();
				$systemfilterupdate->load($systemfilter->statistik_kurzbz, $logged_person_id, $systemfilter_id);
				$systemfilterupdate->setPreferencesString($systemfilter->getPreferencesString());
				$json = $systemfilterupdate->save();
			}
			else
				$json = $systemfilter->save();
		}
		break;
	case 'deletePrivate':
		if (isset($_POST["systemfilter_id"]) && isset($logged_person_id))
		{
			$json = $systemfilter->delete($_POST["systemfilter_id"], $logged_person_id);
		}
		break;
	case 'setDefault':
		if (isset($_POST["systemfilter_id"]) && isset($_POST["default_filter"]) && isset($logged_person_id))
		{
			$default_filter = $_POST["default_filter"];
			if (!is_bool($default_filter))
			{
				if ($default_filter === 'true')
					$default_filter = true;
				else
					$default_filter = false;
			}

			if ($systemfilter->setDefault($_POST["systemfilter_id"], $logged_person_id, $default_filter))
				$json = true;
		}
		break;
}
echo json_encode($json);
die();
