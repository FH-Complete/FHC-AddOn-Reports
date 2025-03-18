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
 * Authors: Manfred Kindl 	< manfred.kindl@technikum-wien.at >
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
//require_once('../../../include/filter.class.php');
require_once('../include/rp_system_filter.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/person.class.php');

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('basis/statistik', null, 'suid'))
	die($rechte->errormsg);

$statistik_kurzbz = isset($_GET['statistik_kurzbz']) ? $_GET['statistik_kurzbz'] : die('Statistik_kurzbz muss übergeben werden');

if (isset($_POST['action']) && $_POST['action'] == 'save')
{
	$filterId = $_POST['id'];
	$filterData = $_POST['filterData'];

	$filter = new rp_system_filter($statistik_kurzbz,$filterId);
	$filter->new = false;
	$filter->filter = $filterData;

	if (!$filter->save())
	{
		echo 'Fehler beim Speichern der Filterdaten';
	}
}

$filter = new rp_system_filter();
$filter->loadAll($statistik_kurzbz, null, true);

?>
<html>
	<head>
		<title>Filter Übersicht</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
		<link href="../../../vendor/twbs/bootstrap3/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">

		<?php
		require_once("../../../include/meta/jquery.php");
		require_once("../../../include/meta/jquery-tablesorter.php");
		?>

		<script language="JavaScript" type="text/javascript">

			$(function() {
				$('.filterData').each(function(index, element) {
					var $textarea = $(element);
					// JSON aus dem aktuellen Textarea lesen
					var jsonData = $textarea.val();

					try {
					// JSON formatieren und in das Textarea einfügen
						var formattedJson = JSON.stringify(JSON.parse(jsonData), null, 2); // 2 steht für die Anzahl der Leerzeichen für Einrückungen
						$textarea.val(formattedJson);
					} catch (error) {
						console.error('Ungültiges JSON in Textarea ' + (index + 1) + ':', error);
					}
					});
			});

			function ConfirmDelete(filter_id)
			{
				if(confirm("Wollen Sie diesen Filter wirklich löschen?"))
				{
					document.forms['form_'+filter_id].submit();
				}
			}
		</script>
	</head>

	<body>
	<div class="container-fluid">
		<h1>Filter</h1>
		<div class="row">
	<?php
	$i = 0;
	foreach ($filter->result as $row)
	{
		$person = new person($row->person_id);
		$json = ($row->filter);
		if ($i % 4 == 0)
		{
			echo '</div><div class="row">';
		}
		echo '<div class="col-md-3">';
		echo '<form name="filterDataForm" class="form-horizontal" action="'.$_SERVER['PHP_SELF'].'?statistik_kurzbz='.$statistik_kurzbz.'" method="POST">';
		echo '<input type="hidden" name="id" value="'.$row->filter_id.'">';
		echo '<div class="form-group">';
		echo '<label class="control-label col-sm-2" for="name">Name:</label>';
		echo '	<div class="col-sm-10" id="name">
					<input type="text" class="form-control" value="'.$person->nachname.' '.$person->vorname.'" disabled >
				</div>';
		echo '</div>';
		echo '<div class="form-group">';
		echo '	<label class="control-label col-sm-2" for="filter">Filter:</label>';
		echo '	<div class="col-sm-10" id="filter">
					<textarea class="form-control filterData" rows="30" name="filterData">'.$json.'</textarea>
				</div>';
		echo '</div>';
		echo '<div class="form-group">';
		echo '	<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-default pull-right" name="action" value="save">Submit</button>
				</div>';
		echo '</div>';
		echo '</form>';
		echo '</div>';
		$i++;
	}
	?>
		</div>
	</div>
	</body>
</html>
