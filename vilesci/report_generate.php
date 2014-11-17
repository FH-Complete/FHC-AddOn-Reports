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
 * Authors: Christian Paminger 	< christian.paminger@technikum-wien.at >
 */
	require_once('../../../config/vilesci.config.inc.php');
	require_once('../../../include/globals.inc.php');
	require_once('../../../include/functions.inc.php');
	require_once('../../../include/benutzerberechtigung.class.php');
	require_once('../include/report.class.php');
	
	if (!$db = new basis_db())
		die('Es konnte keine Verbindung zum Server aufgebaut werden.');
	
	$user = get_uid();
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($user);
	
	if(!$rechte->isBerechtigt('addon/reports'))
		die('Sie haben keine Berechtigung fuer dieses AddOn!');
	
	
	$htmlstr = '';
	$ext='';
	$errorstr = ''; //fehler beim insert

	$report = new report();
	
	if(isset($_REQUEST["report_id"]))
	{
		if(!$rechte->isBerechtigt('addon/reports', null, 'suid'))
			die('Sie haben keine Berechtigung fuer diese Aktion');
	
		$report->load((int)$_REQUEST["report_id"]);
		
	}
	else
		die('report_id is not set');
	switch ($report->format)
	{
		case 'asciidoc': $ext='.ad';
			break;
	}
	$filename='../data/'.$report->report_id.$ext;
	$fh=fopen($filename,'w');
	fwrite($fh,$report->body);
	fclose($fh);
	$htmlstr.=$filename.' is written!';
	
	$htmlstr.=exec('asciidoc -b html5 -o ../data/'.$report->report_id.'.html '.$filename);
	
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Reports - Generate</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
</head>
<body style="background-color:#eeeeee;">

<?php
	echo $htmlstr;
?>

</body>
</html>
