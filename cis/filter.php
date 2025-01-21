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
require_once('../include/rp_report.class.php');
require_once('../include/rp_chart.class.php');
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
$putlog = filter_input(INPUT_GET, 'putlog', FILTER_VALIDATE_BOOLEAN);
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
	//echo '<h4>Statistik '.$statistik->bezeichnung.'</h4>';

	//display description if content_id is set
	if (!is_null($statistik->content_id))
	{
		/*echo '
				<div class="row">
					<div class="col-xs-8">
						<div class="panel-group">
							<div class="panel panel-default">
								<div class="panel-heading" id="sysfilterblockheading">
									<a class="accordion-toggle collapsed" data-toggle="collapse" href="#collapseLegende">
										<div class="row">
											<div class="col-xs-9">
												<h1 class="panel-title" id="ansichtenverwaltentext">
													Statistik '.$statistik->bezeichnung.'
												</h1>
											</div>
											<div class="col-xs-3 text-right" style="white-space: nowrap">Details <span><i class="glyphicon glyphicon-chevron-down rotate-icon"></i></span></div>
										</div>
									</a>
								</div>
								<div class="panel-collapse collapse" id="collapseLegende">
									<div class="panel-body">
										<div class="embed-responsive embed-responsive-16by9">
											<iframe class="embed-responsive-item" src="'. APP_ROOT . 'cms/content.php?content_id=' . $statistik->content_id .'"></iframe>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>';*/
		echo '	
				<div class="row">
					<div class="col-xs-8">
						<div class="panel-group">
							<div class="panel" style="box-shadow: 0 1px 1px rgba(0,0,0,.20);">
								<div class="" id="sysfilterblockheading">
									<a class="accordion-toggle collapsed" data-toggle="collapse" href="#collapseLegende" style="color: inherit">
										<div class="row">
											<div class="col-xs-9">
												<h1 class="panel-title" id="ansichtenverwaltentext">
													Statistik '.$statistik->bezeichnung.'
												</h1>
											</div>
											<div class="col-xs-3 text-right" style="white-space: nowrap">Details <span><i class="glyphicon glyphicon-chevron-down rotate-icon"></i></span></div>
										</div>
									</a>
								</div>
								<div class="panel-collapse collapse" id="collapseLegende">
									<div class="panel-body">
										<div class="embed-responsive embed-responsive-16by9">
											<iframe class="embed-responsive-item" src="'.APP_ROOT.'cms/content.php?content_id='.$statistik->content_id.'"></iframe>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
				</div>';
	}
	else
	{
		//echo '<h4>Statistik '.$statistik->bezeichnung.'</h4>';
		echo '	
				<div class="row">
					<div class="col-xs-8">
						<div class="panel" style="box-shadow: 0 1px 1px rgba(0,0,0,.20);">
							<div class="" id="sysfilterblockheading">
								<div class="row">
									<div class="col-xs-9">
										<h1 class="panel-title" id="ansichtenverwaltentext">
											Statistik '.$statistik->bezeichnung.'
										</h1>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>';
	}

	// Filter parsen
	$filteranzahl = numberOfElements($vars);
	if ($filteranzahl > 0)
	{
		$html .=  '<div class="row"><div class="col-sm-12">';
	}
	foreach($vars as $var)
	{
		$html .= '<div class="form-group col-sm-2" style="float: left; width: auto">';
		if($filter->isFilter($var))
		{
			$bezeichnung = $filter->getBezeichnungFromKurzbz($var);
			$html .= '<label for="'.$var.'">';
			$html .= empty($bezeichnung) ? $var : $bezeichnung;
			$html .= ': </label>';
			$html .= $filter->getHtmlWidget($var);
		}
		else
		{
			// Checken, ob ein Filter mit diesem Namen als GET-Parameter übergeben wurde und diesen verwenden
			$val = '';
			if (isset($_GET[$var]))
			{
				$val = $_GET[$var];
			}
			$html .= '<label for="'.$var.'">';
			$html .= $var;
			$html .= ': </label>';
			$html .= ' <input class="form-control" type="text" id="'.$var.'" name="'.$var.'" value="'.$val.'">';
		}
		$html .= '</div>';
	}
	if ($filteranzahl > 0)
	{
		$html .=  '</div></div>';
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
	echo '<h4>Report '.$report->title.'</h4>';
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
		$vars = array_merge_recursive($vars, $s->parseVars($s->sql));
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
	if($putlog === true)
		$html .= '<input type="hidden" name="putlog" value="true">';

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
