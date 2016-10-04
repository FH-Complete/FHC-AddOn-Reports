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
 * Authors: Christian Paminger     < christian.paminger@technikum-wien.at > and
 *          Andreas Moik           < moik@technikum-wien.at >.
 */
	require_once('../../../config/vilesci.config.inc.php');
	require_once('../../../include/globals.inc.php');
	require_once('../../../include/functions.inc.php');
	require_once('../../../include/benutzerberechtigung.class.php');
	require_once('../include/rp_report.class.php');
	require_once('../include/rp_chart.class.php');
	require_once('../include/rp_report_statistik.class.php');
	require_once('../../../include/process.class.php');
	require_once('../../../include/filter.class.php');

	$iconsdir='/etc/asciidoc/images/icons';
	$reportsTmpDir = sys_get_temp_dir() . "/reports_" . uniqid();
	$pthreadsEnabled = extension_loaded('pthreads');
	$workers = array();
	$errstr = '';

	if($pthreadsEnabled)
	{
		require_once('../include/rp_chart_thread.class.php');
		addOutput($errstr, 1, "PTHREADS: enabled!");
	}
	else
	{
		addOutput($errstr, 0, "PTHREADS: disabled!");
	}

	if (!$db = new basis_db())
		die('Es konnte keine Verbindung zum Server aufgebaut werden.');

	// ************** Rechte Pruefen ********************
	$user = get_uid();
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($user);



	if(!isset($_REQUEST['type']))
		$type = "html";
	else
		$type = $_REQUEST['type'];

	// *************** Pruefen ob die benoetigten Programme vorhanden sind *******************

	if(!`which asciidoc`)
	{
		if($type == "debug")
			die('asciidoc ist auf diesem System nicht installiert');
		else
			die("Der Report konnte nicht erstellt werden!");
	}
	if(!`which dblatex`)
	{
		if($type == "debug")
			die('dbLatex ist auf diesem System nicht installiert');
		else
			die("Der Report konnte nicht erstellt werden!");
	}

	// *************** Asciidoc Version Prüfen *******************
	$asciidocHtmlVersion = "html5";		//standard

	$out = array();	/* empty the out array, to remove the old entries */
	exec('asciidoc --version'.' 2>&1', $out, $ret);
	$asciiVer = str_replace("asciidoc ","",$out);
	if(!version_compare ( "8.6.4" , $asciiVer[0], "<" ))
	{
		addOutput($errstr, 0, "Achtung: Diese Asciidoc Version unterstützt nur html4!");
		$asciidocHtmlVersion = "html4";
	}

	// *************** Parameter pruefen und Daten laden *******************


	$filter = new filter();
	$filter->loadAll();

	$report = new report();
	if(isset($_REQUEST['report_id']))
		$report->load((int)$_REQUEST['report_id']);
	else
		die('Es wurde keine report_id angegeben');
	$charts = new chart();
	if (!$charts->loadCharts($report->report_id))
	{
		if($type == "debug")
			die($charts->errormsg);
		else
			die("Der Report konnte nicht erstellt werden!");
	}

	//wenn der nutzer nicht für addon/reports berechtigt ist, wird abgefragt, ob der report oeffentlich ist
	//und ob der nutzer das recht für diesen report hat
	if(!$rechte->isBerechtigt("addon/reports"))
	{
		if($report->publish !== true)
			die("Dieser Report ist nicht Oeffentlich!");

		if(isset($report->berechtigung_kurzbz))
			if(!$rechte->isBerechtigt($report->berechtigung_kurzbz))
				die('Sie haben keine Berechtigung fuer diesen Report!');
	}

	//create the folder in temp, which will be removed afterwards
	if (!file_exists($reportsTmpDir))
		mkdir($reportsTmpDir, 0777, true);

	// ***** Define Filenames ******************
	$tmpFilename=$reportsTmpDir.'/Report'.$report->report_id.date('Y-m-d_H:i:s').'.tmp';
	$filename=$reportsTmpDir.'/Report'.$report->report_id;
	$docinfoFilename=$reportsTmpDir.'/Report'.$report->report_id.'-docinfo.xml';
	$htmlFilename=$reportsTmpDir.'/Report'.$report->report_id.'.html';
	$xmlFilename=$reportsTmpDir.'/Report'.$report->report_id.'.xml';
	$pdfFilename=$reportsTmpDir.'/Report'.$report->report_id.'.pdf';


	foreach($charts->chart as $chart)
	{
		if(isset($chart->statistik_kurzbz))
		{
			generateStatistik($chart->statistik_kurzbz, $reportsTmpDir, $errstr, $type);

			if(!$pthreadsEnabled)
			{
				$outputfilename=$chart->writePNG($reportsTmpDir);

				if (!$outputfilename)
				{
					addOutput($errstr, 0, "PNG not written: " . $chart->errormsg);
					cleanUpAndDie("Der Report konnte nicht erstellt werden!", $errstr, $reportsTmpDir, $type);
				}
				else
				{
					addOutput($errstr, 1, "PNG: '".$outputfilename."' has been written!");
				}
			}
			else
			{
				$workers[$chart->chart_id] = new ChartThread($chart, $reportsTmpDir);
				$workers[$chart->chart_id]->start();
			}
		}

		$description = "";
		$textile = $reportsTmpDir.'/Chart'.$chart->chart_id.'.textile';
		if(isset($chart->description))
		{
			$description = $chart->description;
		}
		file_put_contents($textile, $description);
	}

	if($pthreadsEnabled)
	{
		foreach($workers as $wk => $wv)
		{
			$wv->join();
			if (!$wv->outputfilename)
			{
				addOutput($errstr, 0, "PNG not written: " . $wv->errormsg);
				cleanUpAndDie("Der Report konnte nicht erstellt werden!", $errstr, $reportsTmpDir, $type);
			}
			else
			{
				addOutput($errstr, 1, "PNG: '".$wv->outputfilename."' has been written!");
			}
		}
	}


	$report_statistik = new rp_report_statistik();
	$report_statistik->getReportStatistiken($report->report_id);

	foreach($report_statistik->result as $s)
	{
		generateStatistik($s->statistik_kurzbz, $reportsTmpDir, $errstr, $type);
	}

	// *************** Startwerte Setzen ************************
	$crlf=PHP_EOL;
	$content = '';
	$errorstr = ''; //fehler beim insert

	switch ($report->format)
	{
		case 'asciidoc':
			$filename.='.asciidoc';
			$content.='= Report - '.$report->title.$crlf;
			$content.=$report->header.$crlf.$report->printParam('attr',$crlf).":chartDir: ".$reportsTmpDir.$crlf;
			$content.=$crlf.'<<<'.$crlf.$crlf;
			$content.=$crlf.'== Einleitung'.$crlf.$report->description.$crlf;
			$content.=$crlf.'<<<'.$crlf.$crlf;
			$content.=$crlf.'== Report'.$crlf.$report->body.$crlf;
			$content.=$crlf.'<<<'.$crlf.$crlf;
			$content.=$crlf.'== Hinweise'.$crlf.$report->footer.$crlf;
			$content.=$crlf.'=== Parameter'.$crlf.'- Erstellung: *'.date("D, j M Y").'*'.$crlf.'- Datenstand: *'.date(DATE_RFC2822).'*'.$crlf.$report->printParam('param',$crlf).$crlf;
			break;
	}

	// **** Write DocInfo
	$fh=fopen($docinfoFilename,'w');
	fwrite($fh,$report->docinfo);
	fclose($fh);
	addOutput($errstr, 1, "DOCINFO: '.$docinfoFilename.' has been written!");

	// ***** Write ContentFile
	$fh=fopen($filename,'w');
	fwrite($fh,$content);
	fclose($fh);

	addOutput($errstr, 1, "ASCIIDOC: '.$filename.' has been written!");


	/* HTML creation */
	$out = array();	/* empty the out array, to remove the old entries */
	$cmd = 'asciidoc -o '.$htmlFilename.' -b '.$asciidocHtmlVersion.' -a theme=flask -a data-uri -a toc2 -a pygments -a icons -a iconsdir='.$iconsdir.' -a asciimath '.$filename;
	exec($cmd.' 2>&1', $out, $ret);

	if($ret != 0)
	{
		addOutput($errstr, 0, "Asciidoc fehlgeschlagen:");
		foreach($out as $o)
			addOutput($errstr, 0, $o, 1);

		cleanUpAndDie("Der Report konnte nicht erstellt werden!", $errstr, $reportsTmpDir, $type);
	}
	if(count($out) > 0)
	{
		addOutput($errstr, 2, "Asciidoc Warnungen:");
		foreach($out as $o)
			addOutput($errstr, 2, $o, 1);
	}
	addOutput($errstr, 1, "HTML: '.$htmlFilename.' has been written!");

	/* PDF creation */
	$out = array();	/* empty the out array, to remove the old entries */
	$cmd = 'a2x -a docinfo -f pdf --dblatex-opts="--param=latex.encoding=utf8 -P latex.unicode.use=1 -f docbook -p ../system/asciidoc/asciidoc-dblatex.xsl" ' . $filename;
	exec($cmd.' 2>&1', $out, $ret);

	if($ret != 0)
	{
		addOutput($errstr, 0, "a2x fehlgeschlagen:");
		foreach($out as $o)
			addOutput($errstr, 0, $o, 1);

		cleanUpAndDie("Der Report konnte nicht erstellt werden!", $errstr, $reportsTmpDir, $type);
	}
	if(count($out) > 0)
	{
		addOutput($errstr, 2, "a2x Warnungen:");
		foreach($out as $o)
			addOutput($errstr, 2, $o, 1);
	}
	addOutput($errstr, 1, "PDF: '.$pdfFilename.' has been written!");


	if($type == "pdf")
	{
		header('Content-type: application/force-download');
		header('Content-Disposition: attachment; filename="'.$report->title.'.pdf"');
		readfile($pdfFilename);
	}
	else if($type == "debug")
	{
		cleanUpAndDie("", $errstr, $reportsTmpDir, $type);
	}
	else
	{
		readfile($htmlFilename);
	}
	removeFolder($reportsTmpDir);		//cleanup


	function generateStatistik($statistik_kurzbz, $reportsTmpDir, &$errstr, $type)
	{
		$statistik = new statistik($statistik_kurzbz);
		if (!$statistik->loadData())
		{
			addOutput($errstr, 0, 'Data not loaded('.$statistik->statistik_kurzbz.'): '.$statistik->errormsg);
			cleanUpAndDie('Data not loaded!'.$statistik->errormsg, $errstr, $reportsTmpDir, $type);
		}

		$vars = $statistik->parseVars($statistik->sql);

		$datafile=$reportsTmpDir.'/data'.$statistik_kurzbz.'.csv';
		if (!$statistik->writeCSV($datafile,',','"'))
		{
			addOutput($errstr, 0, 'File '.$datafile.' not written: ' . $statistik->errormsg);
			cleanUpAndDie("Der Report konnte nicht erstellt werden!", $errstr, $reportsTmpDir, $type);
		}
		else
			addOutput($errstr, 1, "CSV: '.$datafile.' has been written!");
	}





	function cleanUpAndDie($msg, $errstr, $reportsTmpDir, $type)
	{
		if($type == "debug")
		{
			echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
			echo '<html>';
			echo '<head>';
			echo '<title>Reports - Generate</title>';
			echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
			echo '<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">';
			echo '</head>';
			echo '<body style="background-color:#eeeeee;">';
			echo $errstr;
			echo '</body>';
			echo '</html>';
			die("");
		}
		removeFolder($reportsTmpDir);
		die($msg);
	}



	function removeFolder($dir)
	{
		if($dir == "/")
			return false;
		if (is_dir($dir) === true)
		{
			$files = array_diff(scandir($dir), array('.', '..'));
			foreach ($files as $file)
			{
				unlink($dir . "/" . $file);
			}
			return rmdir($dir);
		}
		return false;
	}

	function addOutput(&$str, $level, $addstr, $offset = 0)
	{
		$offset *= 20;

		$color = "orange";
		switch($level)
		{
			case 0:
				$color = "red";
				break;
			case 1:
				$color = "green";
				break;
		}
		$str .= '<p style="color:'.$color.'; margin-left:'.$offset.'">'.$addstr.'</p>';
	}
?>
