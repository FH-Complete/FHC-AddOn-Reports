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
 * Authors: Andreas Moik <moik@technikum-wien.at>
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../include/rp_attribut.class.php');
require_once('../include/rp_attribut_zuweisungen.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/datum.class.php');

$user = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if($rechte->isBerechtigt('addon/reports_verwaltung', 'suid'))
{
	$write_admin=true;
}

$attribut = new rp_attribut();
$sprache = new sprache();



if(isset($_REQUEST['action']))
{
	if($_REQUEST['action']=='delete')
	{
		$zuweisungen = new rp_attribut_zuweisungen();
		$zuweisungen->loadAllFromAttribut($_REQUEST['attribut_id']);

		if(numberOfElements($zuweisungen->result) > 0)
		{
			echo "<script>alert('Es gibt noch Zuweisungen!');</script>";
		}
		else
		{
			if(!$attribut->delete($_REQUEST['attribut_id']))
				echo '<script>alert("Der Eintrag konnte nicht gelöscht werden!");</script>';
		}
	}
}

if (!$attribut->loadAll())
{
	die($attribut->errormsg);
}

?>
<html>
	<head>
		<title>Attribut Übersicht</title>
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
					sortList: [[0,1]],
					widgets: ["saveSort", "zebra", "filter", "stickyHeaders"],
					headers: {2: {sorter: false, filter: false}},
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

		</script>
	</head>

	<body class="background_main">
		<a href="attribut_details.php" target="frame_attribut_details">Neues Attribut</a><br>
		<button type="button" class="resetsaved" title="Reset Filter">Reset Filter</button>

		<form name="formular">
			<input type="hidden" name="check" value="">
		</form>

		<table class="tablesorter" id="t1">
			<thead>
				<tr>
					<th title="ID des Attributs">
						ID
					</th>
					<th title="Titel des Attributs">
						Titel
					</th>
					<th>
						<!-- Entfernen -->
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($attribut->result as $attribut): ?>
					<tr>
						<td class="overview-id">
							<a href="attribut_details.php?attribut_id=<?php echo $attribut->attribut_id ?>" style="float:left;" target="frame_attribut_details">
								<?php echo $attribut->attribut_id ?>
							</a>
							<a href="attribut_vorschau.php?attribut_id=<?php echo $attribut->attribut_id ?>" target="frame_attribut_details"><img style="float:left;" title="Vorschau zu <?php echo $attribut->shorttitle["German"];?>" src="../include/images/Attribut.svg" class="mini-icon" /></a>
						</td>
						<td>
							<a href="attribut_details.php?attribut_id=<?php echo $attribut->attribut_id ?>" target="frame_attribut_details">
								<?php echo $attribut->shorttitle["German"];?>
							</a>
						</td>
						<td>
							<a href="attribut_overview.php?action=delete&attribut_id=<?php echo $attribut->attribut_id ?>" onclick="return confdel()">entfernen</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</body>
</html>
