<?php
/* Copyright (C) 2011 FH Technikum-Wien
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
 *          Andreas Oesterreicher 	< andreas.oesterreicher@technikum-wien.at >
 *          Karl Burkhart 		< burkhart@technikum-wien.at >
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/statistik.class.php');
require_once('../../../include/benutzerberechtigung.class.php');

$user = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('basis/statistik'))
{
	die('Sie haben keine Berechtigung fuer diese Seite');
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Statistik</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css">
		<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
		<?php require_once("../../../include/meta/jquery.php"); ?>
		<?php require_once("../../../include/meta/jquery-tablesorter.php"); ?>

		<script type="text/javascript">

			$(function() {
				$("#myTable").tablesorter(
				{
					sortList: [[1,0]],
					widgets: ['zebra']
				});
			});

			function confdel()
			{
				return confirm('Wollen Sie diesen Eintrag wirklich löschen?');
			}
		</script>
	</head>
	<body>
		<div style="text-align:right">
			<a href="../../../vilesci/stammdaten/statistik_details.php?action=new" target="detail_statistik">Neu</a>
		</div>

		<?php
		if(isset($_GET['action']) && $_GET['action']=='delete')
		{
			if(!$rechte->isBerechtigt('basis/statistik', null, 'suid'))
				die('Sie haben keine Berechtigung fuer diese Seite');

			if(!isset($_GET['statistik_kurzbz']))
				die('Fehlender Parameter Statistik');

			$statistik = new statistik();
			if($statistik->delete($_GET['statistik_kurzbz']))
				echo '<span class="ok">Eintrag wurde erfolgreich gelöscht</span>';
			else
				echo '<span class="error">'.$statistik->errormsg.'</span>';
		}

		$statistik = new statistik();

		if(!$statistik->getAll())
			die($statistik->errormsg);
		?>
		<table class="tablesorter" id="myTable">
			<thead>
				<tr>
					<th>Gruppe</th>
					<th>Kurzbz</th>
					<th>Pub</th>
					<th>Bezeichnung</th>
					<th>URL</th>
					<th>SQL</th>
					<th>Beschr.</th>
					<th colspan="2">Aktion</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($statistik->result as $row): ?>
					<tr>
						<td>
							<?php echo $row->gruppe ;?>
							<a href="../cis/vorschau.php?statistik_kurzbz=<?php echo $row->statistik_kurzbz ?>&debug=true" target="_blank"><img style="float:right;" title="<?php echo $row->gruppe ?> anzeigen" src="../include/images/Bar_Chart_Statistics_clip_art.svg" class="mini-icon" /></a>
						</td>
						<td>
							<a href="../../../vilesci/stammdaten/statistik_details.php?action=update&statistik_kurzbz=<?php echo $row->statistik_kurzbz ?>" target="detail_statistik">
								<?php echo $row->statistik_kurzbz; ?>
							</a>
						</td>
						<td align="center">
							<?php echo $row->publish; ?>
						</td>
						<td>
							<?php echo $row->bezeichnung; ?>
						</td>
						<td>
							<?php if($row->url != null)echo substr($row->url, 0, 25)."..." ?>
						</td>
						<td>
							<?php if($row->sql != null)echo substr($row->sql, 0, 25)."..." ?>
						</td>
						<td>
							<a href="#" onclick="window.open('../../cms/content.php?content_id=<?php echo $row->content_id ?>', 'Beschreibung', 'width=600,height=600, scrollbars=yes');">
								<?php echo $row->content_id ?>
							</a>
						</td>
						<td>
							<a href="../../../vilesci/stammdaten/statistik_details.php?action=update&statistik_kurzbz=<?php echo $row->statistik_kurzbz ?>" target="detail_statistik">
								bearbeiten
							</a>
						</td>
						<td>
							<a href="../../../vilesci/stammdaten/statistik_uebersicht.php?action=delete&statistik_kurzbz=<?php echo $row->statistik_kurzbz ?>" onclick="return confdel()">
								entfernen
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</body>
</html>
