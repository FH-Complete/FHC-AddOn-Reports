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

$person_id = null;
$person = new person();
if ($person->getPersonFromBenutzer($uid))
{
	$person_id = $person->person_id;
}

$initialPreferences = $statistik->preferences;

$systemfilter = new rp_system_filter();

// preferences je nach angewendeten systemfilter ändern
if ($systemfilter->load($statistik_kurzbz, $person_id, $systemfilter_id))
{
	if (isset($systemfilter->filter_id) && is_numeric($systemfilter->filter_id))
	{
		$statistik->preferences = $systemfilter->getPreferencesString();
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
<?php if($statistik->data): ?>
	<div id="sysfilterblock">
		<?php
			$collapseFilterBlock = true;
			include('./systemfilter_block_view.php')
		?>
	</div>
	<div id="pivot">
	</div>

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
	<script type="text/javascript" src="./systemfilter.js"></script>
	<script type="text/javascript">
		var GLOBAL_OPTIONS_STORAGE = null;//options for pivot are stored here and retrieved in systemfilter.js
		//useInitialOptions - if true, ignore any systemfilters and use options from tbl_statistik
		function drawPivotUI(options, useInitialOptions)
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

			var dateFormat =	 $.pivotUtilities.derivers.dateFormat;
			var sortAs =		 $.pivotUtilities.sortAs;
			var tpl =			$.pivotUtilities.aggregatorTemplates;
			var numberFormat =   $.pivotUtilities.numberFormat;

			var deFormat =	   numberFormat({thousandsSep:".", decimalSep:","});
			var deFormatInt =	   numberFormat({digitsAfterDecimal: 0,thousandsSep:".", decimalSep:","});

			var dataset = <?php echo $statistik->db_getResultJSON($statistik->data); ?>;
			var initialOptions = <?php echo $initialPreferences ? : '{}' ?>;

			if (useInitialOptions)
				options = initialOptions;
			else
			{
				options = options ? options : <?php echo $statistik->preferences ?: '{}' ?>;

				options.renderers = renderers;

				if (initialOptions.aggregators)
					options.aggregators = initialOptions.aggregators;

				if (initialOptions.sorters)
					options.sorters = initialOptions.sorters;

				if (initialOptions.rendererOptions)
					options.rendererOptions = initialOptions.rendererOptions;
			}

			GLOBAL_OPTIONS_STORAGE = options;

			//executed for each user action, new options are saved globally
			options.onRefresh = function (config) {
				var config_copy = JSON.parse(JSON.stringify(config));

				//delete some values which are functions
				delete config_copy["aggregators"];
				delete config_copy["renderers"];

				//delete some bulky default values
				delete config_copy["rendererOptions"];
				delete config_copy["localeStrings"];

				GLOBAL_OPTIONS_STORAGE = config_copy;

				// Wenn die Option "parseHTML" true ist, werden HTML-Elements als solche ausgegeben
				if (options.parseHTML == true)
				{
					$('th.pvtRowLabel').each(function () {
						$(this).html($(this).text());
					});
					$('td.pvtVal').each(function () {
						$(this).html($(this).text());
					});
					$('th.pvtColLabel').each(function () {
						$(this).html($(this).text());
					})
				}
				
				// Wenn die Option "hideTotals" true ist, Total-Zeile und Spalten verstecken
				if (options.hideTotals == true)
				{
					$(".pvtTable").find('.rowTotal').addClass('hidden');
					$(".pvtTable").find('.colTotal').addClass('hidden');
					$(".pvtTable").find('.pvtGrandTotal').addClass('hidden');
					$(".pvtTable").find('.pvtTotalLabel').addClass('hidden');
				}

				// Wenn die Option "showLineNumber" true ist, wird als erste Spalte die Zeilennummer angezeigt
				if (options.showLineNumber == true)
				{
					$('.pvtTable > thead').find('th').eq(0).before('<th class="pvtAxisLabel">#</th>');
					$('.pvtTable > tbody  > tr').each(function (index) {
						zeilennummer = index+1;
						rowspan = $(this).find('th').eq(0).attr('rowspan');
						if (!$(this).find('th').eq(0).hasClass('hidden'))
						{
							$(this).find('th').eq(0).before('<th class="pvtRowLabel" rowspan="' + rowspan + '">' + zeilennummer + '</th>');
						}
					});
				}

				// Wenn die Option "showLineCount" true ist, wird die Summe der Zeilen angezeigt
				if (options.showLineCount == true)
				{
					// Eine Zeile für Summenzeile abziehen
					rowCount = $('.pvtTable > tbody  > tr').length -1 ;
					// Wenn die Option "hideTotals" true ist, wieder eine Zeile dazuzählen
					if (options.hideTotals == true)
					{
						rowCount = rowCount+1;
					}
					if ( $( "#rowCount" ).length )
					{
						$('#rowCount').html(rowCount + ' Zeilen');
					}
					else
					{
						$('.pvtUiCell').eq(0).append('<p id="rowCount" style="color: grey; font-size: small; text-align: center; padding-top: 5px;">' + rowCount + ' Zeilen</p>');
					}
				}

				// Wenn die Option "showEmailButton" true ist, wird der Button zum senden von E-Mails angezeigt
				if (options.showEmailButton == true)
				{
					$("#sendPivotMailButton").show();
				}
				else
				{
					$("#sendPivotMailButton").hide();
				}
			};

			$("#pivot").pivotUI(dataset,options,true,lang);// true - rerender on repeat call

			// Check if Browser is IE -> Then hide ExcelExportButton
			var ua = window.navigator.userAgent;
			var msie = ua.indexOf("MSIE ");

			if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))  // If Internet Explorer, return version number
			{
				$("#excelExportButton").hide();
			}
		}

		$(function()
		{
			drawPivotUI();

			$('#sendPivotMailButton').on('click', function()
			{
				var mailstring = '';
				var submailstring = '';
				var splitposition = 0;
				var idx = 0;
				$('.pvtRowLabel').each(function()
				{
					function extractEmails(text)
					{
						return text.match(/([a-zA-Z0-9._+-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9._-]+)/gi);
					}

					var emails = extractEmails($(this).text());

					$.each(emails, function(index, email)
					{
						var divHtml = email + ';';
						mailstring += divHtml;
						if(mailstring.length > 2048)//2048
						{
							// Alert nur einmalig ausgeben
							if (idx == 0)
							{
								alert('Aufgrund der großen Anzahl an EmpfängerInnen, muss die Nachricht auf mehrere E-Mails aufgeteilt werden!');
								idx = 1;
							}
							splitposition = mailstring.indexOf(';',1900);//1900
							submailstring = mailstring.substring(0,splitposition);
							window.location = 'mailto:<?php echo $uid.'@'.DOMAIN;?>?bcc= ' + submailstring;
							mailstring = mailstring.substring(splitposition);
						}
						else
						{

						}
					});
				});
				//console.log(mailstring);
				window.location = 'mailto:<?php echo $uid.'@'.DOMAIN;?>?bcc= ' + mailstring;
			});

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
	<p><button style="display: inline; height:30px;" onclick="exportChartCSV()" class="btn btn-default" type="button">CSV Rohdaten herunterladen</button></p>
	<p><button id="excelExportButton" style="display: inline; height:30px;" onclick="tableToExcel('Statistik', '<?php echo $statistik_kurzbz; ?>.xls')" class="btn btn-default" type="button">Excel-Export</button></p>
	<p><button id="sendPivotMailButton" style="display: none; height:30px;" class="btn btn-default" type="button">E-Mail senden</button></p>
	<a id="dlink" href="#pvtTableID" style="display:none;"></a>

	<?php endif; ?>

<?php if($htmlbody): ?>
	</body>
</html>
<?php endif;
