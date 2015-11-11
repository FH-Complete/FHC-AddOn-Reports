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


if(!isset($_REQUEST["action"]))
	die("Keine Aktion spezifiziert!");


$action = $_REQUEST["action"];


if($action === "menueBaum")
{
	$rp_gruppe->loadAll();
	$rp_gruppe->loadRecursive();
	$treeDaten = $rp_gruppe->recursive;

	foreach($treeDaten as $i)
	{
		if(isset($i->children) && $i->children !== null)
		{
			foreach($i->children as $j)
			{
				$j->children = array();

				$rp_gruppe->getGruppenzuordnung($j->reportgruppe_id);

				foreach($rp_gruppe->gruppe as $g)
				{
					if($g->statistik_kurzbz != null)
					{
						$ns = new statistik($g->statistik_kurzbz);

						$neu["text"] = $ns->bezeichnung;
						$neu["iconCls"] = $iconStatistik;
						$neu["gruppenzuordnung_id"] = $g->gruppenzuordnung_id;

						$j->children[] = $neu;
					}
					else if($g->report_id != null)
					{
						$nr = new report($g->report_id);

						$neu["text"] = $nr->title;
						$neu["iconCls"] = $iconReport;
						$neu["gruppenzuordnung_id"] = $g->gruppenzuordnung_id;

						$j->children[] = $neu;
					}
					else if($g->chart_id != null)
					{
						$nc = new chart($g->chart_id);

						$neu["text"] = $nc->title;
						$neu["iconCls"] = $iconChart;
						$neu["gruppenzuordnung_id"] = $g->gruppenzuordnung_id;

						$j->children[] = $neu;
					}
				}
			}
		}
	}
	returnAJAX(true, $treeDaten);
}
/*
//variante 1 ohne ordner
else if ($action === "alleDaten")
{
	//alle daten holen
	$alleDaten = array();


	$allReps = new Report();
	$allReps->getAll("title");
	foreach($allReps->result as $rp)
	{
		$n = array(
			"text" => $rp->title,
			"iconCls" => $iconReport,
		);

		$alleDaten[] = $n;
	}


	$allCharts = new chart();
	$allCharts->getAll("title");
	foreach($allCharts->result as $ch)
	{
		$n = array(
			"text" => $ch->title,
			"iconCls" => $iconChart,
		);

		$alleDaten[] = $n;
	}


	$allStat = new Statistik();
	$allStat->getAll("bezeichnung");
	foreach($allStat->result as $st)
	{
		$n = array(
			"text" => $st->bezeichnung,
			"iconCls" => $iconStatistik,
		);

		$alleDaten[] = $n;
	}
	returnAJAX(true, $alleDaten);
}

*/

//variante 2 mit ordner
else if ($action === "alleDaten")
{
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
}
else if ($action === "addEntityToMenue")
{
	if(isset($_REQUEST["reportgruppe_id"]))
	{
		if(isset($_REQUEST["report_id"]))
		{
			$report_id = $_REQUEST["report_id"];
			$reportgruppe_id = $_REQUEST["reportgruppe_id"];

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
		else if(isset($_REQUEST["statistik_kurzbz"]))
		{
			$statistik_kurzbz = $_REQUEST["statistik_kurzbz"];
			$reportgruppe_id = $_REQUEST["reportgruppe_id"];

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
		else if(isset($_REQUEST["chart_id"]))
		{
			$chart_id = $_REQUEST["chart_id"];
			$reportgruppe_id = $_REQUEST["reportgruppe_id"];

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
}
else if ($action === "saveReportGruppe")
{
	if(isset($_REQUEST["bezeichnung"]) && isset($_REQUEST["reportgruppe_parent_id"]))
	{
		if(isset( $_REQUEST["reportgruppe_id"]))
		{
			$rg = new rp_gruppe($_REQUEST["reportgruppe_id"]);
			$rg->updatevon = $user;
		}
		else
		{
			$rg = new rp_gruppe();
			$rg->insertvon = $user;
		}

		$rg->bezeichnung = $_REQUEST["bezeichnung"];
		$rg->reportgruppe_parent_id = $_REQUEST["reportgruppe_parent_id"];

		if($rg->save())
			returnAJAX(true, "Erfolgreich");

		returnAJAX(false, "konnte nicht gespeichert werden");
	}


	returnAJAX(false, "Es ist ein Fehler aufgetreten");
}
else if ($action === "removeGruppenzuordung")
{
	if(isset($_REQUEST["gruppenzuordnung_id"]))
	{
		$gz = new rp_gruppenzuordnung();
		if($gz->delete($_REQUEST["gruppenzuordnung_id"]))
			returnAJAX(true, "Erfolgreich");
	}

	returnAJAX(false, "Es ist ein Fehler aufgetreten");
}
else if ($action === "removeReportgruppe")
{
	if(isset($_REQUEST["reportgruppe_id"]))
	{
		$rg = new rp_gruppe();
		if($rg->delete($_REQUEST["reportgruppe_id"]))
			returnAJAX(true, "Erfolgreich");
		else
			returnAJAX(false, "fehlgeschlagen");
	}

	returnAJAX(false, "Es ist ein Fehler aufgetreten");
}
else
{
	returnAJAX(false, "Es wurde keine Funktion angegeben");
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
