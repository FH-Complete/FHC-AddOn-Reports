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
 *			Robert Hofer <robert.hofer@technikum-wien.at>
 *					Andreas Moik <moik@technikum-wien.at>
 */

require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/globals.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/statistik.class.php');
require_once('../include/rp_chart.class.php');

$messages = array();
$error = false;

function addMsg(&$errors, $msg, $color = "#070")
{
	$e = new stdClass();
	$e->msg = $msg;
	$e->color = $color;
	$errors[] = $e;
}


?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>

<?php
if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('addon/reports_verwaltung'))
	die($rechte->errormsg);


$reload = false;  // neuladen der liste im oberen frame
$sel = '';
$chk = '';

$chart = new chart();
$chart->chart_id		= 0;
$chart->title 			= 'NewChart';
$chart->description		= '';
$chart->type			= '';
$chart->sourcetype		= '';
$chart->preferences		= '';
$chart->datasource		= '';
$chart->datasource_type	= '';
$chart->insertvon		= $user;
$chart->updatevon		= $user;
$chart->publish			= false;

if(isset($_REQUEST["save"]) && isset($_REQUEST["chart_id"]))
{
	if(!$rechte->isBerechtigt('addon/reports_verwaltung', null, 'suid'))
		die($rechte->errormsg);

	// echo 'DI_ID: '.var_dump((int)$_POST["chart_id"]);
	// Wenn id > 0 ist -> Neuer Datensatz; ansonsten load und update
	if ( ((int)$_REQUEST["chart_id"]) > 0)
		$chart->load((int)$_REQUEST["chart_id"]);
	if ($_REQUEST["save"])
	{
		if(!isset($_POST["preferences"]))
		{
			addMsg($messages, "Keine Preferences gesetzt!", "#700");
			$error = true;
		}
		if(!isset($_POST["statistik_kurzbz"]) || $_POST["statistik_kurzbz"] == "false")
		{
			addMsg($messages, "Keine Statistik gesetzt!", "#700");
			$error = true;
		}

		if(!$error)
		{
			$chart->title = $_POST["title"];
			$chart->longtitle = $_POST["longtitle"];
			$chart->description = $_POST["description"];
			$chart->type = $_POST["type"];
			$chart->sourcetype = $_POST["sourcetype"];
			$chart->preferences = $_POST["preferences"];
			$chart->statistik_kurzbz = $_POST["statistik_kurzbz"];
			$chart->datasource = $_POST["datasource"];
			$chart->datasource_type = $_POST["datasource_type"];
			$chart->publish = (bool) $_POST["publish"];
			$chart->dashboard = (bool) $_POST["dashboard"];

			if($chart->dashboard)
			{
				$chart->dashboard_layout = $_POST["dashboard_layout"];
				$chart->dashboard_pos = (int) $_POST["dashboard_pos"];
			}

			if(!$chart->save())
			{
				addMsg($messages, $chart->errormsg, "#700");
				$error = true;
			}
			else
			{
				addMsg($messages, "Datensatz ge&auml;ndert!&nbsp;&nbsp");
			}

			$reload = true;
		}
	}
}

