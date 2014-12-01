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

$db = new basis_db();

if(!$db)
{
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');
}

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('basis/statistik'))
{
	die('Sie haben keine Berechtigung fuer diese Seite!');
}

$filter = new filter();
$filter->loadAll();
$statistik = new statistik();

$statistik_kurzbz = filter_input(INPUT_GET, 'statistik_kurzbz');
$htmlbody = filter_input(INPUT_GET, 'htmlbody', FILTER_VALIDATE_BOOLEAN);

if(!isset($statistik_kurzbz))
{
	die('"statistik_kurzbz" is not set!');
}
elseif(!$statistik->load($statistik_kurzbz))
{
	die('Statistik not found in DB!');
}

$vars = $statistik->parseVars($statistik->sql);

$html = '';

if($htmlbody)
{
	$html = '
		<!DOCTYPE HTML>
		<html>
			<head>
			<title>Filter</title>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
		</head>
		<body>';
}

// Filter parsen
foreach($vars as $var)
{
	if($filter->isFilter($var))
	{
		$html .= $var . ': ' . $filter->getHtmlWidget($var);
	}
	else
	{
		$html .= $var . ': <input type="text" id="' . $var . '" name="' . $var . '" value="">';
	}
}

if($htmlbody)
{
	$html .= '</body></html>';
}

echo $html;
