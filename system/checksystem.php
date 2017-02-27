<?php
/* Copyright (C) 2016 FH Technikum-Wien
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
 *
 * Beschreibung:
 * Dieses Skript prueft die gesamte Systemumgebung und sollte nach jedem Update gestartet werden.
 * Geprueft wird: die Datenbank per "dbupdate_VERSION.php" auf aktualitaet, dabei werden fehlende Attribute angelegt.
 */
require_once('../../../config/system.config.inc.php');
require_once('../../../include/basis_db.class.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/rp_chart.class.php');
require_once('../version.php');
define("CHECKSYSTEM",$addon_name);

// Datenbank Verbindung
$db = new basis_db();

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
	<title>'.$addon_name.' Datenbank Check</title>
</head>
<body>';

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('basis/addon', null, 'suid'))
{
	exit('Sie haben keine Berechtigung für die Verwaltung von Addons');
}

echo '<H1>'.$addon_name.' Systemcheck!</H1>';
echo '<H2>'.$addon_name.' DB-Updates!</H2>';

echo '<div>';
	$dbupdStr = 'dbupdate_'.$fhcomplete_target_version.'.php';
	echo $dbupdStr . ' wird aufgerufen...';
echo '</div>';
echo '<div>';
	require_once($dbupdStr);
echo '</div>';






// ******** Zusätzliche Prüfungen ************/

echo '<h2>'.$addon_name.' Systemprüfung</h2>';

//dblatex
if(!`which dblatex`)
{
	echo '<strong style="color:red;">dblatex nicht installiert:</strong> ohne dblatex können keine Reports generiert werden!<br>';
}
else
{
	echo '- dblatex: OK<br>';
}

//asciidoc mit version
if(!`which asciidoc`)
{
	echo '<strong style="color:red;">asciidoc nicht installiert:</strong> ohne asciidoc können keine Reports generiert werden!<br>';
}
else
{
	exec('asciidoc --version'.' 2>&1', $out, $ret);
	$asciiVer = str_replace("asciidoc ","",$out);
	if(!version_compare ( "8.6.4" , $asciiVer[0], "<" ))
	{
		echo '<strong style="color:red;">asciidoc Version:</strong> Diese asciidoc Version unterstützt nur html4!<br>';
	}
	else
	{
		echo '- asciidoc: OK<br>';
	}

	echo '<br>Prüfung abgeschlossen<br><br>';
}
?>
