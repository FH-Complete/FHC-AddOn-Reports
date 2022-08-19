<?php

require_once('../../../config/vilesci.config.inc.php');
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once('../../../include/benutzerberechtigung.class.php');

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if (!$rechte->isBerechtigt('addon/reports_verwaltung'))
	die($rechte->errormsg);
?>

<html>
	<head>
		<title>Reporting Problemcheck</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
		<link rel="stylesheet" href="../../../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" type="text/css">
		<link rel="stylesheet" href="../../../vendor/fortawesome/font-awesome4/css/font-awesome.min.css" type="text/css">
		<link rel="stylesheet" href="../../../vendor/blackrockdigital/startbootstrap-sb-admin-2/dist/css/sb-admin-2.min.css" type="text/css">
		<link rel="stylesheet" href="../../../public/css/sbadmin2/admintemplate_contentonly.css" type="text/css">
		<link rel="stylesheet" href="../../../vendor/mottie/tablesorter/dist/css/theme.default.min.css" type="text/css">
		<link rel="stylesheet" href="../../../public/css/sbadmin2/tablesort_bootstrap.css" type="text/css">
		<link rel="stylesheet" href="../../../public/css/AjaxLib.css" type="text/css">

		<?php require_once("../../../include/meta/jquery.php"); ?>
		<script type="text/javascript" src="../../../vendor/blackrockdigital/startbootstrap-sb-admin-2/vendor/metisMenu/metisMenu.min.js"></script>
		<script type="text/javascript" src="../../../vendor/blackrockdigital/startbootstrap-sb-admin-2/dist/js/sb-admin-2.min.js"></script>
		<?php require_once("../include/meta/highcharts.php"); ?>
		<script type="text/javascript" src="../include/js/problemcheck/reports_problemcheck.js"></script>
		<script type="text/javascript" src="../../../vendor/mottie/tablesorter/dist/js/jquery.tablesorter.min.js"></script>
		<script type="text/javascript" src="../../../vendor/mottie/tablesorter/dist/js/jquery.tablesorter.widgets.min.js"></script>
		<script type="text/javascript" src="../../../public/js/tablesort/tablesort.js"></script>
	</head>

<body>
<div id="wrapper">
	<div id="page-wrapper">
		<div class="container-fluid">
			<div class="row">
				<div class="col-xs-12">
					<h3 class="page-header text-center">Reporting Problemcheck</h3>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6 col-xs-offset-3">
					<select id="objecttype" class="form-control">
						<option value="null" selected="selected">Objekttyp auswählen...</option>
						<option value="getViewIssues">View</option>
						<option value="getStatistikIssues">Statistik</option>
						<option value="getChartIssues">Chart</option>
					</select>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6 col-xs-offset-3 text-center">
					<div class="checkbox">
						<label>
						<input type="checkbox" name="showpassed" id="showpassed"checked>
							Erfolgreich
						</label>
						<label>
							<input type="checkbox" name="showerrors" id="showerrors" checked>
							Fehler
						</label>
						<label>
							<input type="checkbox" name="showwarnings" id="showwarnings" checked>
							Warnungen
						</label>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<table class="table table-condensed table-bordered" id="checktableparent">
						<thead>
							<tr>
								<th>Objekt</th>
								<th>Status</th>
								<th>Zuletzt ausgeführt <i class="fa fa-info-circle text-info" data-toggle="tooltip"
														  title="Zuletzt von Nutzern über das Frontend (cis/index.php) ausgeführt.
Views gelten bei Aufruf einer abhängigen Statistik/eines von einer abhängigen Statistik abhängigen Charts als ausgeführt.
Bei Aufruf eines Charts gilt die dazugehörige Statistik auch als ausgeführt.">
									</i>
								</th>
							</tr>
						</thead>
						<tbody id="checktable">
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<div id="charttest" style="display:none">
</div>
</body>
</html>
