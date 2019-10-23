<?php
/* Copyright (C) 2014 fhcomplete.org
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
 * Authors: Christian Paminger,
 *			Andreas Moik <moik@technikum-wien.at>
 */

require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/person.class.php');
require_once('../../../include/filter.class.php');
require_once('../../../include/statistik.class.php');
require_once('../../../include/webservicelog.class.php');
require_once('../include/rp_system_filter.class.php');

ini_set('memory_limit', '1024M');
$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);


$statistik = new statistik();

$statistik_kurzbz = filter_input(INPUT_GET, 'statistik_kurzbz');
$systemfilter_id = filter_input(INPUT_GET, 'systemfilter_id');
$htmlbody = filter_input(INPUT_GET, 'htmlbody', FILTER_VALIDATE_BOOLEAN);

if(!isset($statistik_kurzbz))
{
	die('"statistik_kurzbz" is not set!');
}

if(!$statistik->load($statistik_kurzbz))
{
	die('Fehler: ' . $statistik->errormsg);
}


$i = 0;
while(isset($_GET['varname' . $i]))
{
	$statistik->vars.='&' . $_GET['varname' . $i] . '=';
	if(isset($_GET['var' . $i]))
	{
		$statistik->vars.=$_GET['var' . $i];
	}
	else
	{
		die('"var"' . $i . ' is not set!');
	}
	$i++;
}




if($statistik->publish !== true)
	die("Diese Statistik ist nicht Oeffentlich");


if(isset($statistik->berechtigung_kurzbz))
	if(!$rechte->isBerechtigt($statistik->berechtigung_kurzbz))
		die($rechte->errormsg);

$allstatistikfilter = new rp_system_filter();

// alle Systemfilter holen für Dropdownauswahl
$allstatistikfilter->loadAll($statistik_kurzbz);

$initialPreferences = $statistik->preferences;

$systemfilter = new rp_system_filter();
$isdefault = $isprivate = false;
$person_id = null;
$person = new person();
if ($person->getPersonFromBenutzer($uid))
{
	$person_id = $person->person_id;
}

// preferences je nach angewendeten systemfilter ändern
if ($systemfilter->load($statistik_kurzbz, $person_id, $systemfilter_id))
{
	if (isset($systemfilter->filter_id) && is_numeric($systemfilter->filter_id))
	{
		$statistik->preferences = $systemfilter->getPreferencesString();
		if ($systemfilter->default_filter === true)
			$isdefault = true;
		$isprivate = isset($systemfilter->filter_id) && isset($systemfilter->person_id) && is_numeric($systemfilter->person_id);
	}
}

$putlog = false;
if(isset($_REQUEST["putlog"]) && $_REQUEST["putlog"] === "true")
{
	$putlog = true;
}

if($putlog === true)
{
	$filter = new filter();
	$filter->loadAll();

	$log = new webservicelog();
	$log->request_data = $filter->getVars();
	$log->webservicetyp_kurzbz = 'reports';
	$log->request_id = $statistik_kurzbz;
	$log->beschreibung = 'statistik';
	$log->execute_user = $uid;
	$log->save(true);
}

$statistik->loadData();
?>

<?php if($htmlbody): ?>
<html>
	<head>
		<link rel="stylesheet" href="../../../vendor/nicolaskruchten/pivottable/dist/pivot.min.css" />

		<script type="text/javascript" src="../../../vendor/components/jquery/jquery.min.js"></script>
		<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
		<script type="text/javascript" src="../../../vendor/nicolaskruchten/pivottable/dist/pivot.min.js"></script>
	</head>
	<body>