if ((isset($_REQUEST['chart_id'])) && ((!isset($_REQUEST['neu'])) || ($_REQUEST['neu']!= "true")))
{
	//echo 'loadChart';
	$chart->load($_REQUEST["chart_id"]);
	if ($chart->errormsg!='')
		die($chart->errormsg);
}
?>
		<title>DI-Quelle - Details</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<?php require_once("../../../include/meta/jquery.php"); ?>
		<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
		<script src="../../../include/js/mailcheck.js"></script>
		<script src="../../../include/js/datecheck.js"></script>
		<?php require_once("../../../include/meta/jsoneditor.php"); ?>
		<script type="text/javascript">
			var charts = {
				types: <?php echo json_encode(chart::getPlugins()) ?>,
				default_preferences: <?php echo json_encode(chart::getDefaultPreferences()) ?>
			};

			var editor = false;
			var chartJson = <?php echo (isset($chart->preferences) && $chart->preferences ? $chart->preferences : "{chart:{}}");?>;

			$(function()
			{
				var options =
				{
					mode: 'code',
					modes: ['code', 'form', 'text', 'tree', 'view'], // allowed modes
					onError: function (err)
					{
						console.log(err.toString());
					},
				};

				var container = document.getElementById('jsoneditor');
				editor = new JSONEditor(container, options);
				editor.set(chartJson);
			});
		</script>
		<style>
			#jsoneditor
			{
				width:  100%;
				height: 95%;
			}
			.kopf_r
			{
				position:absolute;
				top:10px;
				right:10px;
				border-color:#777777;
				border-style:solid;
				border-width:1px;
				background-color:#eeeeee;
				padding:2px;
				height: 90%;
			}
		</style>
	</head>
	<body style="background-color:#eeeeee;">
		<?php if($chart->chart_id > 0): ?>
			<br><div class="kopf">Chart <b><?php echo $chart->chart_id ?></b></div>
		<?php else: ?>
			<br><div class="kopf">Neuer Chart</div>
		<?php endif;?>
		<div class="kopf_r" style="visibility:<?php echo (!empty($messages) ? 'visible': 'hidden'); ?>">
			<?php foreach($messages as $m): ?>
			<div id="submsg" style="color:<?php echo $m->color; ?>;"><?php echo $m->msg; ?></div>
			<?php endforeach; ?>
		</div>
		<form action="chart_details.php" method="POST" name="chartform" onsubmit="return appendChartData()">
			<table style="height: 95%;" class="detail">
					<tr>
						<td>
							Title
						</td>
						<td>
							<input class="detail" type="text" style="width: 100%;" name="title" size="22" maxlength="64" value="<?php echo $chart->title ?>">
						</td>
						<td colspan="2" rowspan="14" valign="top" style="width: 50%; padding-left: 10px;">
							Preferences
							<div id="jsoneditor"></div>
						</td>
					</tr>

					<tr>
						<td>
							Long Title
						</td>
						<td>
							<input class="detail" type="text" style="width: 100%" name="longtitle" size="22" maxlength="128" value="<?php echo $chart->longtitle ?>">
						</td>
					</tr>

					<tr>
						<td>Datasource Type</td>
						<td colspan="1">
							<select name="datasource_type" id="datasource_type" style='max-width:150px;'>
								<?php foreach(chart::getDataSourceTypes() as $abk => $datasource_type): ?>
									<option value="<?php echo $abk ?>"<?php echo ($chart->datasource_type === $abk ? ' selected' : '') ?>>
										<?php echo $datasource_type ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					
					<tr>
						<td valign="top">Statistik</td>
						<td valign="top">
							<?php $statistik = new statistik; ?>
							<?php $statistik->getAll('bezeichnung'); ?>
							<select id="statistik_kurzbz" name="statistik_kurzbz" id="statistik_kurzbz" style='max-width:150px;'>
								<option value="false">Keine Auswahl</option>
								<?php foreach($statistik->result as $stat): ?>
									<option value="<?php echo $stat->statistik_kurzbz ?>"<?php echo ($chart->statistik_kurzbz === $stat->statistik_kurzbz ? ' selected' : '') ?>><?php echo $stat->bezeichnung ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					
					<tr>
						<td valign="top" class="datasource">DataSource</td>
						<td valign="top" class="datasource" colspan="1">
						<input id="datasource" class="detail" style="width: 100%;" type="text" name="datasource" size="55" maxlength="256" value="<?php echo $chart->datasource ?>">
						</td>
					</tr>
					
					<tr>
						<td valign="top">Description</td>
						<td valign="top"><textarea name="description" cols="70" rows="6"><?php echo $chart->description ?></textarea></td>
						
					</tr>
					
					<tr>
						<td>
							Type
						</td>
						<td>
							<select name="type" id="chart_type">
								<option value=""></option>
								<?php foreach(chart::getPlugins() as $abk => $plugin): ?>
									<option value="<?php echo $abk ?>"<?php echo ($chart->type === $abk ? ' selected' : '') ?>>
										<?php echo $plugin ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>	
					
					<tr>
						<td>
							SourceType
						</td>
						<td>
							<input class='detail' type='text' style="width: 100%" name='sourcetype' size='8' maxlength='16' value='<?php echo $chart->sourcetype ?>'>
						</td>
					</tr>
					
					<tr>
						<td>
						</td>
					</tr>
					
					<tr>
						<td valign="top">
							Publish
							<input type="hidden" name="publish" value="0" />
							<input type="checkbox" name="publish" value="1"<?php echo $chart->publish ? ' checked' : '' ?> />
						</td>
						<td>
						</td>
					</tr>
					<tr>
						<td valign="top">Dashboard</td>
						<td>
							<input type="hidden" name="dashboard" value="0" />
							<input type="checkbox" name="dashboard" id="dashboard" value="1"<?php echo $chart->dashboard ? ' checked' : '' ?> />
						</td>
					</tr>
					<tr class="dashboard-details">
						<td valign="top">Layout</td>
						<td>
							<select name="dashboard_layout" id="layout">
								<option value=""></option>
								<?php foreach(chart::getDashboardLayouts() as $layout_id => $layout_bez): ?>
									<option value="<?php echo $layout_id ?>"<?php echo ($chart->dashboard_layout === $layout_id ? ' selected' : '') ?>>
										<?php echo $layout_bez ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr class="dashboard-details">
						<td valign="top">Dashboard Position</td>
						<td>
							<input type="number" name="dashboard_pos" value="<?php echo $chart->dashboard_pos ?>" />
						</td>
					</tr>
					<tr>
						<td style="height: 100%"></td>
						<td></td>
					</tr>
					<tr align="right">
						<td colspan="4">
							<input type="hidden" name="chart_id" value="<?php echo $chart->chart_id ?>">
							<input type="submit" value="Speichern" name="save">
						</td>
					</tr>
			</table>
			<br>
		</form>
		<script>
		</script>
		<?php if($reload): ?>
			<script type='text/javascript'>
				parent.frame_chart_overview.location.href='chart_overview.php';
			</script>
		<?php endif; ?>

		<script src="../include/js/chart_details.js"></script>
	</body>
</html>
