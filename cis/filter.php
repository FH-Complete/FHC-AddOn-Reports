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
 * Authors: Andreas Österreicher <andreas.oesterreicher@technikum-wien.at>
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/globals.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/statistik.class.php');
require_once('../../../include/filter.class.php');

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('basis/statistik'))
	die('Sie haben keine Berechtigung fuer diese Seite!');

if(!isset($_GET['htmlbody']))
	$htmlbody='false';
else
	$htmlbody=$_GET['htmlbody'];
	
if(!isset($_GET['type']))
	die('type is not set');
	
$filter=new filter();
$filter->loadAll();
$getvars=$filter->getVars();
$statistik=new statistik();
$action='';
//$chart=new chart();
switch ($_GET['type'])
{
	case 'data':
		if (isset($_GET['statistik_kurzbz']))
			if ($statistik->load($_GET['statistik_kurzbz']))
			{
				$vars = $statistik->parseVars($statistik->sql);
				$action='grid.php?htmlbody=true';
				//var_dump($vars);
				//var_dump($statistik);
			}
			else
				die('Statistik not found in DB!');
}

$action.=$getvars;

$html='';

if($htmlbody)
$html='
<!DOCTYPE HTML>
<html>
	<head>
	<title>Filter</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
</head>
<body>
';
// onsubmit='alert(\"$action\");'
$html.="
	<form action='$action' method='POST' target='iframe_content' >
	<table>
";
// Filter parsen
$html.= '<tr>';
foreach($vars as $var)
{
	if ($filter->isFilter($var))
		$html.= "<td>$var</td><td>".$filter->getHtmlWidget($var)."</td>\n";
	else
		$html.= "<td>$var</td><td><input type=\"text\" id=\"$var\" name=\"$var\" value=\"\"></td>";
}
//$html.='</tr><tr>';
$html.='
		<td></td>
		<td><input class="btn btn-default" type="submit" value="Run >>"></td>
	</tr>
	</table>
</form>';
if ($htmlbody)
	$html.= '</body></html>';
echo $html;
?>
