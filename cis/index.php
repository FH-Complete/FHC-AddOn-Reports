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



$buf = $rp_gruppe->result;

for($i = 0; $i < count($rp_gruppe->result); $i++)
{
	$gruppe = new rp_gruppe();
	$gruppe->getGruppenzuordnung($buf[$i]->reportgruppe_id);

	$buf[$i]->gruppe = $gruppe->gruppe;
	$buf[$i]->statistiken = 0;
	$buf[$i]->reports = 0;
	$buf[$i]->charts = 0;

	$buf[$i]->statistik = array();
	$buf[$i]->report = array();
	$buf[$i]->chart = array();

	foreach($buf[$i]->gruppe as $gr)
	{
		if(isset($gr->statistik_kurzbz))
		{
			$ns = new statistik($gr->statistik_kurzbz);
			if($ns->publish === true && ($rechte->isBerechtigt($ns->berechtigung_kurzbz) || $ns->berechtigung_kurzbz === null))
			{
				$buf[$i]->statistiken ++;
				$buf[$i]->statistik[] = $ns;
			}
		}
		else if(isset($gr->report_id))
		{
			$nr = new report($gr->report_id);
			if($nr->publish === true && ($rechte->isBerechtigt($nr->berechtigung_kurzbz) || $nr->berechtigung_kurzbz === null))
			{
				$buf[$i]->reports ++;
				$buf[$i]->report[] = $nr;
			}
		}
		else if(isset($gr->chart_id))
		{
			$nc = new chart($gr->chart_id);

			if(isset($nc->statistik_kurzbz))
			{
				$nc->statistik = new statistik($nc->statistik_kurzbz);
				if($nc->publish === true && ($rechte->isBerechtigt($nc->statistik->berechtigung_kurzbz) || $nc->statistik->berechtigung_kurzbz === null))
				{
					$buf[$i]->charts ++;
					$buf[$i]->chart[] = $nc;
				}
			}
		}
	}


	if(!is_null($rp_gruppe->result[$i]->reportgruppe_parent_id))
	{
		$found = false;
		foreach($buf as $ent)
		{
			if($buf[$i]->reportgruppe_parent_id === $ent->reportgruppe_id)
			{
				$found = true;

				if(!isset($ent->sub))
					$ent->sub = array();

				$ent->sub[] = $buf[$i];
			}
		}
		if($found)
		{
			unset($buf[$i]);
		}
	}
}
$daten = $buf;

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

		<script src="../include/js/highcharts/highcharts-custom.js" type="application/javascript"></script>
		<script src="../include/js/highcharts/main.js" type="application/javascript"></script>
		<script type="text/javascript" src="../include/js/pivottable/jquery.ui.touch-punch.min.js"></script>
		<script src="../include/js/bootstrap.min.js"></script>
		<script src="../include/js/offcanvas.js"></script>
		<script type="text/javascript" src="reporting.js"></script>
	</head>

	<body>
		<?php $reports = array(); ?>
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
						<img src="../../../skin/styles/tw/logo_ws_50x25.png" height="25" />
						<span>Reporting</span>
					</a>
				</div>
				<div class="collapse navbar-collapse">
					<ul class="nav navbar-nav">
						<?php foreach($daten as $l1):?>
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

									<?php if(isset($l1->sub)):?>
										<?php foreach($l1->sub as $l2):
										$reports[] = $l2;	//jeder report wird in $reports gespeichert, um später bei den menüpunkten nicht noch einmal alles durchloopen zu müssen
										$empty = true;?>
														<li><a style="font-weight: bold;"><?php echo $l2->bezeichnung;?></a></li>
													<?php if($l2->statistiken > 0):$empty = false;?>
														<li><a href="#" onclick='showSidebar(<?php echo $l2->reportgruppe_id;?>, "data")'>&emsp;Pivot&emsp;&emsp;<span class="badge"><?php echo $l2->statistiken?></span></a></li>
													<?php endif;?>
													<?php if($l2->charts > 0): $empty = false; $l2->typ = 'chart';?>
														<li><a href="#" onclick='showSidebar(<?php echo $l2->reportgruppe_id ?>, "charts")'>&emsp;Charts&emsp;&emsp;<span class="badge"><?php echo $l2->charts?></span></a></li>
													<?php endif;?>
													<?php if($l2->reports > 0): $empty = false; $l2->typ = 'report';?>
														<li><a href="#" onclick='showSidebar(<?php echo $l2->reportgruppe_id ?>, "reports")'>&emsp;Reports&emsp;&emsp;<span class="badge"><?php echo $l2->reports?></span></a></li>
													<?php endif;?>
										<?php endforeach;?>
										<?php if($empty):?>
											<li><a href="#">&emsp;Keine Einträge vorhanden</a></li>
										<?php endif;?>
									<?php else:?>
										<li><a href="#">Keine Einträge vorhanden</a></li>
									<?php endif;?>
								</ul>
							</li>
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
							<div id="filter-input" style="float: left"></div>
								<button style="float: left;height:40px;" onclick="runFilter('html')" class="btn btn-default" type="submit">Run</button>
								<button style="float: left;height:40px;" onclick="runFilter('pdf')" id="filter-PdfLink" ><img src="../cis/pdfIcon.png" width="20" alt="pdf"/></button>
							</form>
						</div>
					</div>

					<div id="content" style="display:none;"></div>

				</div>
				<div class="col-xs-6 col-sm-3 sidebar-offcanvas" id="sidebar" role="navigation">
					<div class="list-group">
						<ul class="nav">
							<?php foreach($reports as $re):?>
								<?php foreach($re->gruppe as $gp):?>

										<?php if(isset($gp->chart_id)):?>
											<?php $ch = new chart($gp->chart_id);?>
											<div class="report_<?php echo $gp->reportgruppe_id ?>_charts reports_sidebar_entry" style="display: none;">
												<li><a href="#" onclick='loadChart(<?php echo urlencode($ch->chart_id)?>, "<?php echo urlencode($ch->statistik_kurzbz)?>")' class="list-group-item"><?php echo $ch->title?></a></li>
											</div>

										<?php elseif(isset($gp->report_id)): ?>
											<?php $rp = new report($gp->report_id);?>
											<div class="report_<?php echo $gp->reportgruppe_id ?>_reports reports_sidebar_entry" style="display: none;">
												<li><a class="list-group-item" href="#" onclick='loadReport(<?php echo urlencode($rp->report_id)?>)'><?php echo $rp->title?></a></li>
											</div>

										<?php elseif(isset($gp->statistik_kurzbz)): ?>
											<?php $st = new statistik($gp->statistik_kurzbz); ?>
											<div class="report_<?php echo $gp->reportgruppe_id ?>_data reports_sidebar_entry" style="display: none;">
												<li><a href="#" onclick='loadStatistik("<?php echo urlencode($st->statistik_kurzbz)?>")' class="list-group-item"><?php echo $st->bezeichnung?></a></li>
											</div>

										<?php endif; ?>
								<?php endforeach; ?>
							<?php endforeach; ?>
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
