<?php
/* Copyright (C) 2015 FH Technikum-Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Andreas Moik <moik@technikum-wien.at>
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/globals.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/statistik.class.php');
require_once('../include/chart.class.php');
require_once('../include/report.class.php');
require_once('../include/rp_gruppe.class.php');
require_once('../include/rp_gruppenzuordnung.class.php');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);


$rp_gruppe = new rp_gruppe();


$iconStatistik = "icon-fhc-statistik";
$iconReport = "icon-fhc-report";
$iconChart = "icon-fhc-chart";


if(!isset($_POST["action"]))
	die("Keine Aktion spezifiziert!");


$action = $_POST["action"];

switch($action)
{
	case "menueBaum":
	$rp_gruppe->loadAll();

	if(!$rp_gruppe->loadRecursive())
		returnAJAX(false, $rp_gruppe->errormsg);

	$treeDaten = $rp_gruppe->recursive;
	$treeDaten = processMenueLevel($treeDaten);

	returnAJAX(true, $treeDaten);
	break;

	case "alleDaten":
	//alle daten holen
	$alleDaten = array(
		array("text"=>"Reports", "children"=>array()),
		array("text"=>"Charts", "children"=>array()),
		array("text"=>"Statistiken", "children"=>array()),
	);


	$allReps = new Report();
	$allReps->getAll("title");
	foreach($allReps->result as $rp)
	{
		$n = array(
			"report_id" => $rp->report_id,
			"text" => $rp->title,
			"iconCls" => $iconReport,
		);

		$alleDaten[0]["children"][] = $n;
	}


	$allCharts = new chart();
	$allCharts->getAll("title");
	foreach($allCharts->result as $ch)
	{
		$n = array(
			"chart_id" => $ch->chart_id,
			"text" => $ch->title,
			"iconCls" => $iconChart,
		);

		$alleDaten[1]["children"][] = $n;
	}


	$allStat = new Statistik();
	$allStat->getAll("bezeichnung");
	foreach($allStat->result as $st)
	{
		$n = array(
			"statistik_kurzbz" => $st->statistik_kurzbz,
			"text" => $st->bezeichnung,
			"iconCls" => $iconStatistik,
		);

		$alleDaten[2]["children"][] = $n;
	}
	returnAJAX(true, $alleDaten);
	break;
	case "addEntityToMenue":
	if(isset($_POST["reportgruppe_id"]))
	{
		if(isset($_POST["report_id"]))
		{
			$report_id = $_POST["report_id"];
			$reportgruppe_id = $_POST["reportgruppe_id"];

			$gz = new rp_gruppenzuordnung();

			$gz->reportgruppe_id = $reportgruppe_id;
			$gz->chart_id = "";
			$gz->report_id = $report_id;
			$gz->statistik_kurzbz = "";
			$gz->insertvon = $user;

			if($gz->save())
				returnAJAX(true, "Erfolgreich");

			returnAJAX(false, "Fehler beim Insertieren!");
		}
		else if(isset($_POST["statistik_kurzbz"]))
		{
			$statistik_kurzbz = $_POST["statistik_kurzbz"];
			$reportgruppe_id = $_POST["reportgruppe_id"];

			$gz = new rp_gruppenzuordnung();

			$gz->reportgruppe_id = $reportgruppe_id;
			$gz->chart_id = "";
			$gz->report_id = "";
			$gz->statistik_kurzbz = $statistik_kurzbz;
			$gz->insertvon = $user;

			if($gz->save())
				returnAJAX(true, "Erfolgreich");

			returnAJAX(false, "Fehler beim Insertieren!");
		}
		else if(isset($_POST["chart_id"]))
		{
			$chart_id = $_POST["chart_id"];
			$reportgruppe_id = $_POST["reportgruppe_id"];

			$gz = new rp_gruppenzuordnung();

			$gz->reportgruppe_id = $reportgruppe_id;
			$gz->chart_id = $chart_id;
			$gz->report_id = "";
			$gz->statistik_kurzbz = "";
			$gz->insertvon = $user;

			if($gz->save())
				returnAJAX(true, "Erfolgreich");

			returnAJAX(false, "Fehler beim Insertieren!");
		}
	}


	returnAJAX(false, "Es ist ein Fehler aufgetreten");
	break;
	case "saveReportGruppe":
	if(isset($_POST["bezeichnung"]) && isset($_POST["reportgruppe_parent_id"]))
	{
		if(isset( $_POST["reportgruppe_id"]))
		{
			$rg = new rp_gruppe($_POST["reportgruppe_id"]);
			$rg->updatevon = $user;
		}
		else
		{
			$rg = new rp_gruppe();
			$rg->insertvon = $user;
		}

		$rg->bezeichnung = $_POST["bezeichnung"];
		$rg->reportgruppe_parent_id = $_POST["reportgruppe_parent_id"];

		if($rg->save())
			returnAJAX(true, "Erfolgreich");

		returnAJAX(false, "konnte nicht gespeichert werden");
	}


	returnAJAX(false, "Es ist ein Fehler aufgetreten");
	break;
	case "removeGruppenzuordung":
	if(isset($_POST["gruppenzuordnung_id"]))
	{
		$gz = new rp_gruppenzuordnung();
		if($gz->delete($_POST["gruppenzuordnung_id"]))
			returnAJAX(true, "Erfolgreich");
	}

	returnAJAX(false, "Es ist ein Fehler aufgetreten");
	break;



	case "removeReportgruppe":
	if(isset($_POST["reportgruppe_id"]))
	{
		$rg = new rp_gruppe();
		if($rg->delete($_POST["reportgruppe_id"]))
			returnAJAX(true, "Erfolgreich");
		else
			returnAJAX(false, "fehlgeschlagen");
	}

	returnAJAX(false, "Es ist ein Fehler aufgetreten");
	break;
	default:
	returnAJAX(false, "Es wurde keine Funktion angegeben");
}


function processMenueLevel($data)
{
	$gefiltert = array();

	foreach($data as $d)
	{
		$ent = array();

		if(isset($d->children) && count($d->children) > 0)
			$d->children = processMenueLevel($d->children);

		findZurodnung($d);

		if(isset($d->children) && count($d->children) > 0)
			$ent["children"] = $d->children;

		$ent["text"] = $d->bezeichnung;
		$ent["reportgruppe_id"] = $d->reportgruppe_id;
		$ent["reportgruppe_parent_id"] = $d->reportgruppe_parent_id;
		$gefiltert[] = $ent;
	}

	return $gefiltert;
}

function findZurodnung($entity)
{
	$rg = new rp_gruppe();
	$rg->getGruppenzuordnung($entity->reportgruppe_id);

	foreach($rg->gruppe as $g)
	{
		if($g->statistik_kurzbz != null)
		{
			$ns = new statistik($g->statistik_kurzbz);

			$neu["text"] = $ns->bezeichnung;
			$neu["iconCls"] = "icon-fhc-statistik";
			$neu["gruppenzuordnung_id"] = $g->gruppenzuordnung_id;

			$entity->children[] = $neu;
		}
		else if($g->report_id != null)
		{
			$nr = new report($g->report_id);

			$neu["text"] = $nr->title;
			$neu["iconCls"] = "icon-fhc-report";
			$neu["gruppenzuordnung_id"] = $g->gruppenzuordnung_id;

			$entity->children[] = $neu;
		}
		else if($g->chart_id != null)
		{
			$nc = new chart($g->chart_id);

			$neu["text"] = $nc->title;
			$neu["iconCls"] = "icon-fhc-chart";
			$neu["gruppenzuordnung_id"] = $g->gruppenzuordnung_id;

			$entity->children[] = $neu;
		}
	}
}







function returnAJAX($success, $obj)
{
	//if there is an error
	if(error_get_last())
		$ret = array(
		"erfolg" => false,
		);
	else if(!$success)
	{
		$ret = array(
		"erfolg" => false,
		"message" => $obj,
		);
	}
	//if we dont have a valid user
	else if (!$getuid = get_uid())
	{
		$ret = array(
		"erfolg" => false,
		);
	}
	//if everything worked fine
	else
	{
		$ret = array(
		"erfolg" => true,
		"user" => $getuid,
		"info" => $obj,
		);
	}
	echo json_encode($ret);
	die("");
}


?>
