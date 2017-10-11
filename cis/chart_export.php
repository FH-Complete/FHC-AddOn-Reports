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
 * Authors: Andreas Moik <moik@technikum-wien.at>
 */
require_once('../include/rp_phantom.class.php');
require_once('../../../config/cis.config.inc.php');
require_once('../../../include/dokument_export.class.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/filter.class.php');
require_once('../../../include/webservicelog.class.php');
require_once('../include/rp_chart.class.php');


if(!isset($_GET['type']) || $_GET['type'] != 'csv')
{
	if(!isset($_POST['filename'])
	|| !isset($_POST['type'])
	|| !isset($_POST['width'])
	|| !isset($_POST['scale'])
	|| !isset($_POST['svg']))
		die("Nicht gen&uuml;gend Parameter erhalten!");
	$filename = $_POST['filename'];
	$svg = $_POST['svg'];
	$type = $_POST['type'];
}
else
	$type = $_GET['type'];



if($type === "image/png" || $type === "image/jpeg")
{
	$shorttype = str_replace("image/", "", $type);

	$ph = new phantom();
	$p = $ph->render(array("type" => $shorttype, "infile" => $svg));

	header('Content-Disposition: attachment;filename="'.$filename.'.'.$shorttype.'"');
	header('Content-Type: application/force-download');
	echo base64_decode($p);
}
else if($type === "image/svg+xml")
{
	//da das bild schon in svg kommt, muss es nicht per phantom gerendert werden
	$shorttype = "svg";

	header('Content-Disposition: attachment;filename="'.$filename.'.'.$shorttype.'"');
	header('Content-Type: application/force-download');
	echo $svg;
}
else if($type === "application/pdf")
{
	$ph = new phantom();
	$p = $ph->render(array("type" => "png", "infile" => $svg));
	$user = get_uid();
	$pngPath =sys_get_temp_dir().'/export_'.$user.'.png';



	if(!$rsc=fopen($pngPath,'w'))
		die("Das PDF konnte nicht erstellt werden");
	if(!fwrite($rsc,base64_decode($p)))
		die("Das PDF konnte nicht erstellt werden");

	$doc = new dokument_export('HCPDFExport');
	$doc->addImage($pngPath, '100000000000001000000009BE233EADC2452A3F.png', 'image/png');
	$doc->addDataArray(array(),'chart');
	if(!$doc->create('pdf'))
		die($doc->errormsg);
	$doc->output();
	$doc->close();
	unlink($pngPath);
}
else if($type === 'csv')
{
	$uid = get_uid();
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($uid);

	if(!isset($_GET['statistik_kurzbz']))
	{
		die('Statistik_kurzbz wurde nicht gesetzt');
	}

	$statistik_kurzbz = $_GET['statistik_kurzbz'];
	$chart = new chart();
	$chart->statistik = new statistik($statistik_kurzbz);

	if(isset($chart->statistik->berechtigung_kurzbz))
		if(!$rechte->isBerechtigt($chart->statistik->berechtigung_kurzbz))
			die("Sie haben keine Berechtigung fuer diesen Chart");

	$putlog = false;
	if(isset($_REQUEST["putlog"]) && $_REQUEST["putlog"] === "true")
	{
		$putlog = true;
	}

	if($putlog === true)
	{
		$filter = new filter();
		$filter->loadAll();

		$log = new webservicelog();
		$log->request_data = $filter->getVars();
		$log->webservicetyp_kurzbz = 'reports';
		$log->request_id = $statistik_kurzbz;
		$log->beschreibung = 'csv';
		$log->execute_user = $uid;
		$log->save(true);
	}
	$filename = $statistik_kurzbz.'.csv';
	header('Content-Disposition: attachment;filename="'.$filename.'"');
	header('Content-Type: text/csv');
	if($chart->statistik->loadData())
	{
		echo $chart->statistik->csv;
	}
}
else
	die("Dateityp \"".$type."\" wird nicht unterstützt!");


?>
