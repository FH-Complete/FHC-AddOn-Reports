<?php
/* Copyright (C) 2006 Technikum-Wien
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
 * Authors: Christian Paminger 	< christian.paminger@technikum-wien.at >
 * 			Robert Hofer <robert.hofer@technikum-wien.at>
 *			Andreas Moik <moik@technikum-wien.at>
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/globals.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/statistik.class.php');
require_once('../include/chart.class.php');
require_once('../include/report.class.php');
require_once('../include/rp_gruppe.class.php');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);


$rp_gruppe = new rp_gruppe();
$rp_gruppe->loadAll();
$rp_gruppe->loadRecursive();
$daten=$rp_gruppe->recursive;

//Sortierfunktionen für das seitliche Menue
function titleSort($a, $b) {return strcmp($a->title, $b->title);}
function bezeichnungSort($a, $b) {return strcmp($a->bezeichnung, $b->bezeichnung);}

function getHtmlMenue($data, $rechte)
{
	$htmlstr = '';

	foreach($data as $d)
	{
		if(addZurodnungen($d, $rechte))
		{
			$htmlstr.='<li><a style="font-weight: bold;"> '.$d->bezeichnung.'</a></li>';

			if(isset($d->charts) && count($d->charts)>0)
			{
				$htmlstr.='<li><a href="#" onclick="showSidebar('.$d->reportgruppe_id.', \'charts\')">&emsp;Charts&emsp;&emsp;<span class="badge">'.count($d->charts).'</span></a></li>';
			}
			if(isset($d->statistiken) && count($d->statistiken)>0)
			{
				$htmlstr.='<li><a href="#" onclick="showSidebar('.$d->reportgruppe_id.', \'data\')">&emsp;Pivot&emsp;&emsp;<span class="badge">'.count($d->statistiken).'</span></a></li>';
			}
			if(isset($d->reports) && count($d->reports)>0)
			{
				$htmlstr.='<li><a href="#" onclick="showSidebar('.$d->reportgruppe_id.', \'reports\')">&emsp;Reports&emsp;&emsp;<span class="badge">'.count($d->reports).'</span></a></li>';
			}
		}
	}

	return $htmlstr;
}


function addZurodnungen($entity,$rechte)
{
	$inhalt = false;
	$rg = new rp_gruppe();
	$rg->getGruppenzuordnung($entity->reportgruppe_id);

	foreach($rg->gruppe as $g)
	{
		if($g->statistik_kurzbz != null)
		{
			$st = new statistik($g->statistik_kurzbz);
			if($st->publish === true && ($rechte->isBerechtigt($st->berechtigung_kurzbz) || $st->berechtigung_kurzbz === null))
			{
				$inhalt = true;
				$entity->statistiken[] = $st;
			}
		}
		else if($g->report_id != null)
		{
			$rp = new report($g->report_id);
			if($rp->publish === true && ($rechte->isBerechtigt($rp->berechtigung_kurzbz) || $rp->berechtigung_kurzbz === null))
			{
				$inhalt = true;
				$entity->reports[] = $rp;
			}
		}
		else if($g->chart_id != null)
		{
			$ch = new chart($g->chart_id);
			if(isset($ch->statistik_kurzbz))
			{
				$ch->statistik = new statistik($ch->statistik_kurzbz);
				if($ch->publish === true && ($rechte->isBerechtigt($ch->statistik->berechtigung_kurzbz) || $ch->statistik->berechtigung_kurzbz === null))
				{
					$inhalt = true;
					$entity->charts[] = $ch;
				}
			}
		}
	}
	return $inhalt;
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="">
		<link rel="icon" href="../../../favicon.ico">

		<title>Reports</title>

		<link rel="stylesheet" type="text/css" href="../include/css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="../include/css/offcanvas.css">
		<link rel="stylesheet" type="text/css" href="../include/css/multilevel_dropdown.css">
		<link rel="stylesheet" type="text/css" href="reporting.css">
		<link rel="stylesheet" type="text/css" href="../include/js/pivottable/pivot.css">
		<link rel="stylesheet" href="../include/css/charts.css" type="text/css">
		<link rel="stylesheet" href="../include/css/jquery-ui.1.11.2.min.css" type="text/css">

		<script type="text/javascript" src="../include/js/jquery-1.11.2.min.js"></script>
		<script type="text/javascript" src="../include/js/jquery-ui.1.11.2.min.js"></script>

		<script type="text/javascript" src="../include/js/pivottable/pivot.js"></script>
		<script type="text/javascript" src="../include/js/pivottable/gchart_renderers.js"></script>

		<?php require_once("../include/meta/highcharts.php"); ?>
		<script type="text/javascript" src="../include/js/pivottable/jquery.ui.touch-punch.min.js"></script>
		<script src="../include/js/bootstrap.min.js"></script>
		<script src="../include/js/offcanvas.js"></script>
		<script type="text/javascript" src="reporting.js"></script>
	</head>

	<body>
		<div class="navbar navbar-fixed-top navbar-inverse" role="navigation">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="sr-only">Navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="">
						<div id="logo"></div>
						<div id="navBrand">Reporting</div>
					</a>
				</div>
				<div class="collapse navbar-collapse">
					<ul class="nav navbar-nav">
						<?php foreach($daten as $l1):?>
							<?php $dd =""; if(isset($l1->children))$dd = getHtmlMenue($l1->children, $rechte); ?>
							<?php if($dd != ""): ?>
							<li class="dropdown">
								<a href="#data" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"><?php echo $l1->bezeichnung?> <span class="caret"></span></a>
								<ul class="dropdown-menu multi-level" role="menu">

								<!-- Eine Vorschau auf multilevel_dropdown, falls es einmal benötigt werden sollte-->
								<!--
									<li class="dropdown-submenu">
										<a tabindex="-1" href="#">Mehr</a>
										<ul class="dropdown-menu">
											<li><a tabindex="-1" href="#">Test</a></li>
											<li class="dropdown-submenu">
												<a href="#">Mehr</a>
												<ul class="dropdown-menu">
													<li><a href="#">Test</a></li>
													</li>
												</ul>
											</li>
											<li><a href="#">Test</a></li>
										</ul>
									</li>
									-->
									<?php echo $dd; ?>
								</ul>
							</li>
							<?php endif;?>
						<?php endforeach;?>
					</ul>
				</div>
			</div>
		</div>

		<div id="spinner" style="display:none; width:80%; margin-left:10%; top:50px; position:absolute; z-index:10;">
			<div class="progress">
  				<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
					Loading Data
  				</div>
			</div>
		</div>

		<div class="container-fluid">
			<div class="row row-offcanvas row-offcanvas-right">
				<div class="col-xs-12 col-sm-9">
					<p class="pull-right visible-xs">
						<button type="button" class="btn btn-primary btn-xs" data-toggle="offcanvas">Toggle nav</button>
					</p>
					<div id="welcome">
						<div class="jumbotron">
							<h2>FH Technikum Wien</h2>
							<p>Sie befinden sich im Reporting-System der FH Technikum Wien. Das System ist in 3 Bereiche gegliedert.</p>
						</div>
						<div class="row">
							<div class="col-6 col-sm-6 col-lg-4">
								<h2>Data</h2>
								<p>Auswertungen werden in einer Tabelle mittels Zahlen gezeigt. Klassische Features wie Sortierten und Gruppieren sind hier möglich.</p>
							</div>
							<div class="col-6 col-sm-6 col-lg-4">
								<h2>Charts</h2>
								<p>Um Daten schneller zu Überblicken sind Charts sehr behilflich. Diversen Ansichten wie Bar-Diagramme oder Spidergraphs sind hier in Verwendung.</p>
							</div>
							<div class="col-6 col-sm-6 col-lg-4">
								<h2>Reports</h2>
								<p>Speziell angefertigte Reports sind ein Kombination aus Daten, Charts und Texten. Teilweise sind diese auch in anderen Formaten wie zB. PDF verfügbar.</p>
							</div>
						</div>
					</div>

					<div style="display: none;" id="filter">
						<div class="col-xs-12 col-sm-9">
							<form class="form-inline" onsubmit="return false">
								<span id="filter-input"></span>
								<button style="display: inline;height:40px;" onclick="runFilter('html')" class="btn btn-default" type="submit">Ausf&uuml;hren</button>
								<button style="display: inline;height:40px;" onclick="runFilter('pdf')" id="filter-PdfLink" ><img src="../cis/pdfIcon.png" width="20" alt="pdf"/></button>
								<button style="display: inline;height:40px;color:red;" onclick="runFilter('debug')" id="filter-debugLink">DEBUG</button>
							</form>
						</div>
					</div>

					<div id="content" style="display:none;"></div>

				</div>


				<div class="col-xs-6 col-sm-3 sidebar-offcanvas" id="sidebar" role="navigation">
					<div class="list-group">
						<ul class="nav">
							<?php foreach($daten as $l1):?>
										<?php if(isset($l1->children)):?>
											<?php foreach($l1->children as $l2):?>
														<?php if(isset($l2->statistiken) && count($l2->statistiken) > 0):?>
														<?php usort($l2->statistiken, "bezeichnungSort");?>
															<?php foreach($l2->statistiken as $st): ?>
																<div class="report_<?php echo $l2->reportgruppe_id ?>_data reports_sidebar_entry" style="display: none;">
																	<li><a href="#" onclick='loadStatistik("<?php echo urlencode($st->statistik_kurzbz)?>")' class="list-group-item"><?php echo $st->bezeichnung?></a></li>
																</div>
															<?php endforeach; ?>
														<?php endif;?>
														<?php if(isset($l2->charts) && count($l2->charts) > 0):?>
														<?php usort($l2->charts, "titleSort");?>
															<?php foreach($l2->charts as $ch):?>
																<div class="report_<?php echo $l2->reportgruppe_id ;?>_charts reports_sidebar_entry" style="display: none;">
																	<li><a href="#" onclick='loadChart(<?php echo urlencode($ch->chart_id)?>, "<?php echo urlencode($ch->statistik_kurzbz)?>")' class="list-group-item"><?php echo $ch->title?></a></li>
																</div>
															<?php endforeach; ?>
														<?php endif;?>
														<?php if(isset($l2->reports) && count($l2->reports) > 0):?>
														<?php usort($l2->reports, "titleSort");?>
															<?php foreach($l2->reports as $re): ?>
																<div class="report_<?php echo $l2->reportgruppe_id ?>_reports reports_sidebar_entry" style="display: none;">
																	<li><a class="list-group-item" href="#" onclick='loadReport(<?php echo urlencode($re->report_id)?>)'><?php echo $re->title?></a></li>
																</div>
															<?php endforeach; ?>
														<?php endif;?>
											<?php endforeach;?>
										<?php endif;?>
							<?php endforeach;?>
							<li class="hide-button" onclick="hideSidebar()"><a href="#"><span class="glyphicon glyphicon-chevron-up"></span></a></li>
						</ul>
					</div>
				</div>
			</div>
		<footer class="footer">
			<p class="text-muted">&copy; FH Technikum Wien 2015</p>
		</footer>
	</body>
</html>
