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

// pChart library inclusions
require_once("../include/pChart/class/pData.class.php");
require_once("../include/pChart/class/pDraw.class.php");
require_once("../include/pChart/class/pImage.class.php");

require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/filter.class.php');
require_once('../../../include/statistik.class.php');
require_once('../../../include/webservicelog.class.php');
require_once('../../../include/filter.class.php');

ini_set('memory_limit', '1024M');
$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);


$statistik = new statistik();

$statistik_kurzbz = filter_input(INPUT_GET, 'statistik_kurzbz');
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
		die("Sie haben keine Berechtigung fuer diese Statistik");


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
		<link rel="stylesheet" href="../../../submodules/pivottable/dist/pivot.min.css" />
		<script type="text/javascript" src="../include/js/jquery-1.11.2.min.js"></script>
		<script type="text/javascript" src="../include/js/jquery-ui.1.11.2.min.js"></script>
		<script type="text/javascript" src="../../../submodules/pivottable/dist/pivot.min.js"></script>
	</head>
	<body>
<?php endif; ?>
		<div id="pivot">
		</div>
		<?php if($statistik->data): ?>


		<!-- Pivot Renderers -->
    <script type="text/javascript" src="../include/js/pivot_renderers/c3_renderers.js.map"></script>
    <script type="text/javascript" src="../include/js/d3.min.js"></script>

    <script type="text/javascript" src="../include/js/pivot_renderers/c3_renderers.js"></script>
		<link rel="stylesheet" type="text/css" href="../include/js/pivot_renderers/c3_renderers.css">


    <script type="text/javascript" src="../include/js/pivot_renderers/csv_renderer.js"></script>


		<!-- Pivot Sprachen -->
		<script type="text/javascript" src="../include/js/pivot.de.js"></script>
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

				var options =        <?php echo $statistik->preferences ? : '{}' ?>;
				options.renderers = renderers;
				var dateFormat =     $.pivotUtilities.derivers.dateFormat;
				var sortAs =         $.pivotUtilities.sortAs;
				var tpl =            $.pivotUtilities.aggregatorTemplates;
				var numberFormat =   $.pivotUtilities.numberFormat;

				var deFormat =       numberFormat({thousandsSep:".", decimalSep:","});
				var deFormatInt =       numberFormat({digitsAfterDecimal: 0,thousandsSep:".", decimalSep:","});

				$("#pivot").pivotUI(<?php echo $statistik->db_getResultJSON($statistik->data) ?>,options,false,lang);

			});

		</script>
		<?php endif; ?>

<?php if($htmlbody): ?>
	</body>
</html>
<?php endif;
