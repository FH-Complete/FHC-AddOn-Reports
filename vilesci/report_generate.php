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
	require_once('../include/chart.class.php');
	require_once('../../../include/statistik.class.php');
	require_once('../../../include/process.class.php');
	
	if (!$db = new basis_db())
		die('Es konnte keine Verbindung zum Server aufgebaut werden.');
	
	// ************** Rechte Pruefen ********************
	$user = get_uid();
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($user);
	
	if(!$rechte->isBerechtigt('addon/reports'))
		die('Sie haben keine Berechtigung fuer dieses AddOn!');
	// @todo Rechte der Daten und Charts pruefen
	
	
	// *************** Parameter pruefen und Daten laden *******************
	$report = new report();
	if(isset($_REQUEST["report_id"]))
		$report->load((int)$_REQUEST["report_id"]);
	else
		die('report_id is not set');
	$charts = new chart();
	if (!$charts->loadCharts($report->report_id))
		die($charts->errormsg);
	foreach($charts->chart as $chart)
	{
		$chart->statistik = new statistik($chart->statistik_kurzbz);
		if (!$chart->statistik->loadData())
			die ('Data not loaded!<br/>'.$chart->statistik->errormsg);
		// echo 'Data loaded<br/>'.$chart->statistik->csv;ob_flush();flush();
		$datafile='../data/data'.$chart->statistik->statistik_kurzbz.'.csv';
		if (!$chart->statistik->writeCSV($datafile,',','"'))
			die('File ../data/data'.$chart->statistik->statistik_kurzbz.'not written!<br/>'.$chart->statistik->errormsg);
		if (!$outputfilename=$chart->writePNG($datafile))
			die ($chart->errormsg);
		echo '<br/>OutputFileName: '.$outputfilename.'<br/>';
	}
	// @todo weitere parameter pruefen	
		
	// *************** Startwerte Setzen ************************
	$crlf=PHP_EOL;
	$content = '';
	$htmlstr = '';
	$ext='';
	$errorstr = ''; //fehler beim insert

	switch ($report->format)
	{
		case 'asciidoc': $ext='.asciidoc';
			$content.='= Report - '.$report->title.$crlf;
			$content.=$report->header.$crlf.$report->printParam('attr',$crlf).$crlf;
			$content.=$crlf.'== Beschreibung'.$crlf.$report->description.$crlf;
			$content.=$crlf.'[horizontal]'.$crlf.'*Parameter*::'.$crlf.'- *Erstellung*: '.date("D, j M Y").$crlf.'- *Datenstand*: '.date(DATE_RFC2822).$crlf.$report->printParam('param',$crlf).$crlf;
			$content.=$crlf.'== Report'.$crlf.$report->body.$crlf;
			$content.=$crlf.'== Hinweise'.$crlf.$report->footer.$crlf;
			break;
	}
	// ***** Define Filenames ******************
	$tmpFilename='../data/Report'.$report->report_id.date('Y-m-d_H:i:s').'.tmp';
	$filename='../data/Report'.$report->report_id.$ext;
	$docinfoFilename='../data/Report'.$report->report_id.'-docinfo.xml';
	$htmlFilename='../data/Report'.$report->report_id.'.html';
	$xmlFilename='../data/Report'.$report->report_id.'.xml';
	$pdfFilename='../data/Report'.$report->report_id.'.pdf';
	
	// **** Write DocInfo
	$fh=fopen($docinfoFilename,'w');
	fwrite($fh,$report->docinfo);
	fclose($fh);
	$htmlstr.=$docinfoFilename.' is written!<br/>';
	
	// ***** Write ContentFile
	$fh=fopen($filename,'w');
	fwrite($fh,$content);
	fclose($fh);
	$htmlstr.=$filename.' is written!<br/>';
	
	// ****** Create Destination Files
	$htmlstr.=exec('asciidoctor -o '.$htmlFilename.' '.$filename);
	$htmlstr.=$htmlFilename.' is written!<br/>';
	$command='asciidoc -a docinfo -b docbook -o '.$xmlFilename.' '.$filename;
	$htmlstr.=exec($command);
	echo $command.'<br/>';
	$htmlstr.=$xmlFilename.' is written!<br/>';
	// DB Latex is tricky so i used a new process
	$command='dblatex -f docbook -t pdf -P latex.encoding=utf8 -P latex.unicode.use=1 -o '.$tmpFilename.' '.$xmlFilename;
	echo $command.'<br/>';
	$lastout=exec($command, $output, $exit); //exec($command ,$op);
    var_dump($lastout); echo '<br/';  
	var_dump($output);   echo '<br/';  
	var_dump($exit);   echo '<br/';  
	$process = new process(escapeshellcmd($command));
	for ($i=0;$process->status() && $i<10;$i++)
	{
		echo "<br/>The process is currently running";ob_flush();flush();
		usleep(200000); // wait for 0.2 Seconds
	}
	if ($process->status())
	{
		$process->stop();
		die ('<br/>Timeout in dbLatex execution!<br/>'.escapeshellcmd($command).'<br/>');
	}
	elseif (@fopen($tmpFilename,'r'))
	{
		// move file
		if (!rename($tmpFilename,$pdfFilename))
			die ('<br/>Cannot remove File from '.$tmpFilename.' to '.$pdfFilename.'<br/>');
	}
	else
	{
		var_dump($process);
		die('<br/>Cannot read File: '.$tmpFilename.'<br/>Maybe dblatex failed!<br/>'.escapeshellcmd($command));
	}
	$htmlstr.=$pdfFilename.' is written!<br/>';
	
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
