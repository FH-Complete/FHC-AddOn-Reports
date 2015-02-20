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
 
//require_once('../reports.config.inc.php');
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');

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
<html class="reports">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css">
		<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
		<title>FHC AddOn Reports</title>
	</head>
	<body>
		<div class="icons">
			<strong>Reports</strong>
			<a href="../../../vilesci/statistik/filter_frameset.html" target="rp_main">
				<img src="../include/images/preferences-desktop.svg" alt="" />
			</a>
			<a href="data_frameset.html" target="rp_main">
				<img src="../include/images/x-office-spreadsheet.svg" alt="" />
			</a>
			<a href="chart_frameset.html" target="rp_main">
				<img src="../include/images/Graphs_clip_art.svg" alt="" />
			</a>
			<a href="report_frameset.html" target="rp_main">
				<img src="../include/images/x-office-presentation.svg" alt="" />
			</a>
		</div>
		<hr>
		<iframe id="rp_main" name="rp_main">
		</iframe>
	</body>
</html>
