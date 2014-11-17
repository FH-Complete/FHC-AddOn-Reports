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
 
require_once('../reports.config.inc.php');
require_once(FHC_ROOT.'config/vilesci.config.inc.php');
require_once(FHC_ROOT.'include/functions.inc.php');
require_once(FHC_ROOT.'include/benutzerberechtigung.class.php');

// Rechte pruefen
$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('addon/reports'))
{
	die('Sie haben keine Berechtigung fuer dieses AddOn');
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html style="height:100%">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
	<title>FHC AddOn Reports</title>
</head>
<body style="height:100%">
	<span valign="middle" style="font-size: larger; vertical-align:middle;"><strong>Reports</strong>
	<input type="image" onclick="document.getElementById('rp_main').src='../../../vilesci/statistik/filter_frameset.html'" title="Filter" src="preferences-desktop.svg" height="30" />
	<input type="image" onclick="document.getElementById('rp_main').src='data_frameset.html'" title="Data" src="x-office-spreadsheet.svg" height="30"/>
	<input type="image" onclick="document.getElementById('rp_main').src='chart_frameset.html'" title="Charts" src="Graphs_clip_art.svg" height="30" />
	<input type="image" onclick="document.getElementById('rp_main').src='report_frameset.html'" title="Reports" src="x-office-presentation.svg" height="30" />
	</span>
	<hr align="left" width="100%">
	<iframe id="rp_main" src="" width="100%" height="90%" style="height:85%" frameborder="0" border="0">
	</iframe>
</body>
</html>
