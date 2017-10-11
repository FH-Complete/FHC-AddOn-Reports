<?php
/* Copyright (C) 2006 Technikum-Wien
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
 */
	require_once('../../../config/vilesci.config.inc.php');
	require_once('../../../include/globals.inc.php');
	require_once('../../../include/functions.inc.php');
	require_once('../../../include/benutzerberechtigung.class.php');
	require_once('../include/rp_attribut.class.php');
	require_once('../../../include/sprache.class.php');
	require_once(dirname(__FILE__).'/../../../vendor/autoload.php');

	$user = get_uid();
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($user);

	if(!$rechte->isBerechtigt('addon/reports_verwaltung'))
		die($rechte->errormsg);

	if(!isset($_REQUEST["attribut_id"]))
		die("keine attribut_id erhalten!");

	$attribut = new rp_attribut($_REQUEST["attribut_id"]);

	$sprache = new sprache();
	$sprache->getAll(true);
	$languages = $sprache->getAllIndexesSorted();



	foreach($languages as $lang)
	{
		$md =  \Michelf\Markdown::defaultTransform($attribut->description[$lang]);
		echo '<h2>'.$lang.':</h2><div style="padding:5px;margin-bottom:50px">'.$md.'</div>';
	}
?>
<html>
	<head>
		<title>DI-Quelle - Details</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
		<script src="../../../include/js/mailcheck.js"></script>
		<script src="../../../include/js/datecheck.js"></script>
		<?php require_once("../../../include/meta/jquery.php"); ?>
	</head>
	<body>
	</body>
</html>


