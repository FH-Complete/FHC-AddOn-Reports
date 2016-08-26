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

// pChart library inclusions
require_once("../include/pChart/class/pData.class.php");
require_once("../include/pChart/class/pDraw.class.php");
require_once("../include/pChart/class/pImage.class.php");

require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/filter.class.php');
require_once('../include/rp_chart.class.php');

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

$chart_id = filter_input(INPUT_GET, 'chart_id');
$htmlbody = filter_input(INPUT_GET, 'htmlbody', FILTER_VALIDATE_BOOLEAN);

$chart_ids = explode(',', $chart_id);
$chart_anz = count($chart_ids);
$class = '';

if($chart_anz > 1 && $chart_anz < 5) {
	$class = 'chart2';
} elseif($chart_anz >= 5) {
	$class = 'chart3';
}

$chart=new chart();

if ($htmlbody): ?>
	<!DOCTYPE HTML>
	<html>
		<head>
			<?php echo $chart->getAllHtmlHead() ?>
			<title><?php echo $chart->title ?></title>
		</head>
		<body>
<?php endif;

		foreach($chart_ids as $id):

			$chart->load($id);

			$i=0;
			while (isset($_GET['varname'.$i]))
			{
				$chart->vars.='&'.$_GET['varname'.$i].'=';
				if (isset($_GET['var'.$i]))
					$chart->vars.=$_GET['var'.$i];
				else
					die('"var"'.$i.' is not set!');
				$i++;
			}

			$htmlDiv = $chart->getHtmlDiv($class);

			if(!$htmlDiv)
				die ($chart->errormsg);


			echo $htmlDiv;
		endforeach;

		echo $chart->getFooter();

if ($htmlbody): ?>
		</body>
	</html>
<?php endif;
