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
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../include/report.class.php');
require_once('../../../include/benutzerberechtigung.class.php');

if (!$db = new basis_db())
{
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');
}

$user = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if($rechte->isBerechtigt('addon/reports', 'suid'))
{
	$write_admin=true;
}

$report = new report();

if(isset($_GET['action']))
{
	if($_GET['action']=='delete')
	{
		if(!$report->delete($_GET['report_id']))
			echo '<script>alert("Der Eintrag konnte nicht gelöscht werden!");</script>';
	}
}

if (!$report->loadAll())
{
    die($report->errormsg);
}

?>
<html>
	<head>
		<title>Reports Übersicht</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
		<script type="text/javascript" src="../../../include/js/jquery.min.1.11.1.js"></script>
		<script type="text/javascript" src="../../../submodules/tablesorter/jquery.tablesorter.min.js"></script>
		<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
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
					sortList: [[3,1]],
					widgets: ["zebra"]
				});
			});

		function confdel()
		{
			return confirm("Wollen Sie diesen Eintrag wirklich löschen?");
		}

		</script>
	</head>

	<body class="background_main">
		<a href="report_details.php" target="frame_report_details">Neuer Report</a>

		<form name="formular">
			<input type="hidden" name="check" value="">
		</form>

		<table class="tablesorter" id="t1">
			<thead>
				<tr>
					<th align="center" onmouseup="document.formular.check.value=0">
						ID
					</th>
					<th title="Titel des Reports">
						Titel
					</th>
					<th>
						Gruppe
					</th>
					<th align="center">
						Pub
					</th>
					<th>
						Format
					</th>
					<th title="Ergebnisse in diversen Formaten">
						Results
					</th>
					<th>
						Body
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
				<?php foreach ($report->result as $report): ?>
					<tr>
						<td class="overview-id">
							<a href="report_details.php?report_id=<?php echo $report->report_id ?>" target="frame_report_details">
								<?php echo $report->report_id ?>
							</a>
						</td>
						<td>
							<a href="report_details.php?report_id=<?php echo $report->report_id ?>" target="frame_report_details">
								<?php echo $report->title ?>
							</a>
						</td>
						<td>
							<?php echo $report->gruppe ?>
						</td>
						<td align="center">
							<?php if ($report->publish) echo '✓' ?>
						</td>
						<td>
							<?php echo $report->format ?>
							<a href="../cis/vorschau.php?report_id=<?php echo $report->report_id?>&debug=true" target="frame_report_details">
								<img title="<?php echo $report->title ?> generieren" src="../include/images/Bar_Chart_Statistics_clip_art.svg" class="mini-icon" />
							</a>
						</td>
						<td align="center">
							<a href="../cis/vorschau.php?report_id=<?php echo $report->report_id ?>" target="_blank"><img title="<?php echo $report->title ?> anzeigen" src="../include/images/Bar_Chart_Statistics_clip_art.svg" class="mini-icon" /></a>
						</td>
						<td>
							<?php echo $db->convert_html_chars(substr($report->body,0,32)) ?>...
						</td>
						<td>
							<?php echo $db->convert_html_chars(substr($report->description,0,16)) ?>...
						</td>
						<td>
							<a href="report_overview.php?action=delete&report_id=<?php echo $report->report_id ?>" onclick="return confdel()">entfernen</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</body>
</html>
