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
 * Authors: Alexei Karpenko < karpenko@technikum-wien.at >
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../include/rp_view.class.php');
require_once('../../../include/statistik.class.php');
require_once('../include/rp_gruppe.class.php');
require_once('../../../include/benutzerberechtigung.class.php');

if (!$db = new basis_db())
{
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');
}

$user = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

/*if($rechte->isBerechtigt('addon/reports_verwaltung', 'suid'))
{
	$write_admin=true;
}*/

$view = new view();

if (!$view->loadAll())
{
	die($view->errormsg);
}

$statistik = new statistik();

if (!$statistik->getAnzahlGruppe())
{
	die($statistik->errormsg);
}

$menugruppe = new rp_gruppe();

if (!$menugruppe->loadAll())
{
	die($menugruppe->errormsg);
}
elseif (!$menugruppe->loadRecursive())
{
	die($menugruppe->errormsg);
}

function drawMenuGruppenRec($gruppen, $level = 0)
{
	foreach ($gruppen as $gruppe)
	{
		echo "<option value=".$gruppe->reportgruppe_id.">";
		for ($i = 0; $i < $level; $i++)
		{
			echo "&emsp;";
		}
		echo $gruppe->bezeichnung."</option>";
		if (!empty($gruppe->children))
		{
			$level++;
			drawMenuGruppenRec($gruppe->children, $level);
			$level--;
		}
	}
}

?>
<html>
	<head>
		<title>Reporting Abhängigkeiten Übersicht</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
		<link rel="stylesheet" href="../../../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" type="text/css">
		<link rel="stylesheet" href="../../../vendor/components/font-awesome/css/font-awesome.min.css" type="text/css">
		<link rel="stylesheet" href="../../../vendor/BlackrockDigital/startbootstrap-sb-admin-2/dist/css/sb-admin-2.min.css" type="text/css">
		<link rel="stylesheet" href="../../../public/css/sbadmin2/admintemplate_contentonly.css" type="text/css">
		<?php require_once("../../../include/meta/jquery.php"); ?>
		<script type="text/javascript" src="../../../vendor/BlackrockDigital/startbootstrap-sb-admin-2/vendor/metisMenu/metisMenu.min.js"></script>
		<script type="text/javascript" src="../../../vendor/BlackrockDigital/startbootstrap-sb-admin-2/dist/js/sb-admin-2.min.js"></script>
		<?php require_once("../include/meta/highcharts.php"); ?>
		<script type="text/javascript" src="../include/js/problemcheck/dependency_overview.js"></script>
	</head>

	<body>
	<div id="wrapper">
		<div id="page-wrapper">
			<div class="container-fluid">
				<div class="row">
					<div class="col-xs-12">
						<h3 class="page-header text-center">Reporting Abhängigkeitsübersicht</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-4">
						<div class="form-group">
							<label>Menügruppe</label>
							<select id="selectmenugroup" class="form-control">
								<option value="null">Menügruppe auswählen...</option>
								<option value="getAllDependencies">Alle</option>
								<?php drawMenuGruppenRec($menugruppe->recursive); ?>
							</select>
						</div>
					</div>
					<div class="col-xs-4">
						<div class="form-group">
							<label>Statistikgruppe</label>
							<select id="selectstatistikgroup" class="form-control">
								<option value="null">Statistikgruppe auswählen...</option>
								<option value=""> (Ohne Gruppe)</option>
								<?php foreach ($statistik->result as $statistik):
									if (empty($statistik->gruppe))
										continue;?>
									<option value="<?php echo $statistik->gruppe ?>"><?php echo $statistik->gruppe ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="col-xs-4">
						<div class="form-group">
							<label>View</label>
							<select id="selectview" class="form-control">
								<option value="null">View auswählen...</option>
								<option value="getAllDependencies">Alle</option>
								<?php foreach ($view->result as $view): ?>
									<option value="<?php echo $view->view_id ?>"><?php echo $view->view_kurzbz ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12 text-center">
						<div class="form-group checkbox">
							<label>
							<input type="checkbox" id="showreports">
								Reports anzeigen
							</label>
							 |
							<label>
							<input type="checkbox" id="showanimations">
								Animationen
							</label>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12">
						<div id="netgraph"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</body>
</html>