<?php endif; ?>
		<div class="form-inline">
		<?php if (is_array($allstatistikfilter->result) && count($allstatistikfilter->result) > 0): ?>
			<br><br>
			<select class="form-control" id ="systemfilter">
				<option value="defaultoption">Ansicht wählen...</option>";
				<?php
				if (isset($allstatistikfilter->result))
				{
					foreach ($allstatistikfilter->result as $sysf)
					{
						$selected = $systemfilter->filter_id === $sysf->filter_id ? " selected='selected'" : "";
						$private = isset($sysf->person_id) ? " (p)" : "";
						echo "<option value = '".$sysf->filter_id."' $selected>".$sysf->getFilterName().$private."</option>";
					}
				}
				?>
			</select>
			<?php if ($isprivate): ?>
				<<?php echo ($isdefault ? "label" : "span");?> id="standardsysfilterlabel">
				<input type="checkbox" name="standardsysfilter" id="standardsysfilter"<?php echo ($isdefault ? " checked='checked'" : "");?>>
				Standard
				<?php echo ($isdefault ? "</label>" : "</span>");?>
			<?php endif; ?>
			<br><br>
			<?php if ($isprivate): ?>
				<button class="btn btn-default" id="updateprivatesysfilterbtn">Ansicht überschreiben</button>
				<button class="btn btn-default" id="deleteprivatesysfilterbtn">Ansicht löschen</button>
			<?php endif; ?>
			<div class="input-group">
				<input type="text" placeholder="Ansichtname" class="form-control" id="privatesysfiltername">
				<span class="input-group-btn">
					<button class="btn btn-default" id="addprivatesysfilterbtn">Ansicht anlegen</button>
				</span>
			</div>
		<?php endif; ?>
		</div>
		<hr>
		<div id="pivot">
		</div>
		<?php if($statistik->data): ?>


		<!-- Pivot Renderers -->
	<script type="text/javascript" src="../vendor/c3js/c3/c3.min.js"></script>
  	<script type="text/javascript" src="../vendor/d3/d3/d3.min.js"></script>
    <script type="text/javascript" src="../../../vendor/nicolaskruchten/pivottable/dist/c3_renderers.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../include/js/pivot_renderers/c3_renderers.css">

   <!-- <script type="text/javascript" src="../include/js/pivot_renderers/csv_renderer.js"></script>-->
    <script type="text/javascript" src="../../../vendor/nicolaskruchten/pivottable/dist/export_renderers.min.js"></script>

	<!-- Pivot Sprachen -->
	<script type="text/javascript" src="../../../public/js/pivottable/pivot.de.js"></script>
	<script type="text/javascript" src="../include/js/pivot_renderers/de/c3.de.js"></script>

	<script type="text/javascript">
	$(function()
	{

		var lang = "de";
		var derivers = $.pivotUtilities.derivers;
		var renderers =
		$.extend
		(
			$.pivotUtilities.locales[lang].renderers,
			$.pivotUtilities.locales[lang].c3_renderers,
			$.pivotUtilities.export_renderers
		);

		var options =		<?php echo $statistik->preferences ? : '{}' ?>;
		options.renderers = renderers;

		//aggregators and sorters stay as in initial filter preferences
		var initialOptions = <?php echo $initialPreferences ? : '{}'; ?>;
		if (initialOptions.aggregators)
			options.aggregators = initialOptions.aggregators;

		if (initialOptions.sorters)
			options.sorters = initialOptions.sorters;

		//save options in local storage for retrieval in reporting.js
		var optionstosave = JSON.parse(JSON.stringify(options));

		if (typeof(Storage) !== "undefined")
		{
			localStorage.setItem("FHC_reporting_preferences", JSON.stringify(optionstosave));
		}

		//executed for each user action, new options are saved in local storage
		options.onRefresh = function(config) {
			if (typeof(Storage) !== "undefined")
			{
				var config_copy = JSON.parse(JSON.stringify(config));

				//delete some values which are functions
				delete config_copy["aggregators"];
				delete config_copy["renderers"];

				//delete some bulky default values
				delete config_copy["rendererOptions"];
				delete config_copy["localeStrings"];

				localStorage.setItem("FHC_reporting_preferences", JSON.stringify(config_copy));
			}
		};

		var dateFormat =	 $.pivotUtilities.derivers.dateFormat;
		var sortAs =		 $.pivotUtilities.sortAs;
		var tpl =			$.pivotUtilities.aggregatorTemplates;
		var numberFormat =   $.pivotUtilities.numberFormat;

		var deFormat =	   numberFormat({thousandsSep:".", decimalSep:","});
		var deFormatInt =	   numberFormat({digitsAfterDecimal: 0,thousandsSep:".", decimalSep:","});

		$("#pivot").pivotUI(<?php echo $statistik->db_getResultJSON($statistik->data) ?>,options,false,lang);

		// Check if Browser is IE -> Then hide ExcelExportButton
		var ua = window.navigator.userAgent;
		var msie = ua.indexOf("MSIE ");

		if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))  // If Internet Explorer, return version number
		{
			$("#excelExportButton").hide();
		}
	});


		</script>
		<script type="text/javascript">
		var tableToExcel = (function() {
			var uri = 'data:application/vnd.ms-excel;base64,'
				, template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">'
						+ 	'	<head>'
						+ '			<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name>'
						+ '			<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->'
						+ '			<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">'
						+ '		</head>'
						+ '		<body>'
						+ '			<table>{table}</table>'
						+'		</body>'
						+	'</html>'
				, base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) }
				, format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) };

			return function(name, filename) {
				var table = $("#pivot .pvtUi .pvtTable");
				var ctx = {worksheet: name || 'Worksheet', table: table.html()};

				$(this).prop("download", filename);
				var dlink = $("#dlink");
				dlink.prop("href", uri + base64(format(template, ctx)));
				dlink.prop("download", filename);
				dlink[0].click();
			}
			})();

		</script>
		<br>
		<!--<a onclick="exportChartCSV()" style="cursor:pointer" target="_blank">CSV Rohdaten herunterladen</a><br>-->
		<button style="display: inline; height:30px;" onclick="exportChartCSV()" class="btn btn-default" type="button">CSV Rohdaten herunterladen</button><br>
		<button id="excelExportButton" style="display: inline; height:30px;" onclick="tableToExcel('Statistik', '<?php echo $statistik_kurzbz; ?>.xls')" class="btn btn-default" type="button">Excel-Export</button>
		<a id="dlink" href="#pvtTableID" style="display:none;"></a>

		<?php endif; ?>

		<?php
		//display description if content_id is set
		if (!is_null($statistik->content_id))
		{
			echo '	
				<br><br><br><br>					
				<div class="panel panel-default" style="width: 80%;">			
					<iframe style="border-style: none; padding: 20px; width: 100%; overflow: hidden; min-height: 500px;" src="'. APP_ROOT . 'cms/content.php?content_id=' . $statistik->content_id .'" />
				</div>
			';
		}
		?>

<?php if($htmlbody): ?>
	</body>
</html>
<?php endif;
