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
require_once('../include/rp_chart.class.php');
require_once('../include/rp_report.class.php');
require_once('../include/rp_gruppe.class.php');
require_once('../include/rp_attribut.class.php');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if (!$rechte->isBerechtigt('addon/reports', null, 's'))
	die ($rechte->errormsg);

$rp_gruppe = new rp_gruppe();
$rp_gruppe->loadAll();
$rp_gruppe->loadRecursive();
$daten = $rp_gruppe->recursive;

$attribute = new rp_attribut();
$attribute->loadAll();

$portalbeschreibung = array(); // Array mit HTML-formatiertem String zur Beschreibung der Startseite des Portals

//Das Attribut "_portalbeschreibung_" wird für die Startseite verwendet und wird deshalb rausgefiltert und in die Variable $portalbeschreibung geschrieben
foreach ($attribute->result AS $key => $attr)
{
	if ($attr->shorttitle['German'] == '_portalbeschreibung_')
	{
		$portalbeschreibung['German'] = $attr->description['German'];
		unset ($attribute->result[$key]);
	}
}

//Sortiert die Attribute alphabetisch
function sortAttributes($a, $b)
{
	return strcmp(strtolower($a->longtitle['German']), strtolower($b->longtitle['German']));
}
usort($attribute->result, "sortAttributes");

//Sortierfunktionen für das seitliche Menue
function titleSort($a, $b) {return strcmp($a->title, $b->title);}
function bezeichnungSort($a, $b) {return strcmp($a->bezeichnung, $b->bezeichnung);}

function getHtmlMenue($data, $rechte)
{
	$htmlstr = '';

	foreach($data as $d)
	{
		if(addZuordnungen($d, $rechte))
		{
			$anzahl=0;
			if(isset($d->charts))
				$anzahl += count($d->charts);
			if(isset($d->statistiken))
				$anzahl += count($d->statistiken);
			if(isset($d->reports))
				$anzahl += count($d->reports);

			$htmlstr.='<li><a style="font-weight: bold;" class="ddEntry" onclick="showSidebar('.$d->reportgruppe_id.', \'all\', \''.$d->bezeichnung.'\')"> '.$d->bezeichnung.'<span class="badge" style="margin-left:10px;">'.($anzahl).'</span></a></li>';
/*
			if(isset($d->charts) && count($d->charts)>0)
			{
				$htmlstr.='<li class="ddEntry" onclick="showSidebar('.$d->reportgruppe_id.', \'charts\', \''.$d->bezeichnung.'\')">&emsp;Charts<span class="badge" style="float:right; margin-right:10px;">'.count($d->charts).'</span></li>';
			}
			if(isset($d->statistiken) && count($d->statistiken)>0)
			{
				$htmlstr.='<li class="ddEntry" onclick="showSidebar('.$d->reportgruppe_id.', \'data\', \''.$d->bezeichnung.'\')">&emsp;Pivots<span class="badge" style="float:right; margin-right:10px;">'.count($d->statistiken).'</span></li>';
			}
			if(isset($d->reports) && count($d->reports)>0)
			{
				$htmlstr.='<li class="ddEntry" onclick="showSidebar('.$d->reportgruppe_id.', \'reports\', \''.$d->bezeichnung.'\')">&emsp;Reports<span class="badge" style="float:right; margin-right:10px;">'.count($d->reports).'</span></li>';
			}*/
		}
	}

	return $htmlstr;
}


