<?php
/* Copyright (C) 2015 FH Technikum-Wien
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
 * Authors: Andreas Österreicher <oesi@technikum-wien.at>
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../reports.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../include/rp_chart.class.php');

$uid = get_uid();
$data = array();

if(isset($_GET['page']))
{
	if(defined('CUSTOM_DASHBOARD'))
	{
		$custom_dashboard = unserialize(CUSTOM_DASHBOARD);
		if(is_array($custom_dashboard) && isset($custom_dashboard[$_GET['page']]))
		{
			foreach($custom_dashboard[$_GET['page']] as $row)
			{
				$chart = new stdClass();
				$chart->chart_id = $row['chart_id'];
				$chart->layout = $row['dashboard_layout'];
				$data[] = $chart;
			}
		}
	}
}
else
{
	$chart_obj = new chart();
	$chart_obj->getDashboard();

	foreach($chart_obj->result as $row)
	{
		$chart = new stdClass();
		$chart->chart_id = $row->chart_id;
		$chart->layout = $row->dashboard_layout;
		$data[] = $chart;
	}
}

echo json_encode($data);
?>
