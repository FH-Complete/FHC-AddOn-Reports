<?php

/*
 * Copyright (C) 2014 fhcomplete.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Authors: Robert Hofer <robert.hofer@technikum-wien.at>
 *			Andreas Moik <moik@technikum-wien.at>
 */

require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/filter.class.php');
require_once('../include/chart.class.php');


$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

$chart = new chart();

$chart_id = filter_input(INPUT_GET, 'chart_id', FILTER_VALIDATE_INT);

$filter = $_GET;
unset($filter['chart_id']);

$chart->vars = '&' . http_build_query($filter);

if(!$chart->load($chart_id))
	die("Der Chart konnte nicht geladen werden!");

if($chart->publish !== true)
	die("Dieser Chart ist nicht Oeffentlich");

if(!isset($chart->statistik_kurzbz))
	die("Fuer diesen Chart gibt es keine Statistik");


$chart->statistik = new statistik($chart->statistik_kurzbz);


if(isset($chart->statistik->berechtigung_kurzbz))
	if(!$rechte->isBerechtigt($chart->statistik->berechtigung_kurzbz))
		die("Sie haben keine Berechtigung fuer diesen Chart");

$htmlDiv = $chart->getHtmlDiv();

if(!$htmlDiv)
	die ($chart->errormsg);

echo $htmlDiv;
?>

