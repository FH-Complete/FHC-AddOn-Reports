<?php
/* Copyright (C) 2015 Technikum-Wien
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
//require_once(dirname(__FILE__).'/config/vilesci.config.inc.php'); Muss vor dieser Datei eingebunden werden!

$dr = DOC_ROOT;
$dr = str_replace($_SERVER["DOCUMENT_ROOT"], "", $dr);
if($dr=='')
	$dr='/';

//Originaldateien des Herstellers
echo '<script src="'.$dr.'addons/reports/vendor/FHC-vendor/highcharts/js/highcharts.js"></script>';
echo '<script src="'.$dr.'addons/reports/vendor/FHC-vendor/highcharts/js/highcharts-more.js"></script>';
echo '<script src="'.$dr.'addons/reports/vendor/FHC-vendor/highcharts/js/highcharts-3d.js"></script>';
echo '<script src="'.$dr.'addons/reports/vendor/FHC-vendor/highcharts/js/modules/exporting.js"></script>';
echo '<script src="'.$dr.'addons/reports/vendor/FHC-vendor/highcharts/js/modules/drilldown.js"></script>';
echo '<script src="'.$dr.'addons/reports/vendor/FHC-vendor/highcharts/js/modules/no-data-to-display.js"></script>';


//Anpassungen
/*
 * ...
 * ...
 * ...
 */

?>
