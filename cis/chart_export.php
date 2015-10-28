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
require_once('../include/phantom.class.php');



if(!isset($_POST['filename'])
|| !isset($_POST['type'])
|| !isset($_POST['width'])
|| !isset($_POST['scale'])
|| !isset($_POST['svg']))
	die("Nicht gen&uuml;gend Parameter erhalten!");


$filename = $_POST['filename'];
$type = $_POST['type'];
$width = $_POST['width'];
$scale = $_POST['scale'];
$svg = $_POST['svg'];


if($type === "image/png" || $type === "image/jpeg")
{
	$shorttype = str_replace("image/", "", $type);

	$ph = new phantom();
	$p = $ph->render(array("infile" => $svg, "scale" => $scale, "width" => $width, "type" => $shorttype));

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
else
	die("Dateityp \"".$type."\" wird noch nicht unterstützt!");

?>
