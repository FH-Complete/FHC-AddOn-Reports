<?php
/*
 * Copyright (C) 2015 fhcomplete.org
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
 *					Andreas Moik <moik@technikum-wien.at>
 */

require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/filter.class.php');
require_once('../include/rp_chart.class.php');

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

$chart=new chart();
$chart->getDashboard();

if(count($chart->result)>0)
{
	echo $chart->getAllHtmlHead();

	$params = array(
		'Studiensemester' => 'WS2014',
	);

	$get_string = '&' . http_build_query($params);

	usort($chart->result, "dashboard_sort");

	foreach($chart->result as $onechart)
	{
		$onechart->inDashboard = true;
		$onechart->vars = $get_string;
		echo $onechart->getHtmlDiv($onechart->dashboard_layout);
	}

	echo $chart->getFooter();
}


function dashboard_sort($a,$b)
{
	return $a->dashboard_pos>$b->dashboard_pos;
}
