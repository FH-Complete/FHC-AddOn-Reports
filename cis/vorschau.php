<?php
/* Copyright (C) 2015 FH Technikum-Wien
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
	require_once('../include/chart.class.php');
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
		<link rel="stylesheet" type="text/css" href="../cis/reporting.css">
		<link rel="stylesheet" type="text/css" href="../include/js/pivottable/pivot.css">
		<link rel="stylesheet" href="../include/css/charts.css" type="text/css">
		<link rel="stylesheet" href="../include/css/jquery-ui.1.11.2.min.css" type="text/css">

		<script type="text/javascript" src="../include/js/jquery-1.11.2.min.js"></script>
		<script type="text/javascript" src="../include/js/jquery-ui.1.11.2.min.js"></script>

		<script type="text/javascript" src="../include/js/pivottable/pivot.js"></script>
		<script type="text/javascript" src="../include/js/pivottable/gchart_renderers.js"></script>

		<script src="../include/js/highcharts/highcharts-custom.js" type="application/javascript"></script>
		<script src="../include/js/highcharts/main.js" type="application/javascript"></script>
		<script type="text/javascript" src="../include/js/pivottable/jquery.ui.touch-punch.min.js"></script>

	</head>
	<body>
		<div id="spinner" style="display:none; width:80%; margin-left:10%; top:50px; position:absolute; z-index:10;">
			<div class="progress">
  				<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
					Loading Data
  				</div>
			</div>
		</div>

		<div style="display: none;" id="filter">
			<div class="col-xs-12 col-sm-9">
				<form class="form-inline" onsubmit="return false">
				<div id="filter-input"></div>
				<div style="margin-top:20px">
					<button style="display: inline;height:40px;" onclick="runFilter('html')" class="btn btn-default" type="submit">Ausf&uuml;hren</button>
					<button style="display: inline;height:40px;" onclick="runFilter('pdf')" id="filter-PdfLink" ><img src="../cis/pdfIcon.png" width="20" alt="pdf"/></button>
					<button style="display: inline;height:40px;color:red;" onclick="runFilter('debug')" id="filter-debugLink">DEBUG</button>
				</div>
				</form>
			</div>
		</div>
		<div id="content" style="display:none;"></div>
		<script src="../include/js/bootstrap.min.js"></script>
		<script src="../include/js/offcanvas.js"></script>
		<script type="text/javascript" src="../cis/reporting.js"></script>
		<script>
			<?php

			if(isset($_GET["statistik_kurzbz"]))
			{
				echo "loadStatistik('".$_GET['statistik_kurzbz']."');";
			}

			else if(isset($_GET["report_id"]))
			{
				if(isset($_GET["debug"]))
					echo "var debug = true;";
				echo "loadReport('".$_GET['report_id']."');";
			}

			else if(isset($_GET["chart_id"]))
			{
				$cid = $_GET["chart_id"];
				$c = new chart($_GET["chart_id"]);
				echo "loadChart('".$cid."','".$c->statistik_kurzbz."');";
			}

			?>

		</script>
	</body>
</html>

