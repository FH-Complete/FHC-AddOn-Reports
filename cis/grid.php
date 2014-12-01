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

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);
/* if(!$rechte->isBerechtigt('addon/reports'))
  {
  die('Sie haben keine Berechtigung fuer dieses AddOn');
  } */

$statistik = new statistik();

$statistik_kurzbz = filter_input(INPUT_GET, 'statistik_kurzbz');
$htmlbody = filter_input(INPUT_GET, 'htmlbody', FILTER_VALIDATE_BOOLEAN);

if(isset($statistik_kurzbz))
{
	$statistik->load($statistik_kurzbz);
}
else
{
	die('"statistik_kurzbz" is not set!');
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

$statistik->loadData();

$html = '';
if($htmlbody)
{
	$html .= '<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<!-- ***************** NG-Grid ****************** -->
		<link rel="stylesheet" type="text/css" href="../include/js/ngGrid/ng-grid.css" />
        <link rel="stylesheet" type="text/css" href="../include/js/ngGrid/gridstyle.css" />
        <script type="text/javascript" src="../include/js/jquery.min.js"></script>
        <script type="text/javascript" src="../include/js/ngGrid/angular.min.js"></script>
        <script type="text/javascript" src="../include/js/ngGrid/ng-grid-csv-export.js"></script>
        
        <script type="text/javascript" src="../include/js/ngGrid/ng-grid.debug.js"></script>
        ';
}

if($htmlbody)
{
	$html .= '</head><body>';
}
$html .= '<div ng-app="ngApp">
		<div ng-controller="ngCtrl">
			<div class="gridStyle" ng-grid="gridOptions" style="height:400px;">
			</div>
		</div>
	  </div>';

$html .= '<script type="text/javascript">
	//alert ("test");
	var data = '.($statistik->db_getResultJSON($statistik->data)).',
		app = angular.module("ngApp", ["ngGrid"]);

	app.run(function($rootScope) {
		console.log(321);
		console.log($("#content"));
		$rootScope.$apply($("#content"));
	});

	app.controller("ngCtrl", function($scope) {
		$scope.ngData = data;
		$scope.gridOptions = {
			data: "ngData",
			plugins: [new ngGridCsvExportPlugin()],
			showFooter: true,
			showGroupPanel: true,
			enableColumnReordering: true,
			enableColumnResize: true,
			showColumnMenu: true,
			showFilter: true
		};
	});
</script>
<!--<script src="../include/js/nggrid.js" type="application/javascript"></script>-->
';
if($htmlbody)
{
	$html .= '</body>';
}

/* if ($htmlbody)
  $html.=$statistik->get_htmlhead();
  if ($htmlbody)
  $html.="\n\t\t<title>".$statistik->title."</title>\n\t</head>\n\t<body style='height:100%'>";
  $html.=$statistik->get_htmlform();
  $html.=$statistik->get_htmldiv();
  if ($htmlbody)
  $html.="\n\t</body>\n</html>";
 */
if($html == '')
{
	$statistik->printPng();
}
else
{
	echo $html;
}