function addZuordnungen($entity,$rechte)
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

		<link rel="stylesheet" type="text/css" href="../../../vendor/components/jqueryui/themes/base/jquery-ui.min.css">

		<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>

		<script type="text/javascript" src="../include/js/pivottable/pivot.js"></script>
		<script type="text/javascript" src="../include/js/pivottable/gchart_renderers.js"></script>

		<?php require_once("../include/meta/highcharts.php"); ?>
		<script type="text/javascript" src="../include/js/pivottable/jquery.ui.touch-punch.min.js"></script>
		<script src="../include/js/bootstrap.min.js"></script>
		<script src="../include/js/offcanvas.js"></script>
		<script type="text/javascript" src="reporting.js"></script>
		<script type="text/javascript">
		$(function()
		{
			$('.pagination a').on('click', function()
			{
				var letter = $(this).attr('name');
				$('#attr_list div').hide();
				$('.pagination li').removeClass('active');
				if (letter == 'Alle')
				{
					$('#attr_list div').show();
					$('.pagination li[name='+letter+']').addClass('active');
				}
				else
				{
					$('#attr_list div[name='+letter+']').show();
					$('.pagination li[name='+letter+']').addClass('active');
				}
			});
		});
		</script>
		<style>
			.FHCClickable
			{
				cursor: pointer;
			}
			.itemActive
			{
				background-color: #EEEEEE;
			}
			.ddEntry
			{
				cursor: pointer;
				padding-top: 3px;
				padding-bottom: 6px;
			}
			.ddEntry:hover
			{
				background-color: #333;
				color: #DDD;
				border-radius: 5px;
			}
			.col-sm-1, .col-sm-10, .col-sm-11, .col-sm-12, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9
			{
				float: right;
			}
			a.list-group-item:hover { background-color: #EEEEEE;}
			a.list-group-item:active { background-color: #EEEEEE;}
			a.ddEntry
			{
				cursor: pointer;
				padding-top: 3px;
				padding-bottom: 6px;
			}
			a.ddEntry:hover
			{
				background-color: #333;
				color: #DDD;
				border-radius: 5px;
			}
			.glyphicon-th
			{
				color: #FFE79F;
			}			
			.glyphicon-stats
			{
				color: #9FB7FF;
			}
			.glyphicon-file
			{
				color: #FF768C;
			}
		</style>
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
						<li>
							<a href="#data" id="glossar_link">Glossar</a>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<div id="spinner" style="display:none; width:80%; margin-left:10%; top:55px; position:absolute; z-index:10;">
			<div class="progress">
				<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
					Loading Data
				</div>
			</div>
		</div>

		<div class="container-fluid" style="margin-top:75px;">
			<div class="row row-offcanvas row-offcanvas-right">
				<div class="col-xs-12 col-sm-9">
					<p class="pull-right visible-xs">
						<button type="button" class="btn btn-primary btn-xs" data-toggle="offcanvas">Toggle nav</button>
					</p>
					<div id="welcome">
						<?php echo (isset ($portalbeschreibung['German'])?$portalbeschreibung['German']:''); //Welcome-text kommt aus Attribut "_portalbeschreibung_"?>
					</div>
					<div class="sidebar-offcanvas" role="navigation" style="float: left">
						<div class="list-group">
							<ul class="nav">
								<div id="maximize_sidebar_button" onclick="maximizeSidebar()" style="display: none;">
									<li><span class="glyphicon glyphicon-chevron-down"></span></li>
								</div>
							</ul>
							<!--<button id="maximize_sidebar_button"  style="box-sizing: border-box; position: relative; min-height: 1px; padding-right: 5px; padding-left: 5px; display: none;"><span class="glyphicon glyphicon-chevron-down"></span></button>-->
						</div>
					</div>
					<div id="filter" style="display: none;">
						<div class="col-xs-12 col-sm-9">
							<form class="form-inline" onsubmit="return false">
								<span id="filter-input"></span>
								<button style="display: inline;height:40px;" onclick="runFilter('html', true)" class="btn btn-default" type="submit">Ausf&uuml;hren</button>
								<button style="display: inline;height:40px;" onclick="runFilter('pdf', true)" id="filter-PdfLink" ><img src="../include/images/Pdf.svg" width="20" alt="pdf"/></button>
								<button style="display: inline;height:40px;color:red;" onclick="runFilter('debug', true)" id="filter-debugLink">DEBUG</button>
							</form>
						</div>
					</div>

					<div id="content" style="display:none;"></div>

					<div id="glossar" style="display:none;">
						<div class="page-header">
							<h2>Glossar</h2>
							<p>Begriffsdefinitionen</p>
							<ul class="pagination">
							<?php
								// Create an array with letters from A-Z
								$alphabeth_arr = array();
								$alphabeth_arr[] .= 'Alle';
								foreach (range('A', 'Z') as $char)
									$alphabeth_arr[] = $char;

								// Add ÄÖÜ to array
									$alphabeth_arr[] .= 'Ä';
									$alphabeth_arr[] .= 'Ö';
									$alphabeth_arr[] .= 'Ü';

								// Create an array with all occurring initial letters
								$letter_arr = array();
								foreach($attribute->result as $att)
								{
									$letter_arr[] = mb_strtoupper(mb_substr($att->longtitle["German"], 0, 1));
								}
								$letter_arr = array_unique($letter_arr);

								$letter = '';
								foreach ($alphabeth_arr AS $key => $value)
								{
									if (in_array($value, $letter_arr) || $value ==  'Alle')
									{
										if ($value == 'Alle')
											echo '<li class="active" name="'.$value.'"><a href="#" name="'.$value.'">'.$value.'</a></li>';
										elseif ($value == 'Ä')
											echo '<li name="AE"><a href="#" name="AE">'.$value.'</a></li>';
										elseif ($value == 'Ö')
											echo '<li name="OE"><a href="#" name="OE">'.$value.'</a></li>';
										elseif ($value == 'Ü')
											echo '<li name="UE"><a href="#" name="UE">'.$value.'</a></li>';
										else
											echo '<li name="'.$value.'"><a href="#" name="'.$value.'">'.$value.'</a></li>';
									}
									else
										echo '<li name="'.$value.'" class="disabled"><a href="#" name="'.$value.'">'.$value.'</a></li>';
								}
							?>

							</ul>
						</div>
						<div class="row" id="attr_list">
						<?php
							$name = '';
							foreach($attribute->result as $att)
							{
								if (mb_strtoupper(mb_substr($att->longtitle["German"], 0, 1)) == 'Ä')
									$name = 'AE';
								elseif (mb_strtoupper(mb_substr($att->longtitle["German"], 0, 1)) == 'Ö')
									$name = 'OE';
								elseif (mb_strtoupper(mb_substr($att->longtitle["German"], 0, 1)) == 'Ü')
									$name = 'UE';
								else
									$name = mb_strtoupper(mb_substr($att->longtitle["German"], 0, 1));
								echo '	<div class="col-12 col-sm-12 col-lg-12" name="'.$name.'">
											<h3>'.$att->longtitle["German"].'</h3>
											<p>'.$att->description["German"].'</p>
 											<p>Abkürzungen: '.$att->shorttitle["German"].', '.$att->middletitle["German"].'</p>
										</div>';
							}
						?>
						</div>
					</div>

				</div>


				<div class="col-xs-6 col-sm-3 sidebar-offcanvas" id="sidebar" role="navigation" style="float: right">
					<div class="list-group">
					<h4 id="titel_div"></h4>
						<ul class="nav">
							<?php foreach($daten as $l1):?>
								<?php if(isset($l1->children)):?>
									<?php foreach($l1->children as $l2):?>
										<?php if(isset($l2->statistiken) && count($l2->statistiken) > 0):?>
										<?php usort($l2->statistiken, "bezeichnungSort");?>
											<?php foreach($l2->statistiken as $st): ?>
												<div class="report_<?php echo $l2->reportgruppe_id ?>_data reports_sidebar_entry" style="display: none;">
													<li>
														<a 	id="list_item_statistik_<?php echo urlencode($st->statistik_kurzbz)?>" 
															class="list-group-item FHCClickable" 
															onclick='loadStatistik("<?php echo urlencode($st->statistik_kurzbz)?>", true)'>
															<span class="glyphicon glyphicon-th"></span>
															&nbsp;&nbsp;&nbsp;<?php echo $st->bezeichnung?>
														</a>
													</li>
												</div>
											<?php endforeach; ?>
										<?php endif;?>
										<?php if(isset($l2->charts) && count($l2->charts) > 0):?>
										<?php usort($l2->charts, "titleSort");?>
											<?php foreach($l2->charts as $ch):?>
												<div class="report_<?php echo $l2->reportgruppe_id ;?>_charts reports_sidebar_entry" style="display: none;">
													<li>
														<a 	id="list_item_chart_<?php echo urlencode($ch->chart_id)?>" 
															class="list-group-item FHCClickable" 
															onclick='loadChart(<?php echo urlencode($ch->chart_id)?>, "<?php echo urlencode($ch->statistik_kurzbz)?>", true)'>
															<span class="glyphicon glyphicon-stats"></span>
															&nbsp;&nbsp;&nbsp;<?php echo $ch->title?>
														</a>
													</li>
												</div>
											<?php endforeach; ?>
										<?php endif;?>
										<?php if(isset($l2->reports) && count($l2->reports) > 0):?>
										<?php usort($l2->reports, "titleSort");?>
											<?php foreach($l2->reports as $re): ?>
												<div class="report_<?php echo $l2->reportgruppe_id ?>_reports reports_sidebar_entry" style="display: none;">
													<li>
														<a 	id="list_item_report_<?php echo urlencode($re->report_id)?>" 
															class="list-group-item FHCClickable" onclick='loadReport(<?php echo urlencode($re->report_id)?>, true)'>
															<span class="glyphicon glyphicon-file"></span>
															&nbsp;&nbsp;&nbsp;<?php echo $re->title?>
														</a>
													</li>
												</div>
											<?php endforeach; ?>
										<?php endif;?>
									<?php endforeach;?>
								<?php endif;?>
							<?php endforeach;?>
							<li class="hide-button FHCClickable" onclick="minimizeSidebar()"><span class="glyphicon glyphicon-chevron-up"></span></li>
						</ul>
					</div>
				</div>
			</div>
		<!--<footer class="footer">
			<p class="text-muted">&copy; FH Technikum Wien 2015</p>
		</footer>-->
	</body>
</html>
