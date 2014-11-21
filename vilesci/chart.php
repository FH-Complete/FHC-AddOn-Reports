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
require_once('../include/chart.class.php');
$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);
/*if(!$rechte->isBerechtigt('addon/reports'))
{
	die('Sie haben keine Berechtigung fuer dieses AddOn');
}*/

$chart=new chart();
if (isset($_GET['chart_id']))
	$chart->load($_GET['chart_id']);
else
	die('"chart_id" is not set!');
$i=0;
while (isset($_GET['varname'.$i]))
{
	$chart->vars.='&'.$_GET['varname'.$i].'=';
	if (isset($_GET['var'.$i]))
		$chart->vars.=$_GET['var'.$i];
	else
		die('"var"'.$i.' is not set!');
	$i++;
}
if (isset($_GET['htmlbody']))
{
	if ($_GET['htmlbody']=='true')
		$htmlbody=true;
	else
		$htmlbody=false;
}
else
	$htmlbody=false;

$html='';
if ($htmlbody)
	$html.='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html style="height:100%">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css" />
		';
if ($htmlbody)
	$html.=$chart->getHtmlHead();
if ($htmlbody)
	$html.="\n\t\t<title>".$chart->title."</title>\n\t</head>\n\t<body style='height:100%'>";
//$html.=$chart->get_htmlform();
$html.=$chart->getHtmlDiv();
$html.=$chart->getFooter();
if ($htmlbody)
	$html.="\n\t</body>\n</html>";

if ($html=='')
	$chart->printPng();
else
	echo $html;
?>
