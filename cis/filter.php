<?php
/* Copyright (C) 2014 fhcomplete.org
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
 * Authors: Andreas Österreicher <andreas.oesterreicher@technikum-wien.at>
 *			Andreas Moik <moik@technikum-wien.at>
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/globals.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/statistik.class.php');
require_once('../../../include/filter.class.php');
require_once('../include/report.class.php');
require_once('../include/chart.class.php');
require_once('../include/rp_report_chart.class.php');
require_once('../include/rp_report_statistik.class.php');

$db = new basis_db();

if(!$db)
{
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');
}

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

/*
if(!$rechte->isBerechtigt('basis/statistik'))
{
	die('Sie haben keine Berechtigung fuer diese Seite!');
}*/

$filter = new filter();
$filter->loadAll();
$statistik = new statistik();
$report = new report();

$statistik_kurzbz = filter_input(INPUT_GET, 'statistik_kurzbz');
$report_id = filter_input(INPUT_GET, 'report_id', FILTER_SANITIZE_NUMBER_INT);
$htmlbody = filter_input(INPUT_GET, 'htmlbody', FILTER_VALIDATE_BOOLEAN);
$html = '';

if(isset($statistik_kurzbz) && $statistik_kurzbz != 'undefined')
{
	if(!$statistik->load($statistik_kurzbz))
	{
		die('Statistik not found in DB!');
	}

	$vars = $statistik->parseVars($statistik->sql);


	if($htmlbody)
	{
		$html = '
			<!DOCTYPE HTML>
			<html>
				<head>
				<title>Filter</title>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
				<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
			</head>
			<body>';
	}

	// Filter parsen
	foreach($vars as $var)
	{
		if($filter->isFilter($var))
		{
			$html .= $var . ': ' . $filter->getHtmlWidget($var);
		}
		else
		{
			$html .= $var . ': <input type="text" id="' . $var . '" name="' . $var . '" value="">';
		}
	}

	if($htmlbody)
	{
		$html .= '</body></html>';
	}

}
else if(isset($report_id) && $report_id != 'undefined')
{
	if(!$report->load($report_id))
	{
		die('Report not found in DB!');
	}

	if($htmlbody)
	{
		$html = '
			<!DOCTYPE HTML>
			<html>
				<head>
				<title>Filter</title>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
				<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
			</head>
			<body>';
	}

	$rp_report_chart = new rp_report_chart();

	$rp_report_statistik = new rp_report_statistik();
	$statistiken = array();

	//alle statistiken zu einem report laden
	$rp_report_chart->getReportCharts($report_id);
	$rp_report_statistik->getReportStatistiken($report_id);

	foreach($rp_report_chart->result as $c)
	{
		$nc = new chart($c->chart_id);
		if(isset($nc->statistik_kurzbz))
		{
			$ns = new statistik($nc->statistik_kurzbz);
			$statistiken[] = $ns;
		}
	}

	foreach($rp_report_statistik->result as $s)
	{
		$ns = new statistik($s->statistik_kurzbz);
		$ns->reportstatistik_id = $s->reportstatistik_id;
		$statistiken[] = $ns;
	}

	$vars = array();
	foreach($statistiken as $s)
	{
		$vars= array_merge_recursive($vars, $s->parseVars($s->sql));
	}
	$vars = array_unique($vars, SORT_REGULAR);

	foreach($vars as $var)
	{
		if($filter->isFilter($var))
		{
			$html .= $var . ': ' . $filter->getHtmlWidget($var);
		}
		else
		{
			$html .= $var . ': <input type="text" id="' . $var . '" name="' . $var . '" value="">';
		}
	}

	if($htmlbody)
	{
		$html .= '</body></html>';
	}
}
else
{
	die('"statistik_kurzbz"/"report_id" is not set!');
}

echo $html;
