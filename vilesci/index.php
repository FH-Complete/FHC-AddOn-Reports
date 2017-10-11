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
 *					Andreas Moik <moik@technikum-wien.at>
 */

//require_once('../reports.config.inc.php');
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');

// Rechte pruefen
$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('addon/reports_verwaltung'))
{
	die($rechte->errormsg);
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
		<style>
			.vilesci_menue_entry
			{
				margin-left:  5px;
				margin-right: 5px;
			}
		</style>
	</head>
	<body>
		<div class="icons">
			<strong>Reports</strong>
			<a title="Filter" href="../../../vilesci/statistik/filter_frameset.html" target="rp_main">
				<img src="../include/images/Filter.svg" alt="" class="vilesci_menue_entry" />
			</a>
			<a title="Views" href="view_frameset.html" target="rp_main">
				<img src="../include/images/View.svg" alt="" class="vilesci_menue_entry" />
			</a>
			<a title="Attribute" href="attribut_frameset.html" target="rp_main">
				<img src="../include/images/Attribut.svg" alt="" class="vilesci_menue_entry" />
			</a>
			<a title="Statistiken" href="data_frameset.html" target="rp_main">
				<img src="../include/images/Statistik.svg" alt="" class="vilesci_menue_entry" />
			</a>
			<a title="Charts" href="chart_frameset.html" target="rp_main">
				<img src="../include/images/Chart.svg" alt="" class="vilesci_menue_entry" />
			</a>
			<a title="Reports" href="report_frameset.html" target="rp_main">
				<img src="../include/images/Report.svg" alt="" class="vilesci_menue_entry" />
			</a>
			<a title="Menü" href="cis_menue.php" target="rp_main">
				<img src="../include/images/Menue.svg" alt="" class="vilesci_menue_entry" />
			</a>
		</div>
		<hr>
		<iframe id="rp_main" name="rp_main">
		</iframe>
	</body>
</html>
