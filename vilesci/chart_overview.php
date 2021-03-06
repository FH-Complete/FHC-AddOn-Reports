<?php
/* Copyright (C) 2013 fhcomplete.org
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
 *					Andreas Moik <moik@technikum-wien.at>
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../include/rp_chart.class.php');
require_once('../../../include/benutzerberechtigung.class.php');

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

//if(!$rechte->isBerechtigt('addon/datenimport'))
//	die('Sie haben keine Rechte fuer dieses AddOn! Schleich Dich!');

if($rechte->isBerechtigt('addon/reports_verwaltung', 'suid'))
	$write_admin=true;

$chart = new chart();

if(isset($_GET['action']))
{
	if($_GET['action']=='delete')
	{
		if(!$chart->delete($_GET['chart_id']))
			echo '<script>alert("Der Eintrag konnte nicht gelöscht werden!"</script>';
	}
}

// Speichern der Daten
if(isset($_POST['chart_id']))
{
	// Die Aenderungen werden per Ajax Request durchgefuehrt,
	// daher wird nach dem Speichern mittels exit beendet
	if($write_admin)
	{

		//Lehre Feld setzen
		if(isset($_POST['lehre']))
		{
			$lv_obj = new ort();
			if($lv_obj->load($_POST['ort_kurzbz']))
			{
				$lv_obj->lehre=($_POST['lehre']=='true'?false:true);
				$lv_obj->updateamum = date('Y-m-d H:i:s');
				$lv_obj->updatevon = $user;
				if($lv_obj->save(false))
					exit('true');
				else
					exit('Fehler beim Speichern:'.$lv_obj->errormsg);
			}
			else
				exit('Fehler beim Laden der LV:'.$lv_obj->errormsg);
		}

		//Reservieren Feld setzen
		if(isset($_POST['reservieren']))
		{
			$lv_obj = new ort();
			if($lv_obj->load($_POST['ort_kurzbz']))
			{
				$lv_obj->reservieren=($_POST['reservieren']=='true'?false:true);
				$lv_obj->updateamum = date('Y-m-d H:i:s');
				$lv_obj->updatevon = $user;
				if($lv_obj->save(false))
					exit('true');
				else
					exit('Fehler beim Speichern:'.$lv_obj->errormsg);
			}
			else
				exit('Fehler beim Laden der LV:'.$lv_obj->errormsg);
		}

		//Aktiv Feld setzen
		if(isset($_POST['aktiv']))
		{
			$lv_obj = new ort();
			if($lv_obj->load($_POST['ort_kurzbz']))
			{
				$lv_obj->aktiv=($_POST['aktiv']=='true'?false:true);
				$lv_obj->updateamum = date('Y-m-d H:i:s');
				$lv_obj->updatevon = $user;
				if($lv_obj->save(false))
					exit('true');
				else
					exit('Fehler beim Speichern:'.$lv_obj->errormsg);
			}
			else
				exit('Fehler beim Laden der LV:'.$lv_obj->errormsg);
		}
	}
}

if (isset($_GET["toggle"]))
{
	if(!$rechte->isBerechtigt('basis/ort', null, 'suid'))
		die('Sie haben keine Berechtigung fuer diese Aktion');

	if ($_GET["rlehre"] != "" && $_GET["rlehre"] != NULL)
	{
		$rlehre = $_GET["rlehre"];
		$sg_update = new ort();
		$qry = "UPDATE public.tbl_ort SET lehre = NOT lehre WHERE ort_kurzbz='".$rlehre."';";
		if(!$db->db_query($qry))
		{
			die('Fehler beim Speichern des Datensatzes');
		}
	}
	if ($_GET["rres"] != "" && $_GET["rres"] != NULL)
	{
		$rres = $_GET["rres"];
		$sg_update = new ort();
		$qry = "UPDATE public.tbl_ort SET reservieren = NOT reservieren WHERE ort_kurzbz='".$rres."';";
		if(!$db->db_query($qry))
		{
			die('Fehler beim Speichern des Datensatzes');
		}
	}
	if ($_GET["raktiv"] != "" && $_GET["raktiv"] != NULL)
	{
		$raktiv = $_GET["raktiv"];
		$sg_update = new ort();
		$qry = "UPDATE public.tbl_ort SET aktiv = NOT aktiv WHERE ort_kurzbz='".$raktiv."';";
		if(!$db->db_query($qry))
		{
			die('Fehler beim Speichern des Datensatzes');
		}
	}
}

if (!$chart->loadAll())
    die($chart->errormsg);
?>
<!DOCTYPE html>
<html class="chart-overview">
	<head>
		<title>Räume Übersicht</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
		<?php require_once("../../../include/meta/jquery.php"); ?>
		<?php require_once("../../../include/meta/jquery-tablesorter.php"); ?>
		<style>
			table.tablesorter tbody td
			{
				margin: 0;
				padding: 0;
				vertical-align: middle;
			}
		</style>
		<script language="JavaScript" type="text/javascript">
			$(function() {
				$("#t1").tablesorter(
				{
					sortList: [[0,0]],
					widgets: ["saveSort", "zebra", "filter", "stickyHeaders"],
					headers: {8: {sorter: false, filter: false}},
					widgetOptions : {filter_saveFilters : true}
				});

				$('.resetsaved').click(function()
				{
					$("#t1").trigger("filterReset");
					location.reload();
					return false;
				});
			});

			function confdel()
			{
				if(confirm("Diesen Datensatz wirklick loeschen?"))
				  return true;
				return false;
			}

			function changeboolean(ort_kurzbz, name)
			{
				value=document.getElementById(name+ort_kurzbz).value;

				var dataObj = {};
				dataObj["ort_kurzbz"]=ort_kurzbz;
				dataObj[name]=value;

				$.ajax({
					type:"POST",
					url:"raum_uebersicht.php",
					data:dataObj,
					success: function(data)
					{
						if(data=="true")
						{
							//Image und Value aendern
							if(value=="true")
								value="false";
							else
								value="true";
							document.getElementById(name+ort_kurzbz).value=value;
							document.getElementById(name+"img"+ort_kurzbz).src="../../skin/images/"+value+".png";
						}
						else
							alert("ERROR:"+data)
					},
					error: function() { alert("error"); }
				});
			}

		function confdel()
		{
			return confirm("Wollen Sie diesen Eintrag wirklich löschen?");
		}
		</script>
	</head>

	<body class="background_main">
		<a href="chart_details.php" target="frame_chart_details">Neuer Chart</a><br/>
		<button type="button" class="resetsaved" title="Reset Filter">Reset Filter</button>

		<form name="formular">
			<input type="hidden" name="check" value="">
		</form>

		<table class="tablesorter" id="t1">
			<thead>
				<tr>
					<th onmouseup="document.formular.check.value=0">
						ID
					</th>
					<th title="Titel des Charts">
						Titel
					</th>
					<th>
						Type
					</th>
					<th>
						Statistik
					</th>
					<th>
						SourceType
					</th>
					<th>
						DataSource
					</th>
					<th>
						DataSourceType
					</th>
					<th>
						Description
					</th>
					<th>
						<!-- Entfernen -->
					</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$chart_obj = new chart();
				$plugins = $chart_obj->getPlugins();

				foreach ($chart->result as $chart): ?>
					<tr>
						<td class="overview-id">
							<a href="chart_details.php?chart_id=<?php echo $chart->chart_id ?>" target="frame_chart_details">
								<?php echo $chart->chart_id ?>
							</a>
							<a href="../cis/vorschau.php?chart_id=<?php echo $chart->chart_id ?>" target="_blank">
								<img title="<?php echo $chart->title ?> anzeigen" src="../include/images/Chart.svg" class="mini-icon" />
							</a>
						</td>
						<td>
							<a href="chart_details.php?chart_id=<?php echo $chart->chart_id ?>" target="frame_chart_details">
								<?php echo $chart->title ?>
							</a>
						</td>

						<td>
							<?php echo $chart->type ? $plugins[$chart->type] : '' ?>
						</td>
						<td>
							<?php if(isset($chart->statistik_kurzbz)): ?>
								<a href="../../../vilesci/stammdaten/statistik_details.php?statistik_kurzbz=<?php echo $chart->statistik_kurzbz; ?>" target="frame_chart_details"><img title="Details zu <?php echo $chart->statistik_kurzbz; ?> anzeigen" src="../include/images/Statistik.svg" class="mini-icon" /></a>
							<?php endif; ?>
						</td>
						<td>
							<?php echo $chart->sourcetype ?>
						</td>
						<td>
							<?php echo $db->convert_html_chars($chart->datasource) ?>
						</td>
						<td>
							<?php echo $chart->datasource_type ?>
						</td>
						<td title="<?php echo $db->convert_html_chars($chart->description) ?>">
							<?php echo substr($db->convert_html_chars($chart->description), 0, 16) ?>...
						</td>
						<td>
							<a href="chart_overview.php?action=delete&chart_id=<?php echo $chart->chart_id ?>" onclick="return confdel()">entfernen</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</body>
</html>
