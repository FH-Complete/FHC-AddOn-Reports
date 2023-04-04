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
	require_once('../include/rp_chart.class.php');
	require_once('../include/rp_report.class.php');
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

		<link rel="stylesheet" type="text/css" href="../../../vendor/twbs/bootstrap3/dist/css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="../include/css/offcanvas.css">
		<link rel="stylesheet" type="text/css" href="../include/css/multilevel_dropdown.css">
		<link rel="stylesheet" type="text/css" href="../cis/reporting.css">
		<link rel="stylesheet" type="text/css" href="../../../vendor/nicolaskruchten/pivottable/dist/pivot.min.css">
		<link rel="stylesheet" type="text/css" href="../include/css/custompivot.css">
		<link rel="stylesheet" href="../include/css/charts.css" type="text/css">

		<link rel="stylesheet" type="text/css" href="../../../vendor/components/jqueryui/themes/base/jquery-ui.min.css">

		<script type="text/javascript" src="../../../vendor/components/jquery/jquery.min.js"></script>
		<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>

		<script type="text/javascript" src="../../../vendor/nicolaskruchten/pivottable/dist/pivot.min.js"></script>
		<script type="text/javascript" src="../../../vendor/nicolaskruchten/pivottable/dist/gchart_renderers.min.js"></script>

		<?php require_once("../include/meta/highcharts.php"); ?>
		<script type="text/javascript" src="../vendor/furf/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	</head>
	<body>
	<?php

		if(isset($_GET["report_id"]))
		{
			$report = new report();
			if($report->load($_GET["report_id"]))
				echo "<h5>" . $report->title . "</h5>";
		}
	?>


		<div id="spinner" style="display:none; width:80%; margin-left:10%; top:30px; position:absolute; z-index:10;">
			<div class="progress">
					<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
					Loading Data
					</div>
			</div>
		</div>
		<?php $systemfilter_id = isset($_GET['systemfilter_id']) ? intval($_GET['systemfilter_id']) : 'false';  ?>
		<div style="display: none; margin-top:20px; margin-bottom:2%;" id="filter">
			<div >
				<form class="" onsubmit="return false">
					<div class="row">
						<div class="col-xs12">
							<span id="filter-input"></span>
						</div>
					</div>
						<button style="display: inline;height:40px;" onclick="runFilter('html',true,<?php echo $systemfilter_id ?>)" class="btn btn-default" type="submit">Ausf&uuml;hren</button>
						<button style="display: inline;height:40px;" onclick="runFilter('pdf')" class="btn btn-default" id="filter-PdfLink" ><img src="../include/images/Pdf.svg" width="20" alt="pdf"/></button>
						<button style="display: inline;height:40px;" onclick="runFilter('debug')" class="btn btn-warning" id="filter-debugLink">DEBUG</button>
				</form>
			</div>
		</div>

		<div id="content" style="display:none;"></div>
		<script src="../../../vendor/twbs/bootstrap3/dist/js/bootstrap.min.js"></script>
		<script src="../include/js/offcanvas.js"></script>
		<script type="text/javascript" src="../cis/reporting.js"></script>
		<script>
			<?php
			if(isset($_GET["statistik_kurzbz"]))
			{
				$systemfilter_id = isset($_GET['systemfilter_id']) ? $_GET['systemfilter_id'] : 'false';
				// GET-Parameter auslesen und übergeben
				$getString = '';
				foreach ($_GET AS $key=>$value)
				{
					// Vordefinierte Parameter entfernen
					if ($key != 'type' &&
						$key != 'statistik_kurzbz' &&
						$key != 'report_id' &&
						$key != 'putlog' &&
						$key != 'systemfilter_id' &&
						$key != 'debug')
					{
						$getString .= $key.':'.$value.',';
					}
				}
				$getString = substr($getString, 0, -1);
				// Wenn $getString leer ist, false ausgeben, ansonsten Umklammern
				if ($getString == '')
				{
					$getString = 'false';
				}
				else
				{
					$getString = '{'.$getString.'}';
				}
				echo "loadStatistik('".$_GET['statistik_kurzbz']."', true, ".$systemfilter_id.", ".$getString.");";
			}

			else if(isset($_GET["report_id"]))
			{
				echo "var debug = false;";

				if(isset($_GET["debug"]))
					echo "debug = true;";

				echo "loadReport('".$_GET['report_id']."', true);";
			}

			else if(isset($_GET["chart_id"]))
			{
				$cid = $_GET["chart_id"];
				$c = new chart($_GET["chart_id"]);
				echo "loadChart('".$cid."','".$c->statistik_kurzbz."',true);";
			}
			?>

			function reportsCleanup()
			{
				var req = $.ajax
				({
					url: "../vilesci/reports_cleanup.php",
					method: "POST"
				});

				req.fail(function(){alert("Konnte alte Reports nicht löschen!");});
			}
		</script>
	</body>
</html>
