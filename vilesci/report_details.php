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
	
	
	$reloadstr = '';  // neuladen der liste im oberen frame
	$htmlstr = '';
	$errorstr = ''; //fehler beim insert
	$sel = '';
	$chk = '';

	$report = new report();
	$report->report_id		= 0;
	$report->title 			= 'NewReport';
	$report->description		= '=== Beschreibung';
	$report->format			= 'asciidoc';
	$report->header		= "";
	$report->body		= "=== Chart\n=== Data";
	$report->footer		= "=== Hinweise\n[horizontal]\n==== Mögliche Fehlerquellen:\n- ";
	$report->docinfo	= file_get_contents('../data/template-docinfo.xml');
	$report->insertvon		= $user;
	$report->updatevon		= $user;
	
	if(isset($_REQUEST["action"]) && isset($_REQUEST["report_id"]))
	{
		if(!$rechte->isBerechtigt('addon/reports', null, 'suid'))
			die('Sie haben keine Berechtigung fuer diese Aktion');
	
		// echo 'DI_ID: '.var_dump((int)$_POST["report_id"]);
		// Wenn id > 0 ist -> Neuer Datensatz; ansonsten load und update
		if ( ((int)$_REQUEST["report_id"]) > 0)
			$report->load((int)$_REQUEST["report_id"]);
		if ($_REQUEST["action"]=='save')
		{
			$report->title = $_POST["title"];
			$report->description = $_POST["description"];
			$report->format = $_POST["format"];
			$report->header = $_POST["header"];
			$report->body = $_POST["body"];
			$report->footer = $_POST["footer"];
			$report->docinfo = $_POST["docinfo"];
			$report->publish = isset($_POST["publish"]);
			$report->gruppe = $_POST["gruppe"];
			
			if(!$report->save())
			{
				$errorstr .= $report->errormsg;
			}
		
			$reloadstr .= "<script type='text/javascript'>\n";
			$reloadstr .= "	parent.frame_report_overview.location.href='report_overview.php';";
			$reloadstr .= "</script>\n";
		}
	}

	if ((isset($_REQUEST['report_id'])) && ((!isset($_REQUEST['neu'])) || ($_REQUEST['neu']!= "true")))
	{
		//echo 'loadChart';
		$report->load($_REQUEST["report_id"]);
		if ($report->errormsg!='')
			die($report->errormsg);
	}

    if($report->report_id > 0)
        $htmlstr .= "<br><div class='kopf'>Report <b>".$report->report_id."</b></div>\n";
    else
        $htmlstr .="<br><div class='kopf'>Neuer Report</div>\n"; 
	$htmlstr .= "<form action='report_details.php' method='POST' name='reportform'>\n";
	$htmlstr .= "	<table class='detail'>\n";
	$htmlstr .= "			<tr>\n";
	$htmlstr .= "				<td>Title</td>\n";
	$htmlstr .= "				<td><input class='detail' type='text' name='title' size='22' maxlength='32' value='".$db->convert_html_chars($report->title)."' onchange='submitable()'>\n";
	$htmlstr .= "				Format: <input class='detail' type='text' name='format' size='8' maxlength='512' value='".$db->convert_html_chars($report->format)."' onchange='submitable()'></td>\n";
	$htmlstr .= "				<td>Gruppe</td>\n";
	$htmlstr .= "				<td><input class='detail' type='text' name='gruppe' size='22' maxlength='32' value='".$db->convert_html_chars($report->gruppe)."' onchange='submitable()'>\n";
	$htmlstr .= "				Publish: <input class='detail' type='checkbox' name='publish' ".($report->publish?'checked="checked"':'')." onchange='submitable()'></td>\n";
	$htmlstr .= "			</tr>\n";
	$htmlstr .= "			<tr>\n";
	$htmlstr .= "				<td valign='top'>Description</td>\n";
	$htmlstr .= " 				<td ><textarea name='description' cols='70' rows='6' onchange='submitable()'>".$db->convert_html_chars($report->description)."</textarea></td>\n";
	$htmlstr .= "				<td valign='top'>Header</td>\n";
	$htmlstr .= " 				<td colspan='2'><textarea name='header' cols='70' rows='6' onchange='submitable()'>".$db->convert_html_chars($report->header)."</textarea></td>\n";
	$htmlstr .= "			</tr>\n";
	$htmlstr .= "			<tr>\n";
	$htmlstr .= "				<td rowspan='2' valign='top'>Body</td>\n";
	$htmlstr .= " 				<td rowspan='2'><textarea name='body' cols='70' rows='14' onchange='submitable()'>".$db->convert_html_chars($report->body)."</textarea></td>\n";
	$htmlstr .= "				<td valign='top'>Footer</td>\n";
	$htmlstr .= " 				<td colspan='2'><textarea name='footer' cols='70' rows='6' onchange='submitable()'>".$db->convert_html_chars($report->footer)."</textarea></td>\n";
	$htmlstr .= "			</tr>\n";
	$htmlstr .= "			<tr>\n";
	$htmlstr .= "				<td valign='top'>DocInfo</td>\n";
	$htmlstr .= " 				<td ><textarea name='docinfo' cols='70' rows='6' onchange='submitable()'>".$db->convert_html_chars($report->docinfo)."</textarea></td>\n";
	$htmlstr .= "			</tr>\n";
	$htmlstr .= "	</table>\n";
	$htmlstr .= "<br>\n";
	$htmlstr .= "<div align='right' id='sub'>\n";
	$htmlstr .= "	<span id='submsg' style='color:red; visibility:hidden;'>Datensatz ge&auml;ndert!&nbsp;&nbsp;</span>\n";
	$htmlstr .= "	<input type='hidden' name='report_id' value='".$report->report_id."'>";
	$htmlstr .= "	<input type='submit' value='save' name='action'>\n";
	$htmlstr .= "	<input type='button' value='Reset' onclick='unchanged()'>\n";
	$htmlstr .= "</div>";
	$htmlstr .= "</form>";
	$htmlstr .= "<div class='inserterror'>".$errorstr."</div>"
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>DI-Quelle - Details</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
<script src="../../../include/js/mailcheck.js"></script>
<script src="../../../include/js/datecheck.js"></script>
<script type="text/javascript">
function unchanged()
{
		document.reportform.reset();
		document.reportform.schick.disabled = true;
		document.getElementById("submsg").style.visibility="hidden";
		checkrequired(document.reportform.report_id);
}

function checkrequired(feld)
{
	if(feld.value == '')
	{
		feld.className = "input_error";
		return false;
	}
	else
	{
		feld.className = "input_ok";
		return true;
	}
}

function submitable()
{
	required1 = checkrequired(document.reportform.report_id);

	if(!required1)
	{
		document.reportform.schick.disabled = true;
		document.getElementById("submsg").style.visibility="hidden";
	}
	else
	{
		document.reportform.schick.disabled = false;
		document.getElementById("submsg").style.visibility="visible";
	}
}
</script>
</head>
<body style="background-color:#eeeeee;">

<?php
	echo $htmlstr;
	echo $reloadstr;
?>

</body>
</html>
