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
require_once('../include/rp_view.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/datum.class.php');

if (!$db = new basis_db())
{
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');
}

$user = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if($rechte->isBerechtigt('addon/reports_verwaltung', 'suid'))
{
	$write_admin=true;
}

$view = new view();
$rp_generateAllViews_msg = "";

if(isset($_REQUEST['action']))
{
	if($_REQUEST['action']=='delete')
	{
		if(!$view->delete($_REQUEST['view_id']))
			echo '<script>alert("Der Eintrag konnte nicht gelöscht werden!");</script>';
	}
	else if ($_REQUEST["action"]=='generateTable')
	{
		if (isset($_REQUEST["view_id"]))
		{
			$gv = new view($_REQUEST["view_id"]);
			// Check, ob Tabellenname gesetz ist
			if ($gv->table_kurzbz != '' && $gv->table_kurzbz != (defined('TABLE_BEGIN')?TABLE_BEGIN:''))
			{
				$gv->generateTable();
			}
			else
			{
				echo '<script>alert("Die Tabelle muss eine Bezeichnung haben");</script>';
			}
		}
	}
	else if($_REQUEST["action"]=='Statische Tabellen generieren')
	{
		if(rp_generateAllViews())
			$rp_generateAllViews_msg .= "<span style='color:green;margin-left:5px;'>Erfolgreich abgeschlossen</span>";
		else
			$rp_generateAllViews_msg .= "<span style='color:red;margin-left:5px;'>Es sind Fehler aufgetreten</span>";
	}
}

if (!$view->loadAll())
{
	die($view->errormsg);
}

?>
<html>
	<head>
		<title>Views Übersicht</title>
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

		.notGenerated
		{
			background: url("../../../skin/images/ampel_rot.png") no-repeat center center;
			background-size: 10px 10px;
			padding-left: 5px;
			padding-right: 5px;
			margin-left: 2px;
			margin-right: 2px;
		}
		</style>
		<script language="JavaScript" type="text/javascript">
			$(function() {
				$("#t1").tablesorter(
				{
					sortList: [[0,1]],
					widgets: ["saveSort", "zebra", "filter", "stickyHeaders"],
					headers: {6: {sorter: false, filter: false}},
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
			return confirm("Wollen Sie diesen Eintrag wirklich löschen?");
		}
		function loading(id)
		{
			$('#'+id).text('Loading [...]');
		}

		</script>
	</head>

	<body class="background_main">
		<a href="view_details.php" target="frame_view_details">Neue View</a>
		<form action='view_overview.php' method='POST' name='rebuildForm'>
			<input type='submit' value='Statische Tabellen generieren' name='action'><?php echo $rp_generateAllViews_msg; ?>
		</form>
		<button type="button" class="resetsaved" title="Reset Filter">Reset Filter</button>

		<form name="formular">
			<input type="hidden" name="check" value="">
		</form>

		<table class="tablesorter" id="t1" style="table-layout: fixed">
			<thead>
				<tr>
					<th title="ID der View">
						ID
					</th>
					<th title="Bezeichnung der View">
						View
					</th>
					<th title="Bezeichnung der Tabelle">
						Table
					</th>
					<th align="center">
						Static
					</th>
					<th>
						LastCopy
					</th>
					<th>
						SQL
					</th>
					<th>
						<!-- Entfernen -->
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($view->result as $view): ?>
					<tr>
						<td class="overview-id">
							<a href="view_details.php?view_id=<?php echo $view->view_id ?>" target="frame_view_details">
								<?php echo $view->view_id ?>
							</a>
						</td>
						<td>
							<a href="view_details.php?view_id=<?php echo $view->view_id ?>" target="frame_view_details">
								<?php echo $view->view_kurzbz ?>
							</a>
						</td>
						<td>
							<?php echo $view->table_kurzbz ?>
						</td>
						<td align="center">
							<?php if ($view->static) echo '✓' ?>
						</td>
						<td align="center">
							<?php
							//if($view->static)
							{
								echo '<a href="view_overview.php?action=generateTable&view_id='.$view->view_id.'" onclick="return loading(\''.$view->view_id.'lastCopyDate\')">';
								echo '<img title="Tabelle generieren" anzeigen" src="../include/images/View.svg" class="mini-icon" />';
								echo '</a> ';

								if(!isset($view->lastcopy))
								{
									//echo '<span class="notGenerated"></span>';
									echo '<span id="'.$view->view_id.'lastCopyDate"></span>';
								}
								else
								{
									$dt = new datum();
									echo '<span id="'.$view->view_id.'lastCopyDate">'.$dt->formatDatum($view->lastcopy).'</span>';
								}
							}
							?>
						</td>
						<td style="text-overflow: ellipsis; white-space: nowrap; overflow:hidden;">
							<?php echo $db->convert_html_chars($view->sql) ?>
						</td>
						<td>
							<a href="view_overview.php?action=delete&view_id=<?php echo $view->view_id ?>" onclick="return confdel()">entfernen</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</body>
</html>
